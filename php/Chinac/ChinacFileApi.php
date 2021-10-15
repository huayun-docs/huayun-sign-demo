<?php
/**
 * openapi文件上传接口
 * 执行完do后会重置参数
 * 不会重置设置的accessKeyId、accessKeySecret
 * @author Tu
 */
namespace Chinac;

use CURLFile;
use Exception;

class ChinacFileApi
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
     * 上传文件文件地址
     * 通过GetCpfsUrl获取
     * @var string
     */
    private $fileUrl = '';

    /**
     * 文件上传所需token
     * 通过GetCpfsUrl获取
     * @var string
     */
    private $token = '';

    /**
     * 上传的文件路径
     * @var string
     */
    private $uploadFileName = '';

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
     * 设置文件上传通信地址
     * @param  string $fileUrl 文件上传地址
     * @return this
     */
    public function setFileUrl($fileUrl)
    {
        $this->fileUrl = $fileUrl;
        return $this;
    }

    /**
     * 设置通信token
     * @param  string $token 通信token
     * @return this
     */
    public function setToken($token)
    {
        $this->token = $token;
        return $this;
    }

    /**
     * 设置上传的文件路径
     * @param  string $uploadFileName 文件路径
     * @return this
     */
    public function setUploadFileName($uploadFileName)
    {
        $this->uploadFileName = $uploadFileName;
        return $this;
    }

    /**
     * 请求并返回结果
     * @return string
     */
    public function do()
    {
        if (!is_file($this->uploadFileName)) {
            throw new Exception('File not exist', 404);
        }
        $this->generateHeaders(); //通信头
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
            'Content-Type'    => 'multipart/form-data',
            'Accept-Encoding' => '*',
            'Date'            => date('Y-m-d\TH:i:s O')
        ];
    }

    /**
     * curl请求
     * @return array
     */
    private function request()
    {
        $url = $this->fileUrl . '/cp/fs/upload?token=' . $this->token;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FAILONERROR, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, ['file'=>(new CURLFile($this->uploadFileName))]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 600);        //超时
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 600); //连接等待超时
        if (strlen($this->fileUrl) > 5 && strtolower(substr($this->fileUrl, 0, 5)) == 'https') {
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
        $this->fileUrl = '';
        $this->token = '';
        $this->uploadFileName = '';
        $this->headers = [];
    }
}
