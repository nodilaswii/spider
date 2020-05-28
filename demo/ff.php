<?php

require 'webapp_api.php';
$count = 0;
$conn = new webapp_api('https://saohu19.com/');

for ($i = 4600; $i < 4610; ++$i)
{
	++$count;
    $conn->request('GET', '/v1/api/apiGetPhotoData?id='.$i, $res); //请求数据地址

    $data = json_decode($raw = $conn->contents, TRUE);
    $list = json_decode($data['data']['content'], TRUE);
	
	mkdir('c:/saohu/'.$i);

    file_put_contents('c:/saohu/'.$i.'/info.txt', $raw);

    foreach (json_decode($data['data']['content'], TRUE) as $file)
    {
        $names = explode('/', $file);
        if (isset($newconn) === FALSE)
        {
            $newconn = new webapp_api(join('/', array_slice($names, 0, 3)));
        }
        $newconn->request('GET', '/'.  join('/', array_slice($names, 3)), $res);
        echo $file, $newconn->saveto('c:/saohu/'.$i.'/'. end($names)) ? ' OK' : ' NO', "\n"; //保存文件路径
    }
	unset($newconn);
	if ($count > 50)
	{
		$conn = new webapp_api('https://saohu19.com/');
	}
}