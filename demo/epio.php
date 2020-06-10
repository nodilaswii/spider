<?php
require 'webflock/core/webapp.php';

$skip = 0;


$host = new webapp_connect("https://girlimg.epio.app");
$host->headers([
	'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36',
	'Referer' => 'https://girlimg.epio.app/article'
]);
do
{
	while (empty($json = $host->content('GET', "/api/articles?lang=en-us&filter=%7B%22where%22%3A%7B%22tag%22%3A%22all%22%2C%22lang%22%3A%22en-us%22%7D%2C%22limit%22%3A20%2C%22skip%22%3A{$skip}%7D")))
	{
		sleep(4);
		$host->reconnect(4);
	}


	print_r($json);




} while(0);