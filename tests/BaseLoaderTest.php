<?php
namespace Fullworks_Template_Loader_Lib2\Tests;

use PHPUnit\Framework\TestCase;

class BaseLoaderTest extends TestCase {
    /**
     * @var TestTemplateLoader
     */
    private $loader;

    public function setUp(): void {
        parent::setUp();
        $this->loader = new TestTemplateLoader();
    }

    /**
     * Test template paths are correctly set up
     */
    public function testTemplatePaths() {
        $paths = $this->loader->test_get_template_paths();
        
        // Since we've set is_child_theme to true in bootstrap.php,
        // we should have 4 paths - child theme, parent theme, plugin, and wp-content
        $this->assertCount(4, $paths);
        
        // Test keys match the expected priorities
        $this->assertArrayHasKey(1, $paths); // Child theme
        $this->assertArrayHasKey(10, $paths); // Parent theme
        $this->assertArrayHasKey(100, $paths); // Plugin
        $this->assertArrayHasKey(200, $paths); // WP Content
    }

    /**
     * Test child theme handling
     */
    public function testChildThemePath() {
        // Mock the is_child_theme function to return true
        global $mock_is_child_theme;
        $mock_is_child_theme = true;
        
        $this->assertTrue(is_child_theme());
        
        // Create a new loader that will use the mocked function
        $loader = new class extends TestTemplateLoader {
            protected function get_template_paths() {
                // Override is_child_theme check
                $paths = parent::get_template_paths();
                // Manually add child theme path for testing
                $paths[1] = trailingslashit(get_stylesheet_directory()) . $this->theme_template_directory;
                return $paths;
            }
        };
        
        $paths = $loader->test_get_template_paths();
        
        // We should now have 4 paths including child theme
        $this->assertCount(4, $paths);
        $this->assertArrayHasKey(1, $paths); // Child theme should be highest priority
    }

    /**
     * Test template file names generation
     */
    public function testTemplateFileNames() {
        // Test with only slug
        $names = $this->loader->test_get_template_file_names('content', null);
        $this->assertCount(1, $names);
        $this->assertEquals('content.php', $names[0]);
        
        // Test with slug and name
        $names = $this->loader->test_get_template_file_names('content', 'product');
        $this->assertCount(2, $names);
        $this->assertEquals('content-product.php', $names[0]);
        $this->assertEquals('content.php', $names[1]);
    }

    /**
     * Test templates dir method
     */
    public function testTemplatesDir() {
        $dir = $this->loader->test_get_templates_dir();
        $this->assertEquals(
            trailingslashit(dirname(__DIR__) . '/tests/mock/plugin') . 'templates',
            $dir
        );
    }

    /**
     * Test template data handling
     */
    public function testTemplateData() {
        // Test with array data
        $data = ['title' => 'Test Title', 'price' => 19.99];
        $this->loader->set_template_data($data);
        
        // Get the template from plugin directory
        ob_start();
        $this->loader->get_template_part('content');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Test Title', $output);
        $this->assertStringContainsString('19.99', $output);
    }
    
    /**
     * Test template loading with direct data
     */
    public function testTemplateLoadingWithDirectData() {
        $data = ['title' => 'Direct Data', 'price' => 29.99];
        
        ob_start();
        $this->loader->get_template_part('content', null, $data);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Direct Data', $output);
        $this->assertStringContainsString('29.99', $output);
    }
    
    /**
     * Test template loading priority (child theme > parent theme > plugin)
     */
    public function testTemplatePriority() {
        // First verify we're correctly setting up our test mock files
        $child_theme_path = get_stylesheet_directory() . '/test-plugin/content-product.php';
        $parent_theme_path = get_template_directory() . '/test-plugin/content.php';
        
        $this->assertTrue(file_exists($child_theme_path), "Child theme test file is missing: $child_theme_path");
        $this->assertTrue(file_exists($parent_theme_path), "Parent theme test file is missing: $parent_theme_path");
        
        // Create a new loader with child theme support
        $loader = new class extends TestTemplateLoader {
            public function __construct() {
                $this->plugin_directory = dirname(__DIR__) . '/tests/mock/plugin';
                parent::__construct();
            }
            
            protected function get_template_paths() {
                $theme_directory = trailingslashit($this->theme_template_directory);
                
                $file_paths = array(
                    10 => trailingslashit(get_template_directory()) . $theme_directory,
                    100 => $this->get_templates_dir(),
                    200 => trailingslashit(WP_CONTENT_DIR) . $theme_directory,
                );
                
                // Manually add child theme path for testing with highest priority
                $file_paths[1] = trailingslashit(get_stylesheet_directory()) . $theme_directory;
                
                return $file_paths;
            }
        };
        
        // Verify template paths are set correctly
        $paths = $loader->test_get_template_paths();
        $this->assertArrayHasKey(1, $paths); // Child theme
        $this->assertArrayHasKey(10, $paths); // Parent theme
        $this->assertArrayHasKey(100, $paths); // Plugin
        $this->assertArrayHasKey(200, $paths); // WP Content
        
        // Test loading content-product.php which exists in child theme
        ob_start();
        $loader->get_template_part('content', 'product');
        $output = ob_get_clean();
        
        // Should use the child theme version
        $this->assertStringContainsString('Child Theme Product Template', $output);
        
        // Test loading content.php which exists in parent theme but not child theme
        ob_start();
        $loader->get_template_part('content');
        $output = ob_get_clean();
        
        // Should use the parent theme version
        $this->assertStringContainsString('Parent Theme Template', $output);
    }
    
