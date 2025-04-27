<?php
// Parent theme template
echo "Parent Theme Template";
if (isset($title)) {
    echo " - Title: " . $title;
}
if (isset($data) && is_object($data) && isset($data->price)) {
    echo " - Price: " . $data->price;
}
?>