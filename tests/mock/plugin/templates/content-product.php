<?php
// Plugin specific template
echo "Plugin Product Template";
if (isset($title)) {
    echo " - Title: " . $title;
}
if (isset($data) && is_object($data) && isset($data->price)) {
    echo " - Price: " . $data->price;
}
?>