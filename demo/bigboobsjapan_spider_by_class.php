<?php
require '../core/webapp_connect.php';
define('PATH_DATA', '../../SPIDER_DATA/data/');

$config = [
    'name' => 'bigboobsjapan',
    'header' => [
        'Accept-Encoding' => '',
//        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.97 Safari/537.36',
    ],
];
//读取最后一次爬取的链接

if (file_exists($last_url_path = PATH_DATA . $config['name'] . DIRECTORY_SEPARATOR . 'last_url') && $nas = file($last_url_path, FILE_SKIP_EMPTY_LINES)) {
    $nextPage = reset($nas);
} else {
    $nextPage = '/';
}

$conn = new webapp_connect('http://www.bigboobsjapan.com/');
$conn->headers($config['header']);
do {
//    echo "\n", '正在获取以下列表页: ' . $nextPage, "\n";
    while (count($res = $conn->request('GET', $nextPage)) <= 1) {
//        echo "\n正在重连，请等待\n";
        sleep(4);
        $conn->reconnect(4);
    }
    $content = $conn->bufferdata();
    $dom = new DOMDocument();
    $dom->loadHTML($content, LIBXML_NOWARNING | LIBXML_NOERROR);
    $listXml = simplexml_import_dom($dom);
    $contentByXpath = $listXml->xpath('//a[@class="entry-featured-img-link"]');
    if (!is_array($contentByXpath)) continue;
    foreach ($contentByXpath as $eachAlbum) {
//        echo "\n正在访问:\n", (string)$eachAlbum['href'], "\n";
        //get every album all detail informations
        $urlParas = parse_url($eachAlbum['href']);
        $albumName = md5($urlParas['path']);
        $savePath = PATH_DATA . $config['name'] . DIRECTORY_SEPARATOR . $albumName . DIRECTORY_SEPARATOR;
        if (file_exists($savePath . 'info.txt')) continue;

        while (count($bol = $conn->request('GET', $urlParas['path'])) <= 1) {
//            echo "等待4秒后重连！\n";
            sleep(1);
            $conn->reconnect(4);
        }
        $dom->loadHTML($conn->bufferdata(), LIBXML_NOWARNING | LIBXML_NOERROR);
        $detailXml = simplexml_import_dom($dom);
        $xmlElement = $detailXml->xpath('//a[@itemprop="contentURL"]');
        if (!empty($xmlElement)) {
            if (!is_dir($savePath)) mkdir($savePath);
            $diff = $detailXml->xpath('//div[@class= "entry-byline"]');
            $description = is_array($diff) ? $diff[0] : '';
        } else {
            echo "\n";
            continue;
        }
        //Download all images on this page


        $count = 0;
        $imagePath = [];
//        echo "\n_________________START_________________\n";
        foreach ($xmlElement as $element) {
            $imageUrl = (string)$element['href'];
            while (count($res = $conn->request('GET', parse_url($imageUrl)['path'])) <= 1) {
//                echo "等待4秒后重连！\n";
                sleep(4);
                if ($conn->reconnect(4)) {
                    $res = $conn->request('GET', parse_url($imageUrl)['path']);
                    break;
                }
            }
            $conn->bufferdump($savePath . basename($imageUrl));
            $imagePath[] = $albumName . DIRECTORY_SEPARATOR . basename($imageUrl);
            if (filesize($savePath . basename($imageUrl)) === 0) {
                rmdir($savePath);
                echo "\n图片下载失败，退出此次下载,并删除该文件夹 {$albumName}\n";
                break 2;
            }
//            echo $albumName . DIRECTORY_SEPARATOR . basename($imageUrl), "\n";
            ++$count;
        }
        echo "\n___END IMGCOUNT: ( {$count} )_____\n";

        $ablumInfo = [
            'name' => (string)$detailXml->xpath('//h1')[0] ?? '',
            'url' => (string)$eachAlbum['href'] ?? '',
            'upload_time' => (string)$description->div[0]->time ?? '',
            'model' => (string)$description->div[1]->a ?? '',
            'tags' => (string)$description->div[2]->a ?? '',
            'image' => $imagePath
        ];

        file_put_contents($savePath . 'info.txt', json_encode($ablumInfo, JSON_UNESCAPED_UNICODE));
    }
    $nextPage = (($lk = $listXml->xpath('//a[@class = "next page-numbers"]')) ? parse_url($lk[0]['href'])['path'] : NULL);
    file_put_contents($last_url_path, $nextPage);
    echo "\n____页码是{$nextPage}_______\n";
} while ($nextPage);
sleep(60);
echo "\n-------程序即将结束------------\n";

function remove_directory($dir)
{
    if ($handle = opendir("$dir")) {
        while (false !== ($item = readdir($handle))) {
            if ($item != "." && $item != "..") {
                if (is_dir("$dir/$item")) {
                    remove_directory("$dir/$item");
                } else {
                    unlink("$dir/$item");
                    echo " removing $dir/$item<br>\n";
                }
            }
        }
        closedir($handle);
        rmdir($dir);
        echo "removing $dir<br>\n";
    }
}




