<?php

require "../core/webapp_connect.php";

$requestQuery = parse_url($url);
var_dump(pathinfo('https://julyporn.com/stories/all/free?page=1'));
exit;

// $conn = new webapp_connect('https://julyporn.com/');

// $conn->request(join('?', [$requestQuery['path']]));