<?php

class Test_Tenet_Category extends WP_UnitTestCase {

    public function setUp() {
        parent::setUp();
        // Instantiate the admin class or generator if needed
        $this->generator = new Tenet_Generator();
    }

    public function test_post_creation_with_category() {
        // Create a category
        $category_id = $this->factory->category->create( array( 'name' => 'Test Category' ) );

        // Mock data similar to what OpenAI returns
        $data = array(
            'title' => 'Test Post Title',
            'content' => '<p>Test content.</p>',
            'excerpt' => 'Test excerpt',
            'meta_description' => 'Meta desc',
            'tags' => 'tag1, tag2',
            'pixabay_search_query' => 'test'
        );

        // Reflection to access private method create_post_in_wp
        $reflection = new ReflectionClass( 'Tenet_Generator' );
        $method = $reflection->getMethod( 'create_post_in_wp' );
        $method->setAccessible( true );

        // Call the method with category_id
        $post_id = $method->invokeArgs( $this->generator, array( $data, 0, $category_id ) );

        // Verify post exists
        $this->assertGreaterThan( 0, $post_id );

        // Verify category
        $categories = wp_get_post_categories( $post_id );
        $this->assertContains( $category_id, $categories );
    }

    public function test_post_creation_without_category() {
        // Mock data
        $data = array(
            'title' => 'Test Post No Cat',
            'content' => '<p>Test content.</p>',
            'excerpt' => 'Test excerpt',
            'meta_description' => 'Meta desc',
            'tags' => 'tag1, tag2',
            'pixabay_search_query' => 'test'
        );

        // Reflection
        $reflection = new ReflectionClass( 'Tenet_Generator' );
        $method = $reflection->getMethod( 'create_post_in_wp' );
        $method->setAccessible( true );

        // Call with 0
        $post_id = $method->invokeArgs( $this->generator, array( $data, 0, 0 ) );

        // Default category is usually 1 (Uncategorized)
        $categories = wp_get_post_categories( $post_id );
        $this->assertNotEmpty( $categories );
        // The exact default depends on WP install, but usually it's term_id 1.
    }
}
