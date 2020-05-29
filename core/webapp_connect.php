<?php
class webapp_connect
{
	public $errors = [];
	private $cookies = [], $headers = [
		'Host' => '*',
		'Connection' => 'keep-alive',
		'User-Agent' => 'WebApp/Connect',
		'Accept' => '*/*',
		//'Accept-Encoding' => 'gzip, deflate',
		'Accept-Language' => 'en'
	], $length = 0, $buffer, $remote, $stream;
	function __construct(string $remote)
	{
		if ($this->buffer = fopen('php://memory', 'w+'))
		{
			$this->headers['Host'] = parse_url($this->remote = $remote)['host'];
			$this->reconnect(ini_get('default_socket_timeout'));
		}
	}
	function __get(string $name)
	{
		switch ($name)
		{
			//case 'contents':		return $this->length && rewind($this->buffer) ? fread($this->buffer, $this->length) : '';
			// case 'json':			return is_array($data = json_decode($this->contents, TRUE)) ? $data : [];
			// case 'cookie':			return json_encode($this->cookies, JSON_UNESCAPED_UNICODE);
			case 'metadata':		return stream_get_meta_data($this->stream);
			// case 'remote_name':		return stream_socket_get_name($this->stream, TRUE);
			// case 'local_name':		return stream_socket_get_name($this->stream, FALSE);
			// case 'is_lockable':		return stream_supports_lock($this->stream);
			// case 'is_local':		return stream_is_local($this->stream);
			// case 'is_tty':			return stream_isatty($this->stream);
		}
	}
	//重连
	function reconnect(int $timeout):bool
	{
		if ($this->stream = @stream_socket_client($this->remote, $erron, $error, $timeout, STREAM_CLIENT_CONNECT, stream_context_create(['ssl' => [
			'verify_peer' => FALSE,
			'verify_peer_name' => FALSE,
			'allow_self_signed' => TRUE]]))) {
			return TRUE;
		}
		$this->errors[] = $error;
		return FALSE;
	}
	//断开
	function disconnect():bool
	{
		return stream_socket_shutdown($this->stream, STREAM_SHUT_WR);
	}
	//模式（请使用默认阻塞模式，别问为什么，除非你知道在做什么）
	function blocking(bool $mode):bool
	{
		return stream_set_blocking($this->stream, $mode);
	}
	//超时
	function timeout(int $seconds):bool
	{
		return stream_set_timeout($this->stream, $seconds);
	}
	//缓冲区重写
	private function rewind():bool
	{
		$this->length = 0;
		return rewind($this->buffer);
	}
	//缓冲区追加
	private function append(int $length):void
	{
		$this->readinto($this->buffer, $length);
		$this->length = ftell($this->buffer);
	}
	//缓冲区内容
	function bufferdata():string
	{
		rewind($this->buffer);
		return stream_get_contents($this->buffer, $this->length);
	}
	//缓冲区内容入流
	function bufferinto($stream):bool
	{
		return rewind($this->buffer) && stream_copy_to_stream($this->buffer, $stream, $this->length) === $this->length;
	}
	//缓冲区转储文件
	function bufferdump(string $filename):bool
	{
		if ($file = fopen($filename, 'wb'))
		{
			$complete = $this->bufferinto($file);
			fclose($file);
			return $end;
		}
		return FALSE;
	}
	//读取（注意：不一定会返回足够长度的数据，要获取指定长度请用 contents 方法）
	function read(int $length):string
	{
		return fread($this->stream, $length);
	}
	//读取一行
	function readline(int $length = 65535, string $ending = "\r\n"):string
	{
		return stream_get_line($this->stream, $length, $ending);
	}
	//读取剩余内容
	// function contents(int $length = -1):string
	// {
	// 	return stream_get_contents($this->stream, $length);
	// }
	//读取入流
	function readinto($stream, int $length = -1):int
	{
		return stream_copy_to_stream($this->stream, $stream, $length);
	}
	//发送
	function send(string $data):bool
	{
		return fwrite($this->stream, $data) === strlen($data);
	}
	//HTTP
	private function multipart(string $contents, string $filename, $data, string $name = NULL):void
	{
		//get_debug_type
		switch (TRUE)
		{
			case is_array($data):
				foreach ($data as $key => $value)
				{
					$this->multipart($contents, $filename, $value, $name === NULL ? $key : "{$name}[{$key}]");
				}
				return;
			case is_scalar($data):
				fwrite($this->buffer, sprintf($contents, $name));
				fwrite($this->buffer, $data);
				fwrite($this->buffer, "\r\n");
				return;
			// case ($data instanceof self):
			// 	fwrite($this->buffer, sprintf($filename, $name, __CLASS__));
			// 	$data->copyto($this->buffer);
			// 	fwrite($this->buffer, "\r\n");
			// 	return;
			// case is_resource($data) && get_resource_type($data) === 'stream':
			// 	fwrite($this->buffer, sprintf($filename, $name, basename(stream_get_meta_data($data)['uri'])));
			// 	stream_copy_to_stream($data, $this->buffer);
			// 	fwrite($this->buffer, "\r\n");
			// 	return;
		}
	}
	function request(string $method, string $url, $data = NULL, bool $multipart = FALSE):array
	{
		$headers = ["{$method} {$url} HTTP/1.1"];
		foreach ($this->headers as $name => $value)
		{
			$headers[] = "{$name}: {$value}";
		}
		if ($this->rewind() && $data !== NULL)
		{
			if ($multipart)
			{
				$boundary = uniqid('----WebAppFormBoundarys');
				$contents = join("\r\n", [$boundary, 'Content-Disposition: form-data; name="%s"', "\r\n"]);
				$filename = substr($contents, 0, -4) . "; filename=\"%s\"\r\nContent-Type: application/octet-stream\r\n\r\n";
				$headers[] = "Content-Type: multipart/form-data; boundary={$boundary}";
				$this->multipart("--{$contents}", "--{$filename}", $data);
				fwrite($this->buffer, "--{$boundary}--");
			}
			else
			{
				//get_debug_type
				switch (TRUE)
				{
					case is_array($data):
						$headers[] = 'Content-Type: application/x-www-form-urlencoded';
						fwrite($this->buffer, http_build_query($data));
						break;
					case is_scalar($data):
						fwrite($this->buffer, $data);
						break;
					// case ($data instanceof self):
					// 	$data->copyto($this->buffer);
					// 	break;
					// case is_resource($data) && get_resource_type($data) === 'stream':
					// 	stream_copy_to_stream($data, $this->buffer);
					// 	break;
				}
			}
			if ($this->length = ftell($this->buffer))
			{
				$headers[] = "Content-Length: {$this->length}";
			}
		}
		if ($this->cookies)
		{
			$headers[] = 'Cookie: '. http_build_query($this->cookies, NULL, '; ');
		}
		if ($this->send(join($headers[] = "\r\n", $headers)) && ($this->length ? $this->bufferinto($this->stream) : TRUE))
		{
			$responses = [$this->readline()];
			while ($header = $this->readline())
			{
				if ($offset = strpos($header, ': '))
				{
					$key = ucwords(substr($header, 0, $offset), '-');
					$value = substr($header, $offset + 2);
					if ($key !== 'Set-Cookie')
					{
						$responses[$key] = $value;
						continue;
					}
					if (preg_match('/^([^=]+)=([^;]+)(?:; expires=([^;]+))?/', $value, $cookies))
					{
						if (isset($cookies[3]) === FALSE || strtotime($cookies[3]) > time())
						{
							$this->cookies[$cookies[1]] = $cookies[2];
						}
					}
				}
			}
			$this->rewind();
			switch ($responses['Content-Encoding'] ?? NULL)
			{
				case 'gzip':
					$encoding = stream_filter_append($this->buffer, 'zlib.inflate', STREAM_FILTER_WRITE, ['window' => 30, 'memory' => 9]);
					break;
				case 'deflate':
					$encoding = stream_filter_append($this->buffer, 'zlib.inflate', STREAM_FILTER_WRITE);
					break;
				default:
					$encoding = NULL;
			}
			if (array_key_exists('Content-Length', $responses))
			{
				if ($length = intval($responses['Content-Length']))
				{
					$this->append($length);
				}
			}
			else
			{
				if (array_key_exists('Transfer-Encoding', $responses) && $responses['Transfer-Encoding'] === 'chunked')
				{
					while ($length = hexdec($this->readline(6)))
					{
						$this->append($length);
						$this->readline(2);
					}
					$this->readline(2);
				}
			}
			if (is_resource($encoding))
			{
				stream_filter_remove($encoding);
			}
			return $responses;
		}
		return [];
	}
	/*
	WebSocket
	Frame format:
	0                   1                   2                   3
	0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1 2 3 4 5 6 7 8 9 0 1
	+-+-+-+-+-------+-+-------------+-------------------------------+
	|F|R|R|R| opcode|M| Payload len |    Extended payload length    |
	|I|S|S|S|  (4)  |A|     (7)     |             (16/64)           |
	|N|V|V|V|       |S|             |   (if payload len==126/127)   |
	| |1|2|3|       |K|             |                               |
	+-+-+-+-+-------+-+-------------+ - - - - - - - - - - - - - - - +
	|     Extended payload length continued, if payload len == 127  |
	+ - - - - - - - - - - - - - - - +-------------------------------+
	|                               |Masking-key, if MASK set to 1  |
	+-------------------------------+-------------------------------+
	| Masking-key (continued)       |          Payload Data         |
	+-------------------------------- - - - - - - - - - - - - - - - +
	:                     Payload Data continued ...                :
	+ - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - +
	|                     Payload Data continued ...                |
	+---------------------------------------------------------------+
	*/
	// function websocket(&$response = NULL):bool
	// {
	// 	return $this->headers([
	// 			'Upgrade' => 'websocket',
	// 			'Connection' => 'Upgrade',
	// 			'Sec-WebSocket-Version' => 13,
	// 			'Sec-WebSocket-Key' => base64_encode(random_bytes(16))])->requests('GET', $this->url)
	// 		&& $this->response($response) === 101
	// 		&& isset($response['headers']['Sec-WebSocket-Accept'])
	// 		&& base64_encode(sha1($this->headers['Sec-WebSocket-Key']
	// 			.'258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE)) === $response['headers']['Sec-WebSocket-Accept'];
	// }
	// function packfhi(int $length, int $opcode = 1, bool $fin = TRUE, int $rsv = 0, string $mask = NULL):string
	// {
	// 	$format = 'CC';
	// 	$inputs = [$fin << 7 | ($rsv & 0x07 << 4) | ($opcode & 0x0f)];
	// 	if ($length < 126)
	// 	{
	// 		$inputs[] = $length;
	// 	}
	// 	else
	// 	{
	// 		if ($length < 65536)
	// 		{
	// 			$format .= 'n';
	// 			$inputs[] = 126;
	// 		}
	// 		else
	// 		{
	// 			$format .= 'J';
	// 			$inputs[] = 127;
	// 		}
	// 		$inputs[] = $length;
	// 	}
	// 	if (strlen($mask) > 3)
	// 	{
	// 		$format .= 'a4';
	// 		$inputs[] = $mask;
	// 	}
	// 	return pack($format, ...$inputs);
	// }
	// function readfhi():array
	// {
	// 	['a0' => $a0, 'a1' => $a1] = unpack('Ca0/Ca1', $this->contents(2));
	// 	$hi = [
	// 		'fin' => $a0 >> 7,
	// 		'rsv' => $a0 >> 4 & 0x07,
	// 		'opcode' => $a0 & 0x0f,
	// 		'mask' => [],
	// 		'length' => $a1 & 0x7f
	// 	];
	// 	if ($hi['length'] > 125)
	// 	{
	// 		$hi['length'] = bindec($this->contents($hi['length'] === 126 ? 2 : 8));
	// 	}
	// 	if ($a1 >> 7)
	// 	{
	// 		$hi['mask'] = array_values(unpack('Ca0/Ca1/Ca2/Ca3', $this->contents(4)));
	// 	}
	// 	return $hi;
	// }
	// function sendframe(string $content, int $opcode = 1, bool $fin = TRUE, int $rsv = 0, string $mask = NULL):bool
	// {
	// 	return $this->send($this->packfhi(strlen($content), $opcode, $fin, $rsv, $mask) . $content);
	// }
	// function readframe(&$hi = NULL):string
	// {
	// 	$hi = $this->readfhi();
	// 	$contents = $this->contents($hi['length']);
	// 	if ($mask = $hi['mask'])
	// 	{
	// 		$length = strlen($contents);
	// 		for ($i = 0; $i < $length; ++$i)
	// 		{
	// 			$contents[$i] = chr(ord($contents[$i]) ^ $mask[$i % 4]);
	// 		}
	// 	}
	// 	return $contents;
	// }
}