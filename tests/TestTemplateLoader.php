<?php
namespace Fullworks_Template_Loader_Lib2\Tests;

use Fullworks_Template_Loader_Lib\BaseLoader;

class TestTemplateLoader extends BaseLoader {
    /**
     * Prefix for filter names.
     *
     * @var string
     */
    protected $filter_prefix = 'test-plugin';

    /**
     * Directory name where custom templates for this plugin should be found in the theme.
     *
     * @var string
     */
    protected $theme_template_directory = 'test-plugin';

    /**
     * Reference to the root directory path of this plugin.
     *
     * @var string
     */
    protected $plugin_directory;

    /**
     * Expose protected methods for testing
     */
    public function __construct() {
        $this->plugin_directory = dirname(__DIR__) . '/tests/mock/plugin';
        parent::__construct();
    }

    public function test_get_template_paths() {
        return $this->get_template_paths();
    }
    
    public function test_get_template_file_names($slug, $name) {
        return $this->get_template_file_names($slug, $name);
    }
    
    public function test_get_templates_dir() {
        return $this->get_templates_dir();
    }
}