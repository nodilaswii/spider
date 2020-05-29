<?php
//引入weapp_api类
require '../core/webapp_api.php';

$domain = 'https://saohu19.com/';
//wait collect list url;
$apiAddress = [
    '?page=1&classify=1&pagesize=2332',
    '?page=1&classify=2&pagesize=2063'
];


foreach ($apiAddress as $apiParam) {
    //建立一个socket连接
    $api = new webapp_api($domain);
    $url = '/v1/api/apiFetchPhotoData' . $apiParam;
    $api->request('GET', $url, $res);
    $apiContents = $api->contents;
    $apiContents = json_decode($apiContents, true);
    $atlasLists = $apiContents['data']['list'];

    //获取每一部漫画的图片与文件信息
    $count = 0;
    $dataRootDirectory = '../data/saohu/';
    if (!file_exists($dataRootDirectory)) mkdir($dataRootDirectory, 0777, true);
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
}






