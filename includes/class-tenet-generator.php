<?php

class Tenet_Generator {

    private $openai_key;
    private $pixabay_key;
    private $post_status;

    public function __construct() {
        $this->openai_key = get_option( 'tenet_openai_key' );
        $this->pixabay_key = get_option( 'tenet_pixabay_key' );
        $this->post_status = get_option( 'tenet_post_status', 'draft' );
    }

    public function generate_content( $topic, $tone, $audience, $instructions ) {
        if ( empty( $this->openai_key ) ) {
            throw new Exception( 'OpenAI API Key não configurada.' );
        }

        // 1. Memory Module
        $recent_titles = $this->get_recent_post_titles( 50 );
        $memory_string = implode( ', ', $recent_titles );

        // 2. Prompt Engineering
        $system_prompt = "Você é o 'Tenet', um redator especialista.
        NÃO repita tópicos ou ângulos já abordados nestes títulos passados: [$memory_string].
        Sua resposta DEVE ser estritamente um JSON válido.";

        $user_prompt = "Escreva um artigo sobre '$topic'.
        Tom de voz: $tone.
        Público Alvo: $audience.
        Instruções Extras: $instructions.

        A estrutura do JSON deve ser:
        {
            \"title\": \"Título Otimizado SEO\",
            \"content\": \"Conteúdo em HTML (h2, p, ul, strong, sem tag html ou body)\",
            \"excerpt\": \"Resumo cativante\",
            \"meta_description\": \"Descrição para SEO\",
            \"tags\": \"tag1, tag2, tag3\",
            \"pixabay_search_query\": \"Termo de busca em inglês para a imagem\"
        }";

        $ai_data = $this->call_openai( $system_prompt, $user_prompt );

        // 3. Visual Module & Publication
        $image_id = 0;
        if ( ! empty( $this->pixabay_key ) && ! empty( $ai_data['pixabay_search_query'] ) ) {
            try {
                $image_id = $this->handle_image_download( $ai_data['pixabay_search_query'] );
            } catch ( Exception $e ) {
                // Log error but continue with post creation
                error_log( 'Tenet Pixabay Error: ' . $e->getMessage() );
            }
        }

        return $this->create_post_in_wp( $ai_data, $image_id );
    }

    private function get_recent_post_titles( $limit ) {
        global $wpdb;

        // Optimization: Use direct SQL to fetch only titles in one query.
        // This avoids the N+1 problem of fetching IDs then looping with get_the_title(),
        // and avoids the memory overhead of fetching full post objects with get_posts().
        $titles = $wpdb->get_col( $wpdb->prepare( "
            SELECT post_title
            FROM {$wpdb->posts}
            WHERE post_status = 'publish'
            AND post_type = 'post'
            ORDER BY post_date DESC
            LIMIT %d
        ", $limit ) );

        return $titles;
    }

    private function call_openai( $system_prompt, $user_prompt ) {
        $url = 'https://api.openai.com/v1/chat/completions';

        $body = array(
            'model' => 'gpt-4o',
            'messages' => array(
                array( 'role' => 'system', 'content' => $system_prompt ),
                array( 'role' => 'user', 'content' => $user_prompt ),
            ),
            'response_format' => array( 'type' => 'json_object' ),
            'temperature' => 0.7,
        );

        $args = array(
            'body'        => json_encode( $body ),
            'headers'     => array(
                'Authorization' => 'Bearer ' . $this->openai_key,
                'Content-Type'  => 'application/json',
            ),
            'timeout'     => 60,
        );

        $response = wp_remote_post( $url, $args );

        if ( is_wp_error( $response ) ) {
            throw new Exception( 'Erro na OpenAI: ' . $response->get_error_message() );
        }

        $code = wp_remote_retrieve_response_code( $response );
        if ( $code !== 200 ) {
            $body_err = wp_remote_retrieve_body( $response );
            throw new Exception( 'Erro OpenAI (' . $code . '): ' . $body_err );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        $content_json_str = $data['choices'][0]['message']['content'];
        $content_data = json_decode( $content_json_str, true );

        if ( json_last_error() !== JSON_ERROR_NONE ) {
             throw new Exception( 'Falha ao decodificar JSON da IA.' );
        }

        return $content_data;
    }

    private function handle_image_download( $query ) {
        $url = 'https://pixabay.com/api/?key=' . $this->pixabay_key . '&q=' . urlencode( $query ) . '&image_type=photo&orientation=horizontal';

        $response = wp_remote_get( $url );
        if ( is_wp_error( $response ) ) {
            throw new Exception( 'Erro Pixabay: ' . $response->get_error_message() );
        }

        $body = wp_remote_retrieve_body( $response );
        $data = json_decode( $body, true );

        if ( empty( $data['hits'] ) ) {
            throw new Exception( 'Nenhuma imagem encontrada no Pixabay.' );
        }

        // Get highest resolution available or largeImageURL
        $image_url = $data['hits'][0]['largeImageURL'];

        return $this->sideload_image( $image_url, $query );
    }

    private function sideload_image( $url, $desc ) {
        // Need to require these files if not available
        require_once( ABSPATH . 'wp-admin/includes/media.php' );
        require_once( ABSPATH . 'wp-admin/includes/file.php' );
        require_once( ABSPATH . 'wp-admin/includes/image.php' );

        // Download and handle
        $id = media_sideload_image( $url, 0, $desc, 'id' );

        if ( is_wp_error( $id ) ) {
            throw new Exception( 'Erro ao baixar imagem: ' . $id->get_error_message() );
        }

        return $id;
    }

    private function create_post_in_wp( $data, $image_id ) {
        $post_arr = array(
            'post_title'   => sanitize_text_field( $data['title'] ),
            'post_content' => wp_kses_post( $data['content'] ),
            'post_excerpt' => sanitize_text_field( $data['excerpt'] ),
            'post_status'  => $this->post_status,
            'post_type'    => 'post',
            'tags_input'   => sanitize_text_field( $data['tags'] ),
        );

        $post_id = wp_insert_post( $post_arr );

        if ( is_wp_error( $post_id ) ) {
            throw new Exception( 'Erro ao criar post: ' . $post_id->get_error_message() );
        }

        // Set Featured Image
        if ( $image_id > 0 ) {
            set_post_thumbnail( $post_id, $image_id );
        }

        // Update Yoast/RankMath meta description
        if ( ! empty( $data['meta_description'] ) ) {
            update_post_meta( $post_id, '_yoast_wpseo_metadesc', sanitize_text_field( $data['meta_description'] ) );
            update_post_meta( $post_id, 'rank_math_description', sanitize_text_field( $data['meta_description'] ) );
        }

        return $post_id;
    }
}
