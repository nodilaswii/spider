<?php

require "../core/webapp_connect.php";
require "../library/cls_query.php";
$conn = new webapp_connect('http://www.baidu.com');

$requestQuery = parse_url($url);
var_dump(pathinfo('https://julyporn.com/stories/all/free?page=1'));

$requestQuery->request('GET', "/stories/all/free?page=1");

exit;
