<?php
require __DIR__ . '/../core/webapp_api.php';
require_once __DIR__ . '/../autoloader.php';

use  phpspider\core\requests;
use phpspider\core\selector;
use phpspider\core\log;

use phpspider\core\phpspider;

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = [
//开发时参数
        /*'log_show' => true,
        'log_type' => 'warn,error,info',
        'interval' => 2000,
        'tasknum' => 1,
        'save_running_state' => false,*/

//正式爬取参数
    'log_show' => false,
    'log_type' => 'warn,error',
    'save_running_state' => true,
    'tasknum' => 2,


    'name' => 'saohu',
    'max_depth' => 1,
    'max_try' => 10,
    'timeout' => 30,
    'user_agent' => [
        'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/70.0.3538.77 Safari/537.36',
        /*        'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML like Gecko) Chrome/44.0.2403.155 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2228.0 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.1 Safari/537.36',
                'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2227.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2226.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.4; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2225.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.3; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2225.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/41.0.2224.3 Safari/537.36',
                'Mozilla/5.0 (Windows NT 10.0) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/40.0.2214.93 Safari/537.36',
                'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2062.124 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.3; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 4.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/37.0.2049.0 Safari/537.36',
                'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.67 Safari/537.36',
                'Mozilla/5.0 (Windows NT 5.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.67 Safari/537.36',
                'Mozilla/5.0 (X11; OpenBSD i386) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/36.0.1985.125 Safari/537.36',
        */
    ],
    'client_ip' => [
        '191.94.217.239',
        '179.22.248.177',
        '2.177.146.53',
        '31.103.193.91',
        '110.72.23.203',
        '185.87.153.67',
        '59.156.12.48',
        '114.90.96.145',
        '132.225.201.115',
        '18.4.71.182',
        '255.38.226.6',
        '118.0.14.246',
        '83.126.1.141',
        '117.248.167.100',
        '101.213.54.76',
        '41.139.107.190',
        '185.6.162.52',
        '201.238.38.102',
        '49.193.183.198',
        '122.65.55.99',
        '51.219.117.25',
        '233.64.120.115',
    ],
    'db_config' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'root',
        'pass' => 'Aa1122334455',
        'name' => 'netflav',
    ],
    'queue_config' => [
        'host' => '127.0.0.1',
        'port' => 6379,
        'pass' => 'Aa1122334455',
        'db' => 8,
        'prefix' => 'phpspider',
        'timeout' => 30,
    ],
    'domains' => [
        'saohu19.com'
    ],
    'scan_urls' => [
        "https://saohu19.com/v1/api/apiFetchPhotoData?classify=1&page=1&pagesize=100000",
        "https://saohu19.com/v1/api/apiFetchPhotoData?classify=2&page=1&pagesize=100000",
    ],
    'list_url_regexes' => [
        "x",
        //"https:\/\/saohu19\.com\/v1\/api\/apiFetchPhotoData.*",
    ],
    'content_url_regexes' => [
        "https:\/\/saohu19\.com\/v1\/api\/apiGetPhotoData\?id=\d+$",
    ],
    'fields' => [
        // 内容页图片信息
        [
            'name' => 'videoInfo',
            'selector' => '/html/body',
            'required' => true,
        ],
    ],
];
//获取已爬列表
$collectedList = getListByFile('collectedURL.txt');

$spider = new phpspider($configs);
$spider->on_status_code = function ($status_code, $url, $content, $phpspider) {
    \phpspider\core\queue::set('sdfasdf','asdfasdfasdf');
    global $collectedList;
    if (!empty($collectedList)) {
        $isJump = array_search($url, $collectedList);
        if (is_int($isJump)) {
            log::warn("此链接已爬取 {$url}\n跳过");
            return false;
        }
        return $content;
    }

};

$spider->on_download_page = function ($page, $phpspider) {
    $isList = preg_match("#v1\/api\/apiFetchPhotoData\?#i", $page['url']) ?? 0;
    if (!empty($isList)) {
        $raw = json_decode($page['raw']);
        $lists = $raw->data->list;
        if (!empty($lists) && is_array($lists)) {
            foreach ($lists as $cartoon) {
                $cartoonId = $cartoon->id;
                $domain = "https://saohu19.com/v1/api/apiGetPhotoData?id={$cartoonId}";
                global $collectedList;
                if (!empty($collectedList)) {
                    $isJump = array_search($domain, $collectedList);
                    if (is_int($isJump)) {
                        log::warn("此链接已爬取 {$domain}\n跳过");
                    } else {
                        $phpspider->add_scan_url($domain);
                        log::info("已添加: " . $domain);
                    }
                }
            }
        }
    }

};

