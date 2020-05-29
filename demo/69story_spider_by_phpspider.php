<?php

use phpspider\core\log;
use phpspider\core\phpspider;
use phpspider\core\queue;

require_once __DIR__ . '/../autoloader.php';

/* Do NOT delete this comment */
/* 不要删除这段注释 */

$configs = [
    //开发时参数
    /*    'log_show' => true,
        'log_type' => 'warn,error,info',
        'interval' => 2000,
        'tasknum' => 1,
        'save_running_state' => false,*/
//正式爬取参数
    'log_show' => true,
    'log_type' => 'warn,error',
    'save_running_state' => true,
    'tasknum' => 1,


    'name' => '69story',
    'max_depth' => 0,
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
        '69story.com'
    ],
    'scan_urls' => [
        'https://69story.com',
        "https://69story.com/wife",
        "https://69story.com/school",
        "https://69story.com/family",
        "https://69story.com/bdsm-abuse",
        "https://69story.com/sex-in-the-city",

    ],
    'list_url_regexes' => [
        'https:\/\/69story\.com\/page\/\d+$',
        'https:\/\/69story\.com\/(wife|school|family|bdsm\-abuse|sex\-in\-the\-city)\/page\/\d+$',
        'https:\/\/69story\.com\/topic\/(.*?)(\/page\/\d+)?$',
    ],
    'content_url_regexes' => [
        "https:\/\/69story\.com\/article\/\d+[-_-].*?\.html$",
    ],
    'fields' => [
        // 内容页图片信息
        [
            'name' => 'name',
            'selector' => '//h1[@class="entry-title"]/text()',
            'required' => true,
        ],
        [
            'name' => 'tag',
            'selector' => '//div[@class="entry-meta"]/ul/li/a[@rel="category tag"]/text()',
            'required' => true,
        ],

        [
            'name' => 'content',
            'selector' => '//div[@class="entry-content"]',
            'required' => true,
        ],
    ],
];


$spider = new phpspider($configs);

$spider->on_start = function ($phpspider) use ($configs) {
    if (!is_dir($path = PHP_DATA . DIRECTORY_SEPARATOR . $configs['name'])) mkdir($path, 0777, true);
};

$spider->on_judge_url = function ($link, $phpspider) {
    $folderName = explode('-', basename($link['url']));
    $folderName = reset($folderName);
    $dataRootDirectory = PATH_DATA . DIRECTORY_SEPARATOR . '69story' . DIRECTORY_SEPARATOR;

    $filePosition = $dataRootDirectory . $folderName . DIRECTORY_SEPARATOR . 'info.txt';
    if (file_exists($filePosition) && filesize($filePosition) !== 0) {
        return true;
    }
};

$spider->on_content_page = function ($page, $content, $phpspider) {
    return false;
};

$spider->on_extract_page = function ($page, $content) {
    $cnn = explode('-', basename($page['url']));
    $cnn = reset($cnn);

    $dataRootDirectory = PATH_DATA. DIRECTORY_SEPARATOR . '69story' . DIRECTORY_SEPARATOR;
    if (!is_dir($dataRootDirectory . $cnn)) mkdir($dataRootDirectory . $cnn);
    $filePosition = $dataRootDirectory . $cnn . DIRECTORY_SEPARATOR . 'info.txt';
    if (file_exists($filePosition) && filesize($filePosition) !== 0) return $content;
    file_put_contents($filePosition, json_encode($content, JSON_UNESCAPED_UNICODE));
    return $content;
};


$spider->start();