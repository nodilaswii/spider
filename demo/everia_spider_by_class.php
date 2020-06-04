<?php
require '../core/webapp_connect.php';
define('PATH_DATA', '../../SPIDER_DATA/data/');

$config = [
    'name' => 'everia',
    'domain' => 'https://everia.club/',
    'header' => [
        'Accept-Encoding' => '',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36',
        'referer' => 'http://everia.club/'
    ],
    'scan_url' => [
        'https://everia.club/',
        'https://everia.club/category/aidol/',
        'https://everia.club/category/gravure/',
        'https://everia.club/category/magazine/',
        'https://everia.club/category/thailand/',
    ]
];
$uncollectedUrl = $config['scan_url'];

$conn = new webapp_connect($config['domain']);
$conn->headers($config['header']);

foreach ($uncollectedUrl as $uKey => $sUrl) {
    unset($uncollectedUrl[$uKey]);
    while (empty($conn->request('GET', parse_url($sUrl)['path']))) {
        echo "获取列表页信息失败 5秒 之后重试！\n";
        sleep(5);
        $conn->reconnect(2);
    }
    $cookies = $conn->cookies;
    $listHtml = $conn->bufferdata();

    $albumXpath = [
        'albumUrl' => '//div[@class="post-thumbnail"]/a/@href',
        'listUrl' => '//div[@class = "nav-previous"]/a/@href',
    ];//列表页提取规则（xpath）
    $album = get_collect_contents($albumXpath, $listHtml);
    if (!empty($album['listurl'])) $uncollectedUrl[] = $album['listUrl']; //新爬取到的列表页地址加入到待爬取列表中

//处理每一个专辑页
    $conn->cookies($cookies);

    $albumDir = PATH_DATA . $config['name'] . DIRECTORY_SEPARATOR;
//循环处理列表页中的每一部图集
    foreach ($album['albumUrl'] as $eachAlbum) {
        //检测专辑是否已处理；
        $albumName = md5(basename($eachAlbum));
        $filePath = $albumDir . $albumName . DIRECTORY_SEPARATOR;
        if (file_exists($filePath . 'info.txt')) continue;//info.txt已存在就采集下一个图集
        if (!is_dir($filePath)) mkdir($filePath);
        $urlInfo = parse_url($eachAlbum);
        //检测连接是否断开
        while (empty($conn->request('GET', $urlInfo['path']))) {
            echo "获取详情页信息失败5秒之后重试！\n";
            sleep(5);
            $conn->reconnect(2);
        }
        $albumHtml = $conn->bufferdata();

        $albumXpath = [
            'name' => '//h1[@class="entry-title"]',
            'tag' => '//a[@rel="category tag"]',
            'imageUrls' => '//noscript/img/@src',
        ];
        $detailContents = (get_collect_contents($albumXpath, $albumHtml));
        if (strtolower($detailContents['tag']) == 'chinese') continue;
        $imageUrls = $detailContents['imageUrls'];
        $imageUrls = array_map(function ($a) {
            $patterns = [
                '#https:\/\/.*blogspot\.com\/#i',
                '#\?.*$#'
            ];
            $replacement = [
                'https://1.bp.blogspot.com/'
            ];
            return preg_replace($patterns, $replacement, $a);
        }, $imageUrls);
        $ref_img = new webapp_connect('https://1.bp.blogspot.com/');
        $ref_img->headers($config['header']);
        echo "_______________________start_____________________\n";
        echo "___正在下载{$detailContents['name']}____\n";
        $imageSavePath = null;
        foreach ($imageUrls as $index => $link) {
            $urlParas = parse_url($link);
            $fileName = basename($link);
            while (empty($ref_img->request('GET', $urlParas['path']))) {
                sleep(2);
                $ref_img->reconnect(2);
            }
            $ref_img->bufferdump($filePath . $fileName);
            $imageSavePath[] = $albumName . DIRECTORY_SEPARATOR . $fileName;
            echo 'everia' . DIRECTORY_SEPARATOR . $albumName . DIRECTORY_SEPARATOR . $fileName, "\n";
        }
        echo "_______________________end_____________________\n";
        $data = [
            'name' => $detailContents['name'],
            'url' => $eachAlbum,
            'image' => $imageSavePath,
            'tag' => $detailContents['tag']
        ];
        file_put_contents($filePath . 'info.txt', json_encode($data, JSON_UNESCAPED_UNICODE));

    }
}

/**
 * 传入一个domdocument类，返回所有链接
 * @param $xpath
 * @param $html
 * @return mixed
 */
function get_content_xpath($xpath, $html)
{
    $dom = new DOMDocument;
    @$dom->loadHTML($html);
    $xpathContent = new DOMXPath($dom);
    $nodeList = $xpathContent->evaluate($xpath);
    if (!$nodeList) return false;
    $content = [];
    foreach ($nodeList as $node) {
        $content[] = $node->nodeValue;
    }
    if (count($content) === 1) return reset($content);
    return $content;
}

/**
 * @param array $xpathList xpath提取列表
 * @param $html string 提取目标
 * @return array 返回提取到的链接
 */
function get_collect_contents(array $xpathList, $html)
{
    $contents = [];
    foreach ($xpathList as $key => $xpathRules) {
        $contents[$key] = get_content_xpath($xpathRules, $html) ?? '';
    }
    return $contents;
}
