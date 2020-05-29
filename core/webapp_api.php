<?php

class webapp_api_debug extends php_user_filter
{
    //注意：过滤流在内部读取时只能过滤一个队列，这是一个BUG？
    function filter($in, $out, &$consumed, $closing): int
    {
        echo "\r\n", $consumed === NULL
            ? '>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>>'
            : '<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<<',
        "\r\n";
        while ($bucket = stream_bucket_make_writeable($in)) {
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
            echo quoted_printable_encode($bucket->data);
        }
        return PSFS_PASS_ON;
    }
}

final class webapp_api
{
    public $url = '/', $errno, $error;
    private $hosts, $length = 0, $buffer, $context, $stream, $debug,
        $headers = [
        'Host' => 'localhost',
        'Connection' => 'keep-alive',
        'User-Agent' => 'WebApp/API',
        'Accept' => '*/*',
//		'Accept-Encoding' => 'gzip, deflate',
        'Accept-Language' => 'en'
    ], $cookies = [];

    function __construct(string $socket, array $context = [])
    {
        $url = parse_url($socket) + ['scheme' => 'tcp', 'host' => $this->headers['Host']];
        if (isset($url['path'])) {
            $this->url = $url['path'];
        }
        if (isset($url['query'])) {
            $this->url .= '?' . $url['query'];
        }
        $scheme = strtolower($url['scheme']);
        if (isset($url['port']) === FALSE) {
            switch ($scheme) {
                case 'http':
                    $url['port'] = 80;
                    break;
                case 'https':
                    $url['port'] = 443;
                    break;
                default:
                    $url['port'] = 0;
            }
        }
        if (preg_match('/^(http|ws)s?$/', $scheme)) {
            $this->headers['Host'] = $url['host'] . ':' . $url['port'];
        }
        $this->hosts = sprintf('%s://%s:%s',
            in_array($scheme, stream_get_transports(), TRUE) ? $scheme : (
            preg_match('/^(http|ws)s$/', $scheme) ? 'ssl' : 'tcp'
            ), $url['host'], $url['port']);
        $this->buffer = tmpfile();
        $this->context = stream_context_create(['ssl' => [
                'verify_peer' => FALSE,
                'verify_peer_name' => FALSE,
                'allow_self_signed' => TRUE
            ]] + $context);
        $this->reconnect();
    }

    function __get(string $name)
    {
        switch ($name) {
            case 'contents':
                return $this->length && rewind($this->buffer) ? fread($this->buffer, $this->length) : '';
            case 'json':
                return is_array($data = json_decode($this->contents, TRUE)) ? $data : [];
            case 'cookie':
                return json_encode($this->cookies, JSON_UNESCAPED_UNICODE);
            case 'metadata':
                return stream_get_meta_data($this->stream);
            case 'remote_name':
                return stream_socket_get_name($this->stream, TRUE);
            case 'local_name':
                return stream_socket_get_name($this->stream, FALSE);
            case 'is_lockable':
                return stream_supports_lock($this->stream);
            case 'is_local':
                return stream_is_local($this->stream);
            case 'is_tty':
                return stream_isatty($this->stream);
        }
    }

    //调试
    function debug(int $filter = STREAM_FILTER_WRITE/* STREAM_FILTER_ALL */): void
    {
        if (in_array('webapp_api_debug', stream_get_filters(), TRUE) === FALSE) {
            stream_filter_register('webapp_api_debug', 'webapp_api_debug');
        }
        if ($this->debug === NULL && $filter) {
            $this->debug = stream_filter_append($this->stream, 'webapp_api_debug', $filter);
        }
    }

    //重连
    function reconnect(): bool
    {
        return boolval($this->stream = stream_socket_client($this->hosts, $this->errno, $this->error,
            ini_get('default_socket_timeout'), STREAM_CLIENT_CONNECT, $this->context));
    }

    //断开
    function disconnect(): bool
    {
        return stream_socket_shutdown($this->stream, STREAM_SHUT_WR);
    }

    //模式
    function blocking(bool $mode): bool
    {
        return stream_set_blocking($this->stream, $mode);
    }

    //超时
    function timeout(int $seconds): bool
    {
        return stream_set_timeout($this->stream, $seconds);
    }

    //发送
    function send(string $data): bool
    {
        return $this->stream && fwrite($this->stream, $data) !== FALSE;
    }

    //读取（注意：不一定会返回足够长度的数据，要获取指定长度请用 contents 方法）
    function read(int $length): string
    {
        return fread($this->stream, $length);
    }

    //读取一行
    function line(int $length = 65535, string $ending = "\r\n"): string
    {
        return stream_get_line($this->stream, $length, $ending);
    }

    //推送流到流
    function push($stream, int $length = -1): int
    {
        return stream_copy_to_stream($this->stream, $stream, $length);
    }

    //下载流到文件
    function download(string $filename, int $length = -1): bool
    {
        if ($handle = fopen($filename, 'wb')) {
            $success = $this->push($handle, $length) === $length || $length === -1;
            fclose($handle);
            return $success;
        }
        return FALSE;
    }

