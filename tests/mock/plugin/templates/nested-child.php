<?php
// Nested child template
echo " [Nested Child Template";
if (isset($title)) {
    echo " - Title: " . $title;
}
if (isset($data) && is_object($data) && isset($data->price)) {
    echo " - Price: " . $data->price;
}
if (isset($nested_only)) {
    echo " - Nested Only: " . $nested_only;
}
echo "]";
?>