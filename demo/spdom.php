<?php
require '../core/webapp_connect.php';
<<<<<<< HEAD

$conn = new webapp_connect('https://69story.com/');
$conn->headers([
    'Accept-Encoding' => '',
    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36',
    'referer' => 'https://69story.com/'
]);

$conn->request('GET', '/');

$dom = new DOMDocument;
$dom->loadHTML($data = $conn->bufferdata(), LIBXML_NOWARNING | LIBXML_NOERROR);
//$dom->loadHTML(file_get_contents('./spdom.txt'), LIBXML_NOWARNING | LIBXML_NOERROR);

$xml = simplexml_import_dom($dom);

foreach ($xml->xpath('//a[@rel="bookmark"]') as $url)
{
    $urlinfo = parse_url((string)$url['href']);
    $conn->request('GET', $urlinfo['path']);

    $dom->loadHTML($conn->bufferdata(), LIBXML_NOWARNING | LIBXML_NOERROR);
    $xml = simplexml_import_dom($dom);

    echo $urlinfo['path'],"\n";
    if ($r = $xml->xpath('//div[@class="entry-content"]'))
    {
        $fname = md5($urlinfo['path']);
        file_put_contents("./{$fname}.txt", substr($r[0]->asXML(), 27, -6) );
    }
}
=======
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
>>>>>>> 6eacc53949bac8ebea4f340b1e3d42748826336e