    //读取流剩余内容
    function contents(int $length = -1): string
    {
        return stream_get_contents($this->stream, $length);
    }

    //输出缓冲区到文件
    function saveto(string $filename): bool
    {
        $success = 0;
        if ($this->length && rewind($this->buffer) && ($handle = fopen($filename, 'wb'))) {
            $success += stream_copy_to_stream($this->buffer, $handle, $this->length);
            fclose($handle);
        }
        return $success === $this->length;
    }

    //拷贝缓冲区到流
    function copyto($stream): bool
    {
        return is_resource($stream)
            && get_resource_type($stream) === 'stream'
            && stream_copy_to_stream($this->buffer, $stream, $this->length) === $this->length;
    }

    private function append(int $length): void
    {
        $this->push($this->buffer, $length);
        $this->length = ftell($this->buffer);
    }

    private function rewind(): void
    {
        rewind($this->buffer);
        $this->length = 0;
    }

    //HTTP
    function cookie(string $data): self
    {
        if (is_array($values = json_decode($data, TRUE))) {
            $this->cookies = $values + $this->cookies;
        } else {
            $this->cookies[0] = $data;
        }
        return $this;
    }

    function cookies(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->cookies[$key] = urlencode($value);
        }
        return $this;
    }

    function headers(array $values): self
    {
        foreach ($values as $key => $value) {
            $this->headers[$key] = $value;
        }
        return $this;
    }

    private function requests(string $method, string $url): bool
    {
        $requests = [sprintf('%s %s HTTP/1.1', $method, $url)];
        foreach ($this->headers as $key => $value) {
            $requests[] = $key . ': ' . $value;
        }
        if ($this->cookies) {
            $cookies = [];
            foreach ($this->cookies as $key => $value) {
                $cookies[] = $key === 0 ? $value : $key . '=' . $value;
            }
            $requests[] = 'Cookie: ' . join($cookies, '; ');
        }
        $requests[] = "\r\n";
        return $this->send(join("\r\n", $requests));
    }

    private function response(&$response = NULL): int
    {
        $response = [
            'code' => 500,
            'status' => $this->line(),
            'headers' => []
        ];
        if (preg_match('/^HTTP\/(?:0\.9|1\.[01]) (\d{3})/', $response['status'], $status)) {
            $response['code'] = intval($status[1]);
        }
        $headers = &$response['headers'];
        while ($header = $this->line()) {
            if ($offset = strpos($header, ': ')) {
                $key = ucwords(substr($header, 0, $offset), '-');
                $value = substr($header, $offset + 2);
                if ($key !== 'Set-Cookie') {
                    $headers[$key] = $value;
                    continue;
                }
                if (preg_match('/^([^=]+)=([^;]+)(?:; expires=([^;]+))?/', $value, $cookies)) {
                    if (isset($cookies[3]) === FALSE || strtotime($cookies[3]) > time()) {
                        $this->cookies[$cookies[1]] = $cookies[2];
                    }
                }
            }
        }
        $this->rewind();
        switch ($headers['Content-Encoding'] ?? NULL) {
            case 'gzip':
                $compress = stream_filter_append($this->buffer, 'zlib.inflate', STREAM_FILTER_WRITE, ['window' => 30]);
                break;
            case 'deflate':
                $compress = stream_filter_append($this->buffer, 'zlib.inflate', STREAM_FILTER_WRITE);
                break;
            default:
                $compress = NULL;
        }
        if (isset($headers['Content-Length'])) {
            if ($length = intval($headers['Content-Length'])) {
                $this->append($length);
            }
        } else {
            if (isset($headers['Transfer-Encoding']) && $headers['Transfer-Encoding'] === 'chunked') {
                while ($length = hexdec($this->line(6))) {
                    $this->append($length);
                    $this->line(2);
                }
                $this->line(2);
            }
        }
        if ($compress) {
            stream_filter_remove($compress);
        }
        if (isset($headers['Content-Type'])
            && preg_match('/^([a-z]+\/[0-9a-z]+(?:\.[0-9a-z]+)*)(?:[^=]+=([^\n]+))?/', $headers['Content-Type'], $types)) {
            $response['mime'] = $types[1];
            $response['encoding'] = $types[2] ?? 'latin1';
        }
        return $response['code'];
    }

    private function form_multipart(string $contents, string $filename, $data, string $name = NULL): void
    {
        switch (TRUE) {
            case is_array($data):
                foreach ($data as $key => $value) {
                    $this->form_multipart($contents, $filename, $value, $name === NULL ? $key : sprintf('%s[%s]', $name, $key));
                }
                return;
            case is_scalar($data):
                fwrite($this->buffer, sprintf($contents, $name));
                fwrite($this->buffer, $data);
                fwrite($this->buffer, "\r\n");
                return;
            case ($data instanceof self):
                fwrite($this->buffer, sprintf($filename, $name, __CLASS__));
                $data->copyto($this->buffer);
                fwrite($this->buffer, "\r\n");
                return;
            case is_resource($data) && get_resource_type($data) === 'stream':
                fwrite($this->buffer, sprintf($filename, $name, basename(stream_get_meta_data($data)['uri'])));
                stream_copy_to_stream($data, $this->buffer);
                fwrite($this->buffer, "\r\n");
                return;
        }
    }

    function form($data, bool $multipart = FALSE): self
    {
        $this->rewind();
        if ($multipart) {
            $boundary = uniqid('----WebAppFormBoundarys');
            $contents = join("\r\n", [$boundary, 'Content-Disposition: form-data; name="%s"', "\r\n"]);
            $filename = substr($contents, 0, -4) . "; filename=\"%s\"\r\nContent-Type: application/octet-stream\r\n\r\n";
            $this->headers['Content-Type'] = 'multipart/form-data; boundary=' . $boundary;
            $this->form_multipart('--' . $contents, '--' . $filename, $data);
            fwrite($this->buffer, '--' . $boundary . '--');
        } else {
            switch (TRUE) {
                case is_array($data):
                    $this->headers['Content-Type'] = 'application/x-www-form-urlencoded';
                    fwrite($this->buffer, http_build_query($data));
                    break;
                case is_scalar($data):
                    fwrite($this->buffer, $data);
                    break;
                case ($data instanceof self):
                    $data->copyto($this->buffer);
                    break;
                case is_resource($data) && get_resource_type($data) === 'stream':
                    stream_copy_to_stream($data, $this->buffer);
                    break;
            }
        }
        $this->length = ftell($this->buffer);
        if ($this->length && rewind($this->buffer)) {
            $this->headers['Content-Length'] = $this->length;
        }
        return $this;
    }

    function request(string $method, string $url, &$response = NULL): self
    {
        if ($this->requests($method, $url)) {
            (isset($this->headers['Content-Length'])
                ? stream_copy_to_stream($this->buffer, $this->stream, $this->length) === $this->length
                : TRUE) ? $this->response($response) : $this->rewind();
            unset($this->headers['Content-Length'], $this->headers['Content-Type']);
        }
        return $this;
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
    function websocket(&$response = NULL): bool
    {
        return $this->headers([
                'Upgrade' => 'websocket',
                'Connection' => 'Upgrade',
                'Sec-WebSocket-Version' => 13,
                'Sec-WebSocket-Key' => base64_encode(random_bytes(16))])->requests('GET', $this->url)
            && $this->response($response) === 101
            && isset($response['headers']['Sec-WebSocket-Accept'])
            && base64_encode(sha1($this->headers['Sec-WebSocket-Key']
                . '258EAFA5-E914-47DA-95CA-C5AB0DC85B11', TRUE)) === $response['headers']['Sec-WebSocket-Accept'];
    }

    function packfhi(int $length, int $opcode = 1, bool $fin = TRUE, int $rsv = 0, string $mask = NULL): string
    {
        $format = 'CC';
        $inputs = [$fin << 7 | ($rsv & 0x07 << 4) | ($opcode & 0x0f)];
        if ($length < 126) {
            $inputs[] = $length;
        } else {
            if ($length < 65536) {
                $format .= 'n';
                $inputs[] = 126;
            } else {
                $format .= 'J';
                $inputs[] = 127;
            }
            $inputs[] = $length;
        }
        if (strlen($mask) > 3) {
            $format .= 'a4';
            $inputs[] = $mask;
        }
        return pack($format, ...$inputs);
    }

    function readfhi(): array
    {
        ['a0' => $a0, 'a1' => $a1] = unpack('Ca0/Ca1', $this->contents(2));
        $hi = [
            'fin' => $a0 >> 7,
            'rsv' => $a0 >> 4 & 0x07,
            'opcode' => $a0 & 0x0f,
            'mask' => [],
            'length' => $a1 & 0x7f
        ];
        if ($hi['length'] > 125) {
            $hi['length'] = bindec($this->contents($hi['length'] === 126 ? 2 : 8));
        }
        if ($a1 >> 7) {
            $hi['mask'] = array_values(unpack('Ca0/Ca1/Ca2/Ca3', $this->contents(4)));
        }
        return $hi;
    }

    function sendframe(string $content, int $opcode = 1, bool $fin = TRUE, int $rsv = 0, string $mask = NULL): bool
    {
        return $this->send($this->packfhi(strlen($content), $opcode, $fin, $rsv, $mask) . $content);
    }

    function readframe(&$hi = NULL): string
    {
        $hi = $this->readfhi();
        $contents = $this->contents($hi['length']);
        if ($mask = $hi['mask']) {
            $length = strlen($contents);
            for ($i = 0; $i < $length; ++$i) {
                $contents[$i] = chr(ord($contents[$i]) ^ $mask[$i % 4]);
            }
        }
        return $contents;
    }
}