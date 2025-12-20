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
        register_setting( 'tenet_settings_group', 'tenet_post_status' );
        register_setting( 'tenet_settings_group', 'tenet_pixabay_key' );

        // AI Provider Settings
        register_setting( 'tenet_settings_group', 'tenet_ai_provider', array( 'sanitize_callback' => 'sanitize_text_field' ) );

        // OpenAI
        register_setting( 'tenet_settings_group', 'tenet_openai_key' );
        register_setting( 'tenet_settings_group', 'tenet_openai_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );

        // Gemini
        register_setting( 'tenet_settings_group', 'tenet_gemini_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'tenet_settings_group', 'tenet_gemini_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );

        // OpenRouter
        register_setting( 'tenet_settings_group', 'tenet_openrouter_key', array( 'sanitize_callback' => 'sanitize_text_field' ) );
        register_setting( 'tenet_settings_group', 'tenet_openrouter_model', array( 'sanitize_callback' => 'sanitize_text_field' ) );

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
                    <!-- General Settings -->
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

                    <!-- AI Providers -->
                    <tr><td colspan="2"><hr><h2>Provedor de IA</h2></td></tr>
                    <tr valign="top">
                        <th scope="row">Provedor Ativo</th>
                        <td>
                            <select name="tenet_ai_provider">
                                <option value="openai" <?php selected( get_option('tenet_ai_provider'), 'openai' ); ?>>OpenAI</option>
                                <option value="gemini" <?php selected( get_option('tenet_ai_provider'), 'gemini' ); ?>>Google Gemini</option>
                                <option value="openrouter" <?php selected( get_option('tenet_ai_provider'), 'openrouter' ); ?>>OpenRouter</option>
                            </select>
                        </td>
                    </tr>

                    <!-- OpenAI Settings -->
                    <tr><td colspan="2"><h3>OpenAI</h3></td></tr>
                    <tr valign="top">
                        <th scope="row">OpenAI API Key</th>
                        <td><input type="password" name="tenet_openai_key" value="<?php echo esc_attr( get_option('tenet_openai_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Modelo OpenAI</th>
                        <td><input type="text" name="tenet_openai_model" value="<?php echo esc_attr( get_option('tenet_openai_model', 'gpt-4o') ); ?>" class="regular-text" placeholder="Ex: gpt-4o" /></td>
                    </tr>

                    <!-- Gemini Settings -->
                    <tr><td colspan="2"><h3>Google Gemini</h3></td></tr>
                    <tr valign="top">
                        <th scope="row">Gemini API Key</th>
                        <td><input type="password" name="tenet_gemini_key" value="<?php echo esc_attr( get_option('tenet_gemini_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Modelo Gemini</th>
                        <td><input type="text" name="tenet_gemini_model" value="<?php echo esc_attr( get_option('tenet_gemini_model', 'gemini-1.5-pro') ); ?>" class="regular-text" placeholder="Ex: gemini-1.5-pro" /></td>
                    </tr>

                    <!-- OpenRouter Settings -->
                    <tr><td colspan="2"><h3>OpenRouter</h3></td></tr>
                    <tr valign="top">
                        <th scope="row">OpenRouter API Key</th>
                        <td><input type="password" name="tenet_openrouter_key" value="<?php echo esc_attr( get_option('tenet_openrouter_key') ); ?>" class="regular-text" /></td>
                    </tr>
                    <tr valign="top">
                        <th scope="row">Modelo OpenRouter</th>
                        <td><input type="text" name="tenet_openrouter_model" value="<?php echo esc_attr( get_option('tenet_openrouter_model') ); ?>" class="regular-text" placeholder="Ex: anthropic/claude-3-opus" /></td>
                    </tr>

                    <!-- Default Content Settings -->
                    <tr><td colspan="2"><hr><h2>Padrões de Conteúdo</h2></td></tr>
                    <tr valign="top">
                        <th scope="row">Tom de Voz Padrão</th>
                        <td>
                            <input type="text" name="tenet_default_tone" value="<?php echo esc_attr( get_option('tenet_default_tone', 'Técnico') ); ?>" class="regular-text" placeholder="Ex: Sarcástico e ácido" />
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
                            <input name="tone" type="text" id="tone" class="regular-text" value="<?php echo esc_attr( $default_tone ); ?>" placeholder="Ex: Sarcástico, Poético, Técnico...">
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
