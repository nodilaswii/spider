<?php
require '../core/webapp_connect.php';
const PATH_DATA = '../../SPIDER_DATA/data1/';
$getimg = new webapp_connect('https://1.bp.blogspot.com/');
//$getimg->disconnect();
$conn = new webapp_connect('https://everia.club/');
$conn->headers(['Accept-Encoding' => '']);
$dom = new DOMDocument;
$nextpage = '/';
do
{
    while(empty($conn->request('GET', $nextpage)))
    {
        echo "page get error\n";
        sleep(4);
        $conn->reconnect(4);
    }
    $dom->loadHTML($conn->bufferdata(), LIBXML_NOWARNING | LIBXML_NOERROR);
    $xml = simplexml_import_dom($dom);
    foreach ($xml->xpath('//a[@rel="bookmark"]') as $url)
    {
        $urlinfo = parse_url((string)$url['href']);
        $dirname = md5($urlinfo['path']);
        while(empty($conn->request('GET', $urlinfo['path'])))
        {
            echo "detail get error\n";
            sleep(4);
            $conn->reconnect(4);
        }
        $dom->loadHTML($conn->bufferdata(), LIBXML_NOWARNING | LIBXML_NOERROR);
        $xmla = simplexml_import_dom($dom);
        if (is_dir($dirsave = PATH_DATA . $dirname . '/'))
        {
            echo "file {$dirsave} exists!\n";
            continue;
        }
        mkdir($dirsave);
        foreach ($xmla->xpath('//noscript/img') as $img)
        {
            $imgstr = parse_url((string)$img['src'])['path'];
            $imgsrc = substr($imgstr, strpos($imgstr, '/', 1));
            echo "request({$imgsrc})...\n";
            while (empty($getimg->request('GET', $imgsrc)))
            {
                echo "image get error\n";
                sleep(4);
                $getimg->reconnect(4);
            }
            $getimg->bufferdump($dirsave . basename($imgsrc));
        }
    }
    sleep(2);
    if ($nextpage = ($pp = $xml->xpath('//div[@class="nav-previous"]/a')) && ($ppinfo = parse_url($pp[0]['href'])) ? $ppinfo['path'] : NULL)
    {
        echo "{$nextpage}\n";
    }
} while($nextpage);
sleep(600);
echo "done\n";