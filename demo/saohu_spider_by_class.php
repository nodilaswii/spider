<?php
//引入weapp_api类
require '../core/webapp_api.php';
define('PATH_DATA', '../../SPIDER_DATA/data/');

$dataRootDirectory = PATH_DATA . 'saohu/';
$imageType = ['photo', 'cartoon'];

foreach ($imageType as $type) {
    if (!is_dir($dataRootDirectory . $type)) {
        echo "文件夹不存在，程序自动退出\n";
        exit;
    }

    $fList = scandir($dataRootDirectory . $type);
    sort($fList, SORT_NUMERIC);
    foreach ($fList as $index => $id) {
        if ($id === '.' || $id === '..') continue;
        $savePath = $type . DIRECTORY_SEPARATOR . $id;
        $fdir = $dataRootDirectory . $savePath;

        if (file_exists("{$fdir}/info.txt") === FALSE || filesize("{$fdir}/info.txt") === 0) {
            $conn = new webapp_api('https://saohu19.com');
            $data = $conn->request('GET', "/v1/api/apiGetPhotoData?id={$id}")->contents; //请求数据地址
            $conn->disconnect();
            $conn = NULL;

            $apiContent = json_decode($data, TRUE);
            $info = $apiContent['data'];
            $list = json_decode($info['content'], TRUE);

            $urls = parse_url($info['cover']);
            $host = new webapp_api("{$urls['scheme']}://{$urls['host']}");
            $host->request('GET', $urls['path']);

            $count = intval($host->saveto("{$fdir}/" . basename($urls['path'])));
            $info['cover'] = $savePath . DIRECTORY_SEPARATOR . basename($urls['path']);

            echo "-----------START:{$id}---------------\n";

            $imagePath = [];
            foreach ($list as $key => $url) {
                $urls = parse_url($url);
                $host->request('GET', $urls['path']);
                while (error_get_last()) {
                    error_clear_last();
                    if ($host->reconnect()) {
                        $host->request('GET', $urls['path']);
                        break;
                    }
                    sleep(10);
                }

                if ($host->saveto("{$fdir}/" . basename($urls['path']))) {
                    $imagePath[$key] = $savePath . DIRECTORY_SEPARATOR . basename($urls['path']);
                    ++$count;
                    echo $urls['path'], " OK\n";
                } else {
                    echo $urls['path'], " NO\n";
                }
            }
            $info['content'] = $imagePath;
            if (count($list) + 1 === $count) {
                echo "-------------OK:{$id}---------------\n";
                file_put_contents("{$fdir}/info.txt", json_encode($info, JSON_UNESCAPED_UNICODE));
            } else {
                echo "-------------NO:{$id}---------------\n";
            }
        }
    }
    exit;
}

//47爬虫实例
/*foreach (scandir('../data/saohu/cartoon') as $id) {
    if ($id === '.' || $id === '..') continue;
    $fdir = "../data/saohu/cartoon/{$id}";

    if (file_exists("{$fdir}/info.txt") === FALSE || filesize("{$fdir}/info.txt") === 0) {
        $conn = new webapp_api('https://saohu19.com');
        $data = $conn->request('GET', "/v1/api/apiGetPhotoData?id={$id}")->contents; //请求数据地址
        $conn->disconnect();
        $conn = NULL;

        $info = json_decode($data, TRUE);
        $list = json_decode($info['data']['content'], TRUE);

        $urls = parse_url($info['data']['cover']);
        $host = new webapp_api("{$urls['scheme']}://{$urls['host']}");
        $host->request('GET', $urls['path']);

        $count = intval($host->saveto("{$fdir}/" . basename($urls['path'])));

        echo "-----------START:{$id}---------------\n";

        foreach ($list as $url) {
            $urls = parse_url($url);
            $host->request('GET', $urls['path']);
            while (error_get_last()) {
                error_clear_last();
                if ($host->reconnect()) {
                    $host->request('GET', $urls['path']);
                    break;
                }
                sleep(10);
            }

            if ($host->saveto("{$fdir}/" . basename($urls['path']))) {
                ++$count;
                echo $urls['path'], " OK\n";
            } else {
                echo $urls['path'], " NO\n";
            }
        }
        if (count($list) + 1 === $count) {
            echo "-------------OK:{$id}---------------\n";
            file_put_contents("{$fdir}/info.txt", $data);
        } else {
            echo "-------------NO:{$id}---------------\n";
        }
    }
}*/


