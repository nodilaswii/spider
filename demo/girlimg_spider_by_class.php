<?php
// $domain = girlimg.epio.app
require '../core/webapp_connect.php';

print_r(json_decode(file_get_contents('/mnt/e/PHP_CLI/SPIDER_DATA/data/bigboobsjapan/d84d93ebf5bd4627440f0efd293f9310/info.txt'))['url']);
exit;

define('PATH_DATA', '../../SPIDER_DATA/data/');


$config = [
    'name' => 'girlimg',
    'domain' => 'https://girlimg.epio.app/',
    'header' => [
        'Accept-Encoding' => '',
        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/83.0.4103.61 Safari/537.36',
        'referer' => 'https://girlimg.epio.app/'
    ],
];

//TODO 获取并提取每一页数据

$url = 'https://girlimg.epio.app/api/articles';

$conn = new webapp_connect('https://girlimg.epio.app/');

$ref = $conn->request('GET', '/article');
var_dump($ref);
echo $conn->bufferdata();

exit;









function get_next_list_link($page){
    $parseUrl = (parse_url($url = 'https://girlimg.epio.app/api/articles?lang=en-us&filter=%7B%22where%22%3A%7B%22tag%22%3A%22all%22%2C%22lang%22%3A%22en-us%22%7D%2C%22limit%22%3A20%2C%22skip%22%3A0%7D'));
    parse_str($parseUrl['query'], $result);
    $queryarray = json_decode($result['filter'], true);
    $queryarray['skip'] = ($page-1) * 20;

    $result['filter'] = json_encode($queryarray);
    $a = http_build_query($result);
    print_r($a);
}

exit;

$uncollectedUrl = $config['scan_url'];
$ref_img = new webapp_connect('https://1.bp.blogspot.com/');

$conn = new webapp_connect($config['domain']);
$conn->headers($config['header']);

while (!empty(count($uncollectedUrl))) {
    print_r($uncollectedUrl);
    $sUrl = array_pop($uncollectedUrl);
    while (empty($conn->request('GET', parse_url($sUrl)['path']))) {
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
    foreach ($album['albumUrl'] as $eachAlbum) {
        //检测专辑是否已处理；
        $albumName = md5(basename($eachAlbum));
        $filePath = PATH_DATA . $config['name'] . DIRECTORY_SEPARATOR . $albumName . DIRECTORY_SEPARATOR;
        if (file_exists($filePath . 'info.txt')) continue;//info.txt已存在就采集下一个图集
        if (!is_dir($filePath)) mkdir($filePath);

        //检测连接是否断开
        while (empty($conn->request('GET', parse_url($eachAlbum)['path']))) {
            echo "获取详情页信息失败5秒之后重试！\n";
            sleep(5);
            $conn->reconnect(2);
        }

        $albumXpath = [
            'name' => '//h1[@class="entry-title"]',
            'tag' => '//a[@rel="category tag"]',
            'imageUrls' => '//noscript/img/@src',
        ];

        $detailContents = get_collect_contents($albumXpath, $conn->bufferdata());
        if (strtolower($detailContents['tag']) == 'chinese') {
            echo "带水印的中文album，跳过\n";
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
        echo "_______________________start_____________________\n";
        echo "___正在下载{$detailContents['name']}____\n";
        $imageSavePath = null;
        foreach ($imageUrls as $index => $link) {
            while (empty($ref_img->request('GET', parse_url($link)['path']))) {
                sleep(2);
                $ref_img->reconnect(2);
            }
            $ref_img->bufferdump($filePath . $fileName = basename($link));
            $imageSavePath[] = $albumName . DIRECTORY_SEPARATOR . $fileName;
            echo $config['name'] . DIRECTORY_SEPARATOR . $albumName . DIRECTORY_SEPARATOR . $fileName, "\n";
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
