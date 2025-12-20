<?php

class Tenet_Admin {

    private $generator;

    public function __construct() {
        $this->generator = new Tenet_Generator();
    }

    public function init() {
        add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
    }

    public function add_admin_menu() {
        add_menu_page(
            'Tenet Generator',
            'Tenet',
            'manage_options',
            'tenet',
            array( $this, 'render_generator_page' ),
            'dashicons-superhero',
            6
        );

        add_submenu_page(
            'tenet',
            'Configurações',
            'Configurações',
            'manage_options',
            'tenet-settings',
            array( $this, 'render_settings_page' )
        );
    }

    public function register_settings() {
        register_setting( 'tenet_settings_group', 'tenet_openai_key' );
        register_setting( 'tenet_settings_group', 'tenet_pixabay_key' );
        register_setting( 'tenet_settings_group', 'tenet_post_status' );

        // Default content settings with sanitization
        register_setting( 'tenet_settings_group', 'tenet_default_tone', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'tenet_settings_group', 'tenet_default_audience', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'tenet_settings_group', 'tenet_default_instructions', array( 'sanitize_callback' => 'sanitize_textarea_field' ) );
        register_setting( 'tenet_settings_group', 'tenet_default_category', array( 'sanitize_callback' => 'absint' ) );
    }

    public function render_settings_page() {
        ?>
        <div class="wrap">
            <h1>Configurações do Tenet</h1>
            <form method="post" action="options.php">
                <?php settings_fields( 'tenet_settings_group' ); ?>
                <?php do_settings_sections( 'tenet_settings_group' ); ?>
                <table class="form-table">
                    <tr valign="top">
                        <th scope="row">OpenAI API Key</th>
                        <td><input type="password" name="tenet_openai_key" value="<?php echo esc_attr( get_option('tenet_openai_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Pixabay API Key</th>
                        <td><input type="password" name="tenet_pixabay_key" value="<?php echo esc_attr( get_option('tenet_pixabay_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Status Padrão do Post</th>
                        <td>
                            <select name="tenet_post_status">
                                <option value="draft" <?php selected( get_option('tenet_post_status'), 'draft' ); ?>>Rascunho</option>
                                <option value="publish" <?php selected( get_option('tenet_post_status'), 'publish' ); ?>>Publicado</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Tom de Voz Padrão</th>
                        <td>
                            <select name="tenet_default_tone">
                                <option value="Técnico" <?php selected( get_option('tenet_default_tone'), 'Técnico' ); ?>>Técnico</option>
                                <option value="Humorístico" <?php selected( get_option('tenet_default_tone'), 'Humorístico' ); ?>>Humorístico</option>
                                <option value="Jornalístico" <?php selected( get_option('tenet_default_tone'), 'Jornalístico' ); ?>>Jornalístico</option>
                                <option value="Acadêmico" <?php selected( get_option('tenet_default_tone'), 'Acadêmico' ); ?>>Acadêmico</option>
                            </select>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Público Alvo Padrão</th>
                        <td><input type="text" name="tenet_default_audience" value="<?php echo esc_attr( get_option('tenet_default_audience') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Categoria Padrão</th>
                        <td>
                            <?php
                            wp_dropdown_categories( array(
                                'name'              => 'tenet_default_category',
                                'show_option_none'  => 'Sem Categoria',
                                'option_none_value' => '0',
                                'hide_empty'        => 0,
                                'selected'          => get_option('tenet_default_category', 0),
                            ) );
                            ?>
                        </td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Instruções Extras Padrão</th>
                        <td><textarea name="tenet_default_instructions" rows="5" class="large-text"><?php echo esc_textarea( get_option('tenet_default_instructions') ); ?></textarea></td>
                    </tr>
                </table>
                <?php submit_button(); ?>
            </form>
        </div>
        <?php
    }

    public function render_generator_page() {
        $message = '';
        if ( isset( $_POST['tenet_generate'] ) && check_admin_referer( 'tenet_generate_action', 'tenet_nonce' ) ) {
            $topic = sanitize_text_field( $_POST['topic'] );
            $tone = sanitize_text_field( $_POST['tone'] );
            $audience = sanitize_text_field( $_POST['audience'] );
            $instructions = sanitize_textarea_field( $_POST['instructions'] );
            $category_id = isset( $_POST['tenet_category'] ) ? (int) $_POST['tenet_category'] : 0;

            try {
                $post_id = $this->generator->generate_content( $topic, $tone, $audience, $instructions, $category_id );
                $message = '<div class="notice notice-success is-dismissible"><p>Conteúdo gerado com sucesso! Post ID: <a href="' . get_edit_post_link( $post_id ) . '">' . $post_id . '</a></p></div>';
            } catch ( Exception $e ) {
                $message = '<div class="notice notice-error is-dismissible"><p>Erro: ' . esc_html( $e->getMessage() ) . '</p></div>';
            }
        }

        ?>
        <div class="wrap">
            <h1>Tenet - Gerador de Conteúdo</h1>
            <?php echo $message; ?>
            <form method="post" action="">
                <?php wp_nonce_field( 'tenet_generate_action', 'tenet_nonce' ); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="topic">Tópico Principal</label></th>
                        <td><input name="topic" type="text" id="topic" class="regular-text" required></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tone">Tom de Voz</label></th>
                        <td>
                            <?php $default_tone = get_option('tenet_default_tone', 'Técnico'); ?>
                            <select name="tone" id="tone">
                                <option value="Técnico" <?php selected( $default_tone, 'Técnico' ); ?>>Técnico</option>
                                <option value="Humorístico" <?php selected( $default_tone, 'Humorístico' ); ?>>Humorístico</option>
                                <option value="Jornalístico" <?php selected( $default_tone, 'Jornalístico' ); ?>>Jornalístico</option>
                                <option value="Acadêmico" <?php selected( $default_tone, 'Acadêmico' ); ?>>Acadêmico</option>
                            </select>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="audience">Público Alvo</label></th>
                        <td><input name="audience" type="text" id="audience" class="regular-text" value="<?php echo esc_attr( get_option('tenet_default_audience') ); ?>"></td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="tenet_category">Categoria</label></th>
                        <td>
                            <?php
                            wp_dropdown_categories( array(
                                'name'              => 'tenet_category',
                                'show_option_none'  => 'Sem Categoria (Padrão)',
                                'option_none_value' => '0',
                                'hide_empty'        => 0,
                                'selected'          => isset( $_POST['tenet_category'] ) ? (int) $_POST['tenet_category'] : get_option('tenet_default_category', 0),
                            ) );
                            ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><label for="instructions">Instruções Extras</label></th>
                        <td><textarea name="instructions" id="instructions" rows="5" class="large-text"><?php echo esc_textarea( get_option('tenet_default_instructions') ); ?></textarea></td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="tenet_generate" id="submit" class="button button-primary" value="Gerar Conteúdo">
                </p>
            </form>
        </div>
        <?php
    }
}