//遍历出所有要爬取的数据

/*$domain = 'https://saohu19.com/v1/api/apiGetPhotoData?id=1376';
$apiAddress = [
    '?page=1&classify=1&pagesize=2332',
    '?page=1&classify=2&pagesize=2063'
];

if (is_dir('../data/saohu/cartoon') === FALSE) {
    mkdir('../data/saohu/cartoon');
    $conn = new webapp_api('https://saohu19.com/');
    $data = $conn->request('GET', '/v1/api/apiFetchPhotoData?page=1&classify=1&pagesize=2332')->contents;
    $conn = NULL;
    $list = json_decode($data, TRUE)['data']['list'];
    foreach ($list as $f) {
        $ss = "../data/saohu/cartoon/{$f['id']}";
        echo $ss, mkdir($ss) ? ' OK' : ' NO', "\n";
    }
}
if (is_dir('../data/saohu/photo') === FALSE) {
    mkdir('../data/saohu/photo');
    $conn = new webapp_api('https://saohu19.com/');
    $data = $conn->request('GET', '/v1/api/apiFetchPhotoData?page=1&classify=2&pagesize=2063')->contents;
    $conn = NULL;
    $list = json_decode($data, TRUE)['data']['list'];
    foreach ($list as $f) {
        $ss = "../data/saohu/photo/{$f['id']}";
        echo $ss, mkdir($ss) ? ' OK' : ' NO', "\n";
    }
}*/
//nova实现的爬虫
/*foreach ($apiAddress as $apiParam) {
    //建立一个socket连接
    $api = new webapp_api($domain);
    $url = '/v1/api/apiFetchPhotoData' . $apiParam;
    $api->request('GET', $url, $res);
    $apiContents = $api->contents;
    $apiContents = json_decode($apiContents, true);
    $atlasLists = $apiContents['data']['list'];

    //获取每一部漫画的图片与文件信息
    $count = 0;
    $dataRootDirectory = PATH_DATA . '/saohu/';
    if (!is_dir($dataRootDirectory)) mkdir($dataRootDirectory, 0777, true);
    chdir($dataRootDirectory);

    foreach ($atlasLists as $eachAtlas) {
        ++$count;

        $api->request('GET', '/v1/api/apiGetPhotoData?id=' . $eachAtlas['id'], $res); //请求数据地址

        $data = json_decode($raw = $api->contents, TRUE);
        $info = $data['data'];
        $list = json_decode($data['data']['content'], TRUE);

        if (!is_dir($eachAtlas['id'])) mkdir($eachAtlas['id']);
        $imageUrls = json_decode($data['data']['content'], TRUE);
        $imageUrls['cover'] = $info['cover'];

        foreach ($imageUrls as $key => $file) {
            $names = explode('/', $file);
            if (isset($newconn) === FALSE || $names[2] != 'saohu38.com') {
                $newconn = new webapp_api(join('/', array_slice($names, 0, 3)));
            }
            $newconn->request('GET', '/' . join('/', array_slice($names, 3)), $res);
            $newconn->saveto($eachAtlas['id'] . '/' . end($names)); //保存文件路径
            if ($key === 'cover') {
                $info['cover'] = 'saohu/' . $eachAtlas['id'] . '/' . end($names);
            }
            $imageUrls[$key] = 'saohu/' . $eachAtlas['id'] . '/' . end($names);
        }
        $info['content'] = $imageUrls;
        file_put_contents($eachAtlas['id'] . DIRECTORY_SEPARATOR . 'info.txt', json_encode($info));
        if ($count > 50) {
            unset($newconn);
            $conn = new webapp_api('https://saohu19.com/');
            $count = 0;
        }
        $pauseTime = random_int(3, 50);
        echo("请等待$pauseTime");
        sleep($pauseTime);
    }
    unset($api);
}*/






