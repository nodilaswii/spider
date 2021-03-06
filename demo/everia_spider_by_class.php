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
        'https://everia.club/category/thailand/',
        'https://everia.club/category/aidol/',
        'https://everia.club/category/gravure/',
        'https://everia.club/category/magazine/',
    ]
];
$uncollectedUrl = $config['scan_url'];
$ref_img = new webapp_connect('https://1.bp.blogspot.com/');

$conn = new webapp_connect($config['domain']);
$conn->headers($config['header']);

while (!empty(count($uncollectedUrl))) {
    $sUrl = array_shift($uncollectedUrl);
    echo "\n", "列表页地址： ", $sUrl, "\n";
    while (count($connr = $conn->request('GET', parse_url($sUrl)['path'])) <= 1) {
        echo "获取列表页信息失败 5秒 之后重试！\n";
        sleep(5);
        $conn->reconnect(2);
    }
//    $cookies = $conn->cookies;
    $albumXpath = [
        'albumUrl' => '//div[@class="post-thumbnail"]/a/@href',
        'listUrl' => '//div[@class = "nav-previous"]/a/@href'
    ];//列表页提取规则（xpath）
    $album = get_collect_contents($albumXpath, $conn->bufferdata());
    if (!empty($album['listUrl'])) $uncollectedUrl[] = $album['listUrl']; //新爬取到的列表页地址加入到待爬取列表中

//    $conn->cookies($cookies);
//循环处理列表页中的每一部图集
    echo "\n\n";
    foreach ($album['albumUrl'] as $eachAlbum) {
        echo "\n", '正在采集以下图集', "\n", $eachAlbum, "\n";
        //检测专辑是否已处理；
        $albumName = md5(basename($eachAlbum));
        $filePath = PATH_DATA . $config['name'] . DIRECTORY_SEPARATOR . $albumName . DIRECTORY_SEPARATOR;
        if (file_exists($filePath . 'info.txt')) continue;//info.txt已存在就采集下一个图集
        if (!is_dir($filePath)) mkdir($filePath);

        //检测连接是否断开
        while (count($connres = $conn->request('GET', parse_url($eachAlbum)['path'])) <= 1) {
            echo "\n获取详情页信息失败5秒之后重试！\n";
            sleep(5);
            $conn->reconnect(2);
        }

        $albumXpath = [
            'name' => '//h1[@class="entry-title"]',
            'tag' => '//a[@rel="category tag"]',
            'imageUrls' => '//noscript/img/@src',
        ];

        $detailContents = get_collect_contents($albumXpath, $conn->bufferdata());
        if (is_string($tag = $detailContents['tag']) && strtolower($tag) == 'chinese') {
            echo "\n带水印的中文album，跳过\n";
            continue;
        } elseif (is_array($detailContents['tag']) && is_int(array_search('chinese', $tag))) {
            continue;
        }

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
        $ref_img->headers($config['header']);
        echo "\n_______________________start_____________________\n";
        echo "\n\n\n正在下载{$detailContents['name']}\n\n\n";

        $count = 0;
        $imageSavePath = [];
        foreach ($imageUrls as $index => $link) {
            while (count($ref = $ref_img->request('GET', parse_url($link)['path'])) <= 1) {
                sleep(2);
                $ref_img->reconnect(2);
            }
            $ref_img->bufferdump($filePath . $fileName = basename($link));
            $imageSavePath[] = $albumName . DIRECTORY_SEPARATOR . $fileName;
            if (filesize($filePath . $fileName = basename($link)) == 0) {
                rmdir($filePath);
                echo "\n图片下载失败，退出此次下载,并删除该文件夹 {$albumName}\n";
                break 2;
            }
//            echo $config['name'] . DIRECTORY_SEPARATOR . $albumName . DIRECTORY_SEPARATOR . $fileName, "\n";
            ++$count;
        }
        echo "\n\n_______________________end IMAGE ($count)_____________________\n\n";
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