<?php
// Parent template that includes a nested child template
echo "Parent Template Start";
if (isset($title)) {
    echo " - Title: " . $title;
}
if (isset($data) && is_object($data) && isset($data->price)) {
    echo " - Price: " . $data->price;
}

// Include a nested template - this should still have access to the data
$this->get_template_part('nested-child');

echo " Parent Template End";
?>