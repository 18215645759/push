<?php

namespace YanlongMa\Push;

/**
 * CURL Http Client
 */
class Client
{
    public $connectTimeout = 10;
    public $timeout = 60;
    public function __construct(array $config = array())
    {
        foreach ($config as $k => $item) {
            $this->$k = $item;
        }
    }
    /**
     * 执行GET请求
     * @param $url
     * @return Response
     */
    public function get($url, $data = null)
    {
        if (!empty($data)) {
            $char = strpos($url, '?') === false ? '?' : '&';
            $url = $url . $char . http_build_query($data);
        }
        return self::request('GET', $url);
    }
    /**
     * 执行POST请求
     * @param string $url
     * @param array|string $data
     * @return Response
     */
    public function post($url, $data = null)
    {
        return self::request('POST', $url, $data);
    }
    /**
     * 上传文件
     * @param string $url
     * @param string $field
     * @param string $filename 文件路径 例如 "./images/1.jpg"
     * @param array $data 需要post的内容
     * @return Response
     */
    public function file($url, $field, $filename, array $data = array())
    {
        $filename = realpath($filename);
        //PHP 5.6 禁用了 '@/path/filename' 语法上传文件
        if (class_exists('\CURLFile')) {
            $data[$field] = new \CURLFile($filename);
        } else {
            $data[$field] = '@' . $filename;
        }
        return self::request('POST', $url, $data);
    }
    /**
     * 执行cURL
     * @param $url
     * @param string $method 例如 GET POST HEAD DELETE PUT
     * @param null|string|array $postData
     *      类似'para1=val1&para2=val2&...'， 此时Content-Type头将会被设置成"application/x-www-form-urlencoded"
     *      也可以使用一个以字段名为键值，字段数据为值的数组。
     *      如果value是一个数组，Content-Type头将会被设置成"multipart/form-data"
     *      从 PHP 5.2.0 开始，使用 @ 前缀传递文件时，value 必须是个数组。
     *      从 PHP 5.5.0 开始, @ 前缀已被废弃，文件可通过 \CURLFile 发送。
     * @param array $options 用于提供给curl_setopt_array的参数
     * 例如:
     *  [
     *      CURLOPT_HTTPHEADER => [
     *          'Content-Type: application/json',
     *      ]
     *  ]
     * @return Response
     */
    public function request($method, $url, $postData = null, $options = array())
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, true);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeout); //接收缓冲完成超时设置 (秒)
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeout); //连接服务器超时设置(秒)
        //https请求 不验证证书和host
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        //GET POST HEAD DELETE PUT
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        if ($postData !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        if (!empty($options)) {
            curl_setopt_array($ch, $options);
        }
        $response = curl_exec($ch);
        $transferInfo = array(
                'errno' => curl_errno($ch),
                'error' => curl_error($ch),
            ) + curl_getinfo($ch);
        curl_close($ch);
        return new Response(array(
            'transferInfo' => $transferInfo,
            'header' => substr($response, 0, $transferInfo['header_size']),
            'body' => substr($response, $transferInfo['header_size']),
        ));
    }
}

class Response
{
    protected $transferInfo;
    protected $header;
    protected $body;
    public function __construct(array $data)
    {
        foreach ($data as $k => $v) {
            $this->$k = $v;
        }
    }
    /**
     * http响应状态码
     *
     * @return int
     */
    public function getStatusCode()
    {
        return isset($this->transferInfo['http_code']) ? $this->transferInfo['http_code'] : 0;
    }
    /**
     * http响应body
     *
     * @return string
     */
    public function getBody()
    {
        return $this->body;
    }
    /**
     * 传输状态信息
     *
     * @return array
     */
    public function getTransferInfo()
    {
        return (array)$this->transferInfo;
    }
    /**
     * http响应头
     *
     * @return string
     */
    public function getHeader()
    {
        return $this->header;
    }
}