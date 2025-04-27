<?php
// This template uses the $loader variable that is automatically available
echo "Loader Variable Template";

// Use the $loader variable to load another template
if (isset($loader)) {
    $loader->get_template_part('nested-child', null, ['nested_only' => 'Loader Variable']);
}
?>