$spider->on_content_page = function ($page, $content, $phpspider) {
    $imagePath = '';
    if (!empty($content)) {
        $cartoonInfo = json_decode($content);
        //整部漫画信息
        $cartoonInfo = $cartoonInfo->data;
        $folderName = bin2hex(random_bytes(16));
        $singleFolderPath = checkAndCreateDir($folderName);

        $coverUrl = $cartoonInfo->cover;
        $cover = downImg($coverUrl, $singleFolderPath);

        $imgAddress = json_decode($cartoonInfo->content);
        $imagePath = downImg($imgAddress, $singleFolderPath);

        $cartoonInfo->cover = $cover;
        $cartoonInfo->content = $imagePath;

        //将漫画信息以json串写入文本文件中
        file_put_contents($singleFolderPath['absPath'] . 'information.txt', json_encode($cartoonInfo, JSON_UNESCAPED_UNICODE));
        writeListToFile($page['url']);
//        log::warn('已爬取：' . $page['url']);
        return false;

    }

};

$spider->start();

/**
 * @param $url array 封面图地址
 * @param $path string 上层目录
 * @return false|string
 */
function downImg($url, $folderName)
{
    if (empty($url)) {
        log::error('未提供图片下载地址！');
        return false;
    }
//    $folderName = checkAndCreateDir($folderName);
    if (is_array($url)) {
        foreach ($url as $key => $image_url) {
            $images[$key] = curlTool($image_url, $folderName);
        }
    } else {
        $images[] = fileRename(curlTool($url, $folderName));
    }
    return $images;


}

/**
 * 调用下载工具weget并返回文件保存地址
 * @param string $url 下载地址
 * @param string $path 存放目录
 * @return string 存放文件的相对路径
 */
function curlTool($url, $folderName)
{
    //文件名
    $imageInfo = pathinfo($url);

    //重新命新文件
    $file_name = $imageInfo['basename'];

    //文件存储的绝对路径 若目录不存在，递规创建

//    $path = checkDirAndCreate($folderName);

    $filePath = $folderName['absPath'] . $file_name;
    //下载文件
    exec("wget -T 180 -t 10 -b -q {$url} -O {$filePath}");

    return $folderName['relatePath'] . $file_name;
}

function checkAndCreateDir($path)
{
    $returnValue = [];
    global $configs;
    $relatePath = $configs['name'] . DIRECTORY_SEPARATOR . $path . DIRECTORY_SEPARATOR;
    $domTree = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $relatePath;
    if (!is_dir($domTree)) mkdir($domTree, 0777, true);
    $returnValue['absPath'] = $domTree;
    $returnValue['relatePath'] = $relatePath;
    return $returnValue;
}

function fileRename($filePath)
{
    chdir(__DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR);
    $replacement = 'cover$2';
    $newFilePath = preg_replace('/([^\/]+)(\..*?)$/i', $replacement, $filePath);
    rename($filePath, $newFilePath);
    return $newFilePath;
}

function saveInfoToFile($path, $info)
{
    $path = checkAndCreateDir($path);
    file_put_contents($path . 'information.txt', $info);
}

//TODO 文件读取列表
/**
 * @param $filePath
 * @return array|false
 */
function getListByFile($filePath)
{
    global $configs;
    //文件路径
    $fileStoragePath = __DIR__ . DIRECTORY_SEPARATOR . '..' . DIRECTORY_SEPARATOR . 'data' . DIRECTORY_SEPARATOR . $configs['name'] . DIRECTORY_SEPARATOR . $filePath;
    //从文本中读取内容到数组
    if (file_exists($fileStoragePath)) {
        $list = file($fileStoragePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (!empty($list) && is_array($list)) {
            return array_filter($list, function ($a) {
                if (empty($a)) {
                    return false;
                }
                return $a;
            });
        }
    }
    return [];
}

//TODO 将内容写入至文件中
/**
 * @param $content string|array 待写入内容
 * @param $filename string 文件名
 */
function writeListToFile($content, $fileName = 'collectedURL.txt')
{
    global $configs;
    file_put_contents(PATH_DATA . '/' . $configs['name'] . '/' . $fileName, $content . PHP_EOL, FILE_APPEND);
}