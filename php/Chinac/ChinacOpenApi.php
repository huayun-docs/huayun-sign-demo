<?php
/**
 * openapi接口
 * 执行完do后会重置参数
 * 不会重置设置的accessKeyId、accessKeySecret、openApiUrl
 * @@author Tu
 */
namespace Chinac;

class ChinacOpenApi
{
    /**
     * 用户Access Key，可以通过用控新增、查看
     * @var string
     */
    private $accessKeyId = '';
    /**
     * 用户Access Key Secret，可以通过用控新增、查看
     * @var string
     */
    private $accessKeySecret = '';

    /**
     * openapi通信地址，默认线上v2版，可以通过setOpenApiUrl修改
     * 结尾不含/
     * @var string
     */
    private $openApiUrl = 'https://api.chinac.com/v2';

    /**
     * 处理后的openapi通信地址
     * @var string
     */
    private $requestUrl = '';

    /**
     * 请求方式，默认GET，可以通过setHttpMethod修改
     * 支持的有GET、POST、PUT等
     * @var string
     */
    private $httpMethod = 'GET';

    /**
     * 请求操作Action名称，如DescribeInstances
     * @var string
     */
    private $action = '';

    /**
     * 请求参数数组，键值对应请求参数名称和值，如：
     * ['Region'=>'a', 'ProductStatus'=>'NORMAL']
     * @var array
     */
    private $params = [];

    /**
     * json参数，一般用于POST、PUT，这边会自动处理
     * @var string
     */
    private $jsonBody = '';

    /**
     * 请求头数组
     * @var array
     */
    private $headers = [];

    /**
     * 构造函数
     * @param string $accessKeyId     用户Access Key
     * @param string $accessKeySecret 用户Access Key Secret
     */
    function __construct($accessKeyId, $accessKeySecret)
    {
        $this->accessKeyId = $accessKeyId;
        $this->accessKeySecret = $accessKeySecret;
    }

    /**
     * 修改openapi默认通信地址
     * @param  string $openApiUrl openapi通信地址
     * @return this
     */
    public function setOpenApiUrl($openApiUrl)
    {
        $this->openApiUrl = $openApiUrl;
        return $this;
    }

    /**
     * 修改修改请求方式
     * @param  string $httpMethod 请求方式
     * @return this
     */
    public function setHttpMethod($httpMethod)
    {
        $this->httpMethod = $httpMethod;
        return $this;
    }

    /**
     * 设置操作方法Action
     * @param  string $action 操作action名称
     * @return this
     */
    public function setAction($action)
    {
        $this->action = $action;
        return $this;
    }

    /**
     * 设置请求参数
     * @param  array $params 请求参数数组
     * @return this
     */
    public function setRequestParams(array $params = [])
    {
        $this->params = $params;
        return $this;
    }

    /**
     * 请求并返回结果
     * @return string
     */
    public function do()
    {
        $this->generateHeaders();
        $this->dealParams();
        $res = $this->request();
        $this->refresh();
        return $res;
    }

    /**
     * 生成请求头
     * @return void
     */
    private function generateHeaders()
    {
        date_default_timezone_set('PRC');
        $this->headers = [
            'Content-Type'    => 'application/json;charset=UTF-8',
            'Accept-Encoding' => '*',
            'Date'            => date('Y-m-d\TH:i:s O')
        ];
    }

    /**
     * 处理参数，生成通信签名等
     * @return void
     */
    private function dealParams()
    {
        $params = [
            'Action'      => $this->action,
            'Version'     => '1.0', //目前固定1.0
            'AccessKeyId' => $this->accessKeyId,
            'Date'        => $this->headers['Date']
        ];
        if ($this->params) {
            if ($this->httpMethod == 'GET') {
                $params = array_merge($params, $this->params);
            } else {
                // POST等参数转成json字符
                $this->jsonBody = json_encode($this->params);
            }
        }
        // 生成签名，更新url
        $res = $this->generateSignature($params);
        $this->requestUrl = $this->openApiUrl . '?' . $res['query'] . '&Signature=' . $res['signature'];
    }

    /**
     * 生成签名
     * @param  array $params 参数
     * @return array
     */
    private function generateSignature($params)
    {
        $stringToSign = $this->httpMethod . "\n";
        $query = http_build_query($params, '', '&', PHP_QUERY_RFC3986);
        $stringToSign .= md5($query) . "\n";
        $stringToSign .= $this->headers['Content-Type'] . "\n";
        $stringToSign .= rawurlencode($this->headers['Date']) . "\n";
        $signature = base64_encode(hash_hmac('sha256', $stringToSign, $this->accessKeySecret, true));
        $signature = $this->percentEncode($signature);
        return ['query'=>$query, 'signature'=>$signature];
    }

    /**
     * 转成url通信标准RFC 3986
     * @param  string $str 原始数据
     * @return string
     */
    private function percentEncode($str)
    {
        $res = urlencode($str);
        $res = preg_replace('/\+/', '%20', $res);
        $res = preg_replace('/\*/', '%2A', $res);
        $res = preg_replace('/%7E/', '~', $res);
        return $res;
    }

    /**
     * 通信请求
     * @return array
     */
    private function request()
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $this->httpMethod);
        curl_setopt($ch, CURLOPT_URL, $this->requestUrl);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        if ($this->jsonBody) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $this->jsonBody);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 120);        //超时
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 120); //连接等待超时
        if (strlen($this->openApiUrl) > 5 && strtolower(substr($this->openApiUrl, 0, 5)) == 'https') {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }
        $curlHeaders = $this->generateCurlHeader($this->headers);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $curlHeaders);
        $response = curl_exec($ch);
        $httpStatus = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        return ['Status'=>$httpStatus, 'Info'=>$response];
    }

    /**
     * 生成curl头部规则
     * @param  array  $headers 键值对header
     * @return array
     */
    private function generateCurlHeader(array $headers)
    {
        $curlHeader = [];
        foreach ($headers as $k => $v) {
            $curlHeader[] = $k . ':' . $v;
        }
        return $curlHeader;
    }

    /**
     * 请求后重置参数
     * @return void
     */
    private function refresh()
    {
        $this->requestUrl = '';
        $this->httpMethod = 'GET';
        $this->action = '';
        $this->params = [];
        $this->jsonBody = '';
        $this->headers = [];
    }
}