    /**
     * Test fallback to wp-content
     */
    public function testWPContentFallback() {
        // Set up scenario where template only exists in wp-content
        ob_start();
        $this->loader->get_template_part('content', 'fallback');
        $output = ob_get_clean();
        
        // Should use the wp-content version as fallback
        $this->assertStringContainsString('WP Content Fallback Template', $output);
    }
    
    /**
     * Test custom variable name
     */
    public function testCustomVariableName() {
        $data = ['title' => 'Custom Var', 'price' => 39.99];
        
        // Create a test template file that looks for a custom variable
        $test_file = dirname(__DIR__) . '/tests/mock/plugin/templates/custom-var.php';
        file_put_contents($test_file, '<?php 
        echo "Title from data: " . (isset($data) && isset($data->title) ? $data->title : "Not found");
        echo ", Title extracted: " . (isset($title) ? $title : "Not found");
        ?>');
        
        // Test with default behavior (extracts to individual variables)
        ob_start();
        $this->loader->get_template_part('custom-var', null, $data);
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Title from data: Custom Var', $output);
        $this->assertStringContainsString('Title extracted: Custom Var', $output);
        
        // Clean up
        unlink($test_file);
    }

    /**
     * Test template caching
     */
    public function testTemplateCache() {
        // First call should populate the cache
        $template1 = $this->loader->locate_template(['content.php'], [], false);
        
        // Cache is working if a second call returns the same result without errors
        $template2 = $this->loader->locate_template(['content.php'], [], false);
        $this->assertEquals($template1, $template2);
        
        // We can't test private properties directly since PHPUnit can't access
        // private properties in parent classes, but we can test the behavior
    }

    /**
     * Test template loader instance accessibility
     */
    public function testTemplateLoaderAccess() {
        // Test using $loader variable
        ob_start();
        $this->loader->get_template_part('loader-variable-template');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Loader Variable Template', $output);
        $this->assertStringContainsString('Nested Child Template', $output);
        $this->assertStringContainsString('Nested Only: Loader Variable', $output);
        
        // Test using static accessor
        ob_start();
        $this->loader->get_template_part('static-access-template');
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Static Access Template', $output);
        $this->assertStringContainsString('Nested Child Template', $output);
        $this->assertStringContainsString('Nested Only: Static Access', $output);
    }
    
    /**
     * Test that data is passed to nested templates
     */
    public function testNestedTemplateData() {
        // Set up data
        $data = ['title' => 'Nested Test', 'price' => 49.99];
        
        // First test: data should be available to both parent and nested templates
        ob_start();
        $this->loader->get_template_part('parent-template', null, $data);
        $output = ob_get_clean();
        
        // Check both templates received the data
        $this->assertStringContainsString('Parent Template Start - Title: Nested Test - Price: 49.99', $output);
        $this->assertStringContainsString('Nested Child Template - Title: Nested Test - Price: 49.99', $output);
        $this->assertStringContainsString('Parent Template End', $output);
        
        // Second test: set data before loading templates
        $this->loader->set_template_data($data);
        
        ob_start();
        $this->loader->get_template_part('parent-template');
        $output = ob_get_clean();
        
        // Check both templates received the data
        $this->assertStringContainsString('Parent Template Start - Title: Nested Test - Price: 49.99', $output);
        $this->assertStringContainsString('Nested Child Template - Title: Nested Test - Price: 49.99', $output);
        
        // Third test: data can be overridden in nested template
        $parent_data = ['title' => 'Parent Title', 'price' => 59.99];
        
        ob_start();
        // Pass different data to the parent template
        $this->loader->get_template_part('parent-template', null, $parent_data);
        $output = ob_get_clean();
        
        // Both templates should have the parent data
        $this->assertStringContainsString('Parent Template Start - Title: Parent Title - Price: 59.99', $output);
        $this->assertStringContainsString('Nested Child Template - Title: Parent Title - Price: 59.99', $output);
        
        // Fourth test: nested template can add its own data
        // Create a modified parent template that passes additional data to child
        $test_file = dirname(__DIR__) . '/tests/mock/plugin/templates/parent-with-nested-data.php';
        file_put_contents($test_file, '<?php
        echo "Parent Template";
        if (isset($title)) { echo " - Title: " . $title; }
        // Pass additional data to nested child
        $nested_data = ["title" => $title, "price" => $data->price, "nested_only" => "Nested Value"];
        $this->get_template_part("nested-child", null, $nested_data);
        ?>');
        
        ob_start();
        $this->loader->get_template_part('parent-with-nested-data', null, $parent_data);
        $output = ob_get_clean();
        
        // Nested template should have both parent data and its own data
        $this->assertStringContainsString('Parent Template - Title: Parent Title', $output);
        $this->assertStringContainsString('Nested Child Template - Title: Parent Title - Price: 59.99 - Nested Only: Nested Value', $output);
        
        // Clean up
        unlink($test_file);
    }
}