<?php
require '../core/webapp_connect.php';

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