<?php
// This template uses the static get_instance() method to access the template loader
use Fullworks_Template_Loader_Lib\BaseLoader;

echo "Static Access Template";

// Get the loader using the static method
$template_loader = BaseLoader::get_instance();

// Use the template loader to load another template
if ($template_loader) {
    $template_loader->get_template_part('nested-child', null, ['nested_only' => 'Static Access']);
}
?>