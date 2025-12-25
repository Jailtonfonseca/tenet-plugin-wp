<?php

class Tenet_Admin {

    private $generator;

    public function __construct() {
        $this->generator = new Tenet_Generator();
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'migrate_settings' ) );

        // Register Async Generation Hook
        add_action( 'tenet_async_generation_event', array( $this->generator, 'generate_content' ), 10, 6 );
    }

    public function migrate_settings() {
        // If profiles already exist, do nothing
        if ( get_option( 'tenet_profiles' ) !== false ) {
            return;
        }

        // Create Default profile from legacy options
        $default_settings = array(
            'tenet_post_status'      => get_option( 'tenet_post_status', 'draft' ),
            'tenet_pixabay_key'      => get_option( 'tenet_pixabay_key', '' ),
            'tenet_ai_provider'      => get_option( 'tenet_ai_provider', 'openai' ),

            'tenet_openai_key'       => get_option( 'tenet_openai_key', '' ),
            'tenet_openai_model'     => get_option( 'tenet_openai_model', 'gpt-4o' ),

            'tenet_gemini_key'       => get_option( 'tenet_gemini_key', '' ),
            'tenet_gemini_model'     => get_option( 'tenet_gemini_model', 'gemini-1.5-pro' ),

            'tenet_openrouter_key'   => get_option( 'tenet_openrouter_key', '' ),
            'tenet_openrouter_model' => get_option( 'tenet_openrouter_model', '' ),

            'tenet_default_tone'         => get_option( 'tenet_default_tone', 'Técnico' ),
            'tenet_default_audience'     => get_option( 'tenet_default_audience', '' ),
            'tenet_default_category'     => get_option( 'tenet_default_category', 0 ),
            'tenet_default_instructions' => get_option( 'tenet_default_instructions', '' ),
        );

        $profiles = array(
            1 => array(
                'id'       => 1,
                'name'     => __( 'Padrão (Importado)', 'tenet' ),
                'settings' => $default_settings
            )
        );

        update_option( 'tenet_profiles', $profiles );
    }

    public function add_admin_menu() {
        add_menu_page(
            __( 'Tenet Generator', 'tenet' ),
            'Tenet',
            'manage_options',
            'tenet',
            array( $this, 'render_generator_page' ),
            'dashicons-superhero',
            6
        );

        add_submenu_page(
            'tenet',
            __( 'Perfis de Configuração', 'tenet' ),
            __( 'Configurações', 'tenet' ),
            'manage_options',
            'tenet-settings',
            array( $this, 'render_settings_page' )
        );
    }

    private function get_profiles() {
        return get_option( 'tenet_profiles', array() );
    }

    private function get_profile( $id ) {
        $profiles = $this->get_profiles();
        return isset( $profiles[ $id ] ) ? $profiles[ $id ] : null;
    }

    private function save_profile( $id, $name, $settings ) {
        $profiles = $this->get_profiles();

        // Generate ID if new
        if ( empty( $id ) ) {
            $id = empty( $profiles ) ? 1 : max( array_keys( $profiles ) ) + 1;
        }

        // Sanitize Settings
        $clean_settings = array(
            'tenet_post_status'      => sanitize_text_field( $settings['tenet_post_status'] ),
            'tenet_pixabay_key'      => sanitize_text_field( $settings['tenet_pixabay_key'] ),
            'tenet_ai_provider'      => sanitize_text_field( $settings['tenet_ai_provider'] ),

            'tenet_openai_key'       => sanitize_text_field( $settings['tenet_openai_key'] ),
            'tenet_openai_model'     => sanitize_text_field( $settings['tenet_openai_model'] ),

            'tenet_gemini_key'       => sanitize_text_field( $settings['tenet_gemini_key'] ),
            'tenet_gemini_model'     => sanitize_text_field( $settings['tenet_gemini_model'] ),

            'tenet_openrouter_key'   => sanitize_text_field( $settings['tenet_openrouter_key'] ),
            'tenet_openrouter_model' => sanitize_text_field( $settings['tenet_openrouter_model'] ),

            'tenet_default_tone'         => sanitize_text_field( $settings['tenet_default_tone'] ),
            'tenet_default_audience'     => sanitize_text_field( $settings['tenet_default_audience'] ),
            'tenet_default_category'     => absint( $settings['tenet_default_category'] ),
            'tenet_default_instructions' => sanitize_textarea_field( $settings['tenet_default_instructions'] ),
        );

        $profiles[ $id ] = array(
            'id'       => $id,
            'name'     => sanitize_text_field( $name ),
            'settings' => $clean_settings
        );

        update_option( 'tenet_profiles', $profiles );
        return $id;
    }

    private function duplicate_profile( $id ) {
        $profiles = $this->get_profiles();
        if ( isset( $profiles[ $id ] ) ) {
            $new_id = empty( $profiles ) ? 1 : max( array_keys( $profiles ) ) + 1;
            $new_profile = $profiles[ $id ];
            $new_profile['id'] = $new_id;
            $new_profile['name'] .= ' (' . __( 'Cópia', 'tenet' ) . ')';

            $profiles[ $new_id ] = $new_profile;
            update_option( 'tenet_profiles', $profiles );
        }
    }

    private function delete_profile( $id ) {
        $profiles = $this->get_profiles();
        if ( isset( $profiles[ $id ] ) ) {
            unset( $profiles[ $id ] );
            update_option( 'tenet_profiles', $profiles );
        }
    }

    public function render_settings_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $action = isset( $_GET['action'] ) ? $_GET['action'] : 'list';
        $message = '';

        // Handle POST actions
        if ( $_SERVER['REQUEST_METHOD'] === 'POST' && check_admin_referer( 'tenet_save_profile', 'tenet_nonce' ) ) {
            if ( isset( $_POST['tenet_action'] ) && $_POST['tenet_action'] === 'save' ) {
                $id = isset( $_POST['profile_id'] ) ? intval( $_POST['profile_id'] ) : 0;
                $name = isset( $_POST['profile_name'] ) ? $_POST['profile_name'] : __( 'Novo Perfil', 'tenet' );
                $settings = $_POST;

                $this->save_profile( $id, $name, $settings );
                $message = '<div class="notice notice-success is-dismissible"><p>' . __( 'Perfil salvo com sucesso.', 'tenet' ) . '</p></div>';
                $action = 'list';
            }
        }

        // Handle GET Actions (Delete, Duplicate)
        if ( isset( $_GET['id'] ) ) {
            $id = intval( $_GET['id'] );

            if ( $action === 'delete' && check_admin_referer( 'tenet_delete_profile' ) ) {
                $this->delete_profile( $id );
                $message = '<div class="notice notice-success is-dismissible"><p>' . __( 'Perfil excluído.', 'tenet' ) . '</p></div>';
                $action = 'list';
            } elseif ( $action === 'duplicate' && check_admin_referer( 'tenet_duplicate_profile' ) ) {
                $this->duplicate_profile( $id );
                $message = '<div class="notice notice-success is-dismissible"><p>' . __( 'Perfil duplicado com sucesso.', 'tenet' ) . '</p></div>';
                $action = 'list';
            }
        }

        echo '<div class="wrap"><h1>' . __( 'Configurações do Tenet (Perfis)', 'tenet' ) . '</h1>' . $message;

        if ( $action == 'edit' || $action == 'new' ) {
            $this->render_profile_form();
        } else {
            $this->render_profile_list();
        }

        echo '</div>';
    }

    private function render_profile_list() {
        $profiles = $this->get_profiles();
        ?>
        <p><?php _e( 'Gerencie seus perfis de configuração. Você pode criar perfis diferentes para nichos, idiomas ou estilos diferentes.', 'tenet' ); ?></p>
        <p><a href="<?php echo admin_url( 'admin.php?page=tenet-settings&action=new' ); ?>" class="button button-primary"><?php _e( 'Adicionar Novo Perfil', 'tenet' ); ?></a></p>

        <table class="wp-list-table widefat fixed striped">
            <thead>
                <tr>
                    <th><?php _e( 'Nome do Perfil', 'tenet' ); ?></th>
                    <th><?php _e( 'Provedor de IA', 'tenet' ); ?></th>
                    <th><?php _e( 'Status do Post', 'tenet' ); ?></th>
                    <th><?php _e( 'Ações', 'tenet' ); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php if ( empty( $profiles ) ) : ?>
                    <tr><td colspan="4"><?php _e( 'Nenhum perfil encontrado. Crie um novo.', 'tenet' ); ?></td></tr>
                <?php else : ?>
                    <?php foreach ( $profiles as $profile ) :
                        $edit_url = admin_url( 'admin.php?page=tenet-settings&action=edit&id=' . $profile['id'] );
                        $dup_url = wp_nonce_url( admin_url( 'admin.php?page=tenet-settings&action=duplicate&id=' . $profile['id'] ), 'tenet_duplicate_profile' );
                        $delete_url = wp_nonce_url( admin_url( 'admin.php?page=tenet-settings&action=delete&id=' . $profile['id'] ), 'tenet_delete_profile' );
                        ?>
                        <tr>
                            <td><strong><?php echo esc_html( $profile['name'] ); ?></strong></td>
                            <td><?php echo esc_html( strtoupper( $profile['settings']['tenet_ai_provider'] ) ); ?></td>
                            <td><?php echo esc_html( ucfirst( $profile['settings']['tenet_post_status'] ) ); ?></td>
                            <td>
                                <a href="<?php echo $edit_url; ?>" class="button button-small"><?php _e( 'Editar', 'tenet' ); ?></a>
                                <a href="<?php echo $dup_url; ?>" class="button button-small"><?php _e( 'Duplicar', 'tenet' ); ?></a>
                                <a href="<?php echo $delete_url; ?>" class="button button-small button-link-delete" onclick="return confirm('<?php _e( 'Tem certeza que deseja excluir este perfil?', 'tenet' ); ?>');"><?php _e( 'Excluir', 'tenet' ); ?></a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
        <?php
    }

    private function render_profile_form() {
        $id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
        $profile = $this->get_profile( $id );
        $settings = $profile ? $profile['settings'] : array();

        $val = function( $key, $default = '' ) use ( $settings ) {
            return isset( $settings[$key] ) ? $settings[$key] : $default;
        };
        ?>
        <form method="post" action="">
            <?php wp_nonce_field( 'tenet_save_profile', 'tenet_nonce' ); ?>
            <input type="hidden" name="tenet_action" value="save">
            <input type="hidden" name="profile_id" value="<?php echo esc_attr( $id ); ?>">

            <p><a href="<?php echo admin_url( 'admin.php?page=tenet-settings' ); ?>">&larr; <?php _e( 'Voltar para a lista', 'tenet' ); ?></a></p>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Nome do Perfil', 'tenet' ); ?></th>
                    <td><input type="text" name="profile_name" value="<?php echo esc_attr( $profile ? $profile['name'] : '' ); ?>" class="regular-text" required placeholder="<?php _e( 'Ex: Tech Blog', 'tenet' ); ?>" /></td>
                </tr>
            </table>

            <hr>

            <table class="form-table">
                <tr valign="top">
                    <th scope="row"><?php _e( 'Pixabay API Key', 'tenet' ); ?></th>
                    <td><input type="password" name="tenet_pixabay_key" value="<?php echo esc_attr( $val('tenet_pixabay_key') ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Status Padrão do Post', 'tenet' ); ?></th>
                    <td>
                        <select name="tenet_post_status">
                            <option value="draft" <?php selected( $val('tenet_post_status'), 'draft' ); ?>><?php _e( 'Rascunho', 'tenet' ); ?></option>
                            <option value="publish" <?php selected( $val('tenet_post_status'), 'publish' ); ?>><?php _e( 'Publicado', 'tenet' ); ?></option>
                        </select>
                    </td>
                </tr>

                <tr><td colspan="2"><hr><h2><?php _e( 'Provedor de IA', 'tenet' ); ?></h2></td></tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Provedor Ativo', 'tenet' ); ?></th>
                    <td>
                        <select name="tenet_ai_provider">
                            <option value="openai" <?php selected( $val('tenet_ai_provider'), 'openai' ); ?>>OpenAI</option>
                            <option value="gemini" <?php selected( $val('tenet_ai_provider'), 'gemini' ); ?>>Google Gemini</option>
                            <option value="openrouter" <?php selected( $val('tenet_ai_provider'), 'openrouter' ); ?>>OpenRouter</option>
                        </select>
                    </td>
                </tr>

                <tr><td colspan="2"><h3>OpenAI</h3></td></tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'OpenAI API Key', 'tenet' ); ?></th>
                    <td><input type="password" name="tenet_openai_key" value="<?php echo esc_attr( $val('tenet_openai_key') ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Modelo OpenAI', 'tenet' ); ?></th>
                    <td><input type="text" name="tenet_openai_model" value="<?php echo esc_attr( $val('tenet_openai_model', 'gpt-4o') ); ?>" class="regular-text" placeholder="Ex: gpt-4o" /></td>
                </tr>

                <tr><td colspan="2"><h3>Google Gemini</h3></td></tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Gemini API Key', 'tenet' ); ?></th>
                    <td><input type="password" name="tenet_gemini_key" value="<?php echo esc_attr( $val('tenet_gemini_key') ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Modelo Gemini', 'tenet' ); ?></th>
                    <td><input type="text" name="tenet_gemini_model" value="<?php echo esc_attr( $val('tenet_gemini_model', 'gemini-1.5-pro') ); ?>" class="regular-text" placeholder="Ex: gemini-1.5-pro" /></td>
                </tr>

                <tr><td colspan="2"><h3>OpenRouter</h3></td></tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'OpenRouter API Key', 'tenet' ); ?></th>
                    <td><input type="password" name="tenet_openrouter_key" value="<?php echo esc_attr( $val('tenet_openrouter_key') ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Modelo OpenRouter', 'tenet' ); ?></th>
                    <td><input type="text" name="tenet_openrouter_model" value="<?php echo esc_attr( $val('tenet_openrouter_model') ); ?>" class="regular-text" placeholder="Ex: anthropic/claude-3-opus" /></td>
                </tr>

                <tr><td colspan="2"><hr><h2><?php _e( 'Padrões de Conteúdo', 'tenet' ); ?></h2></td></tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Tom de Voz Padrão', 'tenet' ); ?></th>
                    <td>
                        <input type="text" name="tenet_default_tone" value="<?php echo esc_attr( $val('tenet_default_tone', 'Técnico') ); ?>" class="regular-text" placeholder="<?php _e( 'Ex: Sarcástico e ácido', 'tenet' ); ?>" />
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Público Alvo Padrão', 'tenet' ); ?></th>
                    <td><input type="text" name="tenet_default_audience" value="<?php echo esc_attr( $val('tenet_default_audience') ); ?>" class="regular-text" /></td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Categoria Padrão', 'tenet' ); ?></th>
                    <td>
                        <?php
                        wp_dropdown_categories( array(
                            'name'              => 'tenet_default_category',
                            'show_option_none'  => __( 'Sem Categoria', 'tenet' ),
                            'option_none_value' => '0',
                            'hide_empty'        => 0,
                            'selected'          => $val('tenet_default_category', 0),
                        ) );
                        ?>
                    </td>
                </tr>
                <tr valign="top">
                    <th scope="row"><?php _e( 'Instruções Extras Padrão', 'tenet' ); ?></th>
                    <td><textarea name="tenet_default_instructions" rows="5" class="large-text"><?php echo esc_textarea( $val('tenet_default_instructions') ); ?></textarea></td>
                </tr>
            </table>
            <?php submit_button( __( 'Salvar Perfil', 'tenet' ) ); ?>
        </form>
        <?php
    }

    public function render_generator_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $message = '';
        $profiles = $this->get_profiles();

        if ( isset( $_POST['tenet_generate'] ) && check_admin_referer( 'tenet_generate_action', 'tenet_nonce' ) ) {
            $topic = sanitize_text_field( $_POST['topic'] );
            $tone = sanitize_text_field( $_POST['tone'] );
            $audience = sanitize_text_field( $_POST['audience'] );
            $instructions = sanitize_textarea_field( $_POST['instructions'] );
            $category_id = isset( $_POST['tenet_category'] ) ? (int) $_POST['tenet_category'] : 0;
            $profile_id = isset( $_POST['tenet_profile_id'] ) ? (int) $_POST['tenet_profile_id'] : 0;

            $args = array( $topic, $tone, $audience, $instructions, $category_id, $profile_id );

            if ( wp_schedule_single_event( time(), 'tenet_async_generation_event', $args ) ) {
                 $message = '<div class="notice notice-success is-dismissible"><p>' . __( 'Geração de conteúdo agendada com o perfil selecionado! Atualize em breve.', 'tenet' ) . '</p></div>';
                 spawn_cron();
            } else {
                 $message = '<div class="notice notice-error is-dismissible"><p>' . __( 'Erro ao agendar a tarefa. Verifique o sistema de CRON.', 'tenet' ) . '</p></div>';
            }
        }

        $selected_profile_id = isset( $_POST['tenet_profile_id'] ) ? (int) $_POST['tenet_profile_id'] : 0;
        if ( ! $selected_profile_id && ! empty( $profiles ) ) {
            $first_id = array_key_first( $profiles );
            $selected_profile_id = $first_id;
        }

        $current_profile_settings = array();
        if ( isset( $profiles[$selected_profile_id] ) ) {
            $current_profile_settings = $profiles[$selected_profile_id]['settings'];
        }

        $get_default = function($key, $fallback) use ($current_profile_settings) {
            return isset($current_profile_settings[$key]) ? $current_profile_settings[$key] : $fallback;
        };

        ?>
        <div class="wrap">
            <h1><?php _e( 'Tenet - Gerador de Conteúdo', 'tenet' ); ?></h1>
            <?php echo $message; ?>

            <form method="post" action="">
                <?php wp_nonce_field( 'tenet_generate_action', 'tenet_nonce' ); ?>

                <div class="card" style="max-width: 100%; margin-top: 20px; padding: 10px;">
                    <h2><?php _e( 'Configuração da Geração', 'tenet' ); ?></h2>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><label for="tenet_profile_id"><?php _e( 'Perfil de Configuração', 'tenet' ); ?></label></th>
                            <td>
                                <select name="tenet_profile_id" id="tenet_profile_id" onchange="this.form.submit()">
                                    <?php foreach ( $profiles as $p_id => $p_data ) : ?>
                                        <option value="<?php echo esc_attr( $p_id ); ?>" <?php selected( $selected_profile_id, $p_id ); ?>>
                                            <?php echo esc_html( $p_data['name'] ); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <p class="description"><?php _e( 'Selecione qual perfil de chaves de API e persona usar.', 'tenet' ); ?></p>
                            </td>
                        </tr>
                    </table>
                </div>

                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="topic"><?php _e( 'Tópico Principal', 'tenet' ); ?></label></th>
                        <td><input name="topic" type="text" id="topic" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tone"><?php _e( 'Tom de Voz', 'tenet' ); ?></label></th>
                        <td>
                            <?php $default_tone = $get_default('tenet_default_tone', 'Técnico'); ?>
                            <input name="tone" type="text" id="tone" class="regular-text" value="<?php echo esc_attr( $default_tone ); ?>" placeholder="<?php _e( 'Ex: Sarcástico, Poético, Técnico...', 'tenet' ); ?>">
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="audience"><?php _e( 'Público Alvo', 'tenet' ); ?></label></th>
                        <td><input name="audience" type="text" id="audience" class="regular-text" value="<?php echo esc_attr( $get_default('tenet_default_audience', '') ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tenet_category"><?php _e( 'Categoria', 'tenet' ); ?></label></th>
                        <td>
                            <?php
                            wp_dropdown_categories( array(
                                'name'              => 'tenet_category',
                                'show_option_none'  => __( 'Sem Categoria (Padrão)', 'tenet' ),
                                'option_none_value' => '0',
                                'hide_empty'        => 0,
                                'selected'          => isset( $_POST['tenet_category'] ) ? (int) $_POST['tenet_category'] : $get_default('tenet_default_category', 0),
                            ) );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="instructions"><?php _e( 'Instruções Extras', 'tenet' ); ?></label></th>
                        <td><textarea name="instructions" id="instructions" rows="5" class="large-text"><?php echo esc_textarea( $get_default('tenet_default_instructions', '') ); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="tenet_generate" id="submit" class="button button-primary" value="<?php _e( 'Gerar Conteúdo', 'tenet' ); ?>">
                </p>
            </form>

            <hr>

            <h2><?php _e( 'Últimos Posts Gerados', 'tenet' ); ?></h2>
            <p><a href="<?php echo esc_url( menu_page_url( 'tenet', false ) ); ?>" class="button"><?php _e( 'Atualizar Lista', 'tenet' ); ?></a></p>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th scope="col" class="manage-column column-title"><?php _e( 'Título', 'tenet' ); ?></th>
                        <th scope="col" class="manage-column column-date"><?php _e( 'Data', 'tenet' ); ?></th>
                        <th scope="col" class="manage-column column-status"><?php _e( 'Status', 'tenet' ); ?></th>
                        <th scope="col" class="manage-column column-primary"><?php _e( 'Ações', 'tenet' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $args = array(
                        'post_type'   => 'post',
                        'post_status' => 'any',
                        'meta_key'    => '_tenet_generated',
                        'meta_value'  => '1',
                        'posts_per_page' => 5,
                        'orderby'     => 'date',
                        'order'       => 'DESC'
                    );
                    $query = new WP_Query( $args );

                    if ( $query->have_posts() ) {
                        while ( $query->have_posts() ) {
                            $query->the_post();
                            $post_id = get_the_ID();
                            $edit_link = get_edit_post_link( $post_id );
                            $status = get_post_status();
                            $status_obj = get_post_status_object( $status );
                            $status_label = $status_obj ? $status_obj->label : ucfirst( $status );
                            ?>
                            <tr>
                                <td><strong><?php the_title(); ?></strong></td>
                                <td><?php echo get_the_date( 'd/m/Y H:i' ); ?></td>
                                <td><?php echo esc_html( $status_label ); ?></td>
                                <td>
                                    <?php if ( $edit_link ) : ?>
                                        <a href="<?php echo esc_url( $edit_link ); ?>" class="button button-small button-secondary" target="_blank"><?php _e( 'Editar', 'tenet' ); ?></a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php
                        }
                        wp_reset_postdata();
                    } else {
                        ?>
                        <tr>
                            <td colspan="4"><?php _e( 'Nenhum post gerado recentemente.', 'tenet' ); ?></td>
                        </tr>
                        <?php
                    }
                    ?>
                </tbody>
            </table>
        </div>
        <?php
    }
}
