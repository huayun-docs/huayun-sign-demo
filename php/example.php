<?php
/**
 * openapi接口测试
 * @@author Tu
 */

use Chinac\ChinacOpenApi;
use Chinac\ChinacFileApi;

require 'Autoloader.php';
Autoloader::register();

// 用户ak、sk
$accessKeyId     = 'aaaaaaaaaaaaaaaaaaaa';
$accessKeySecret = 'bbbbbbbbbbbbbbbbbbbb';

// GET请求示例(查询云主机)
$api = new ChinacOpenApi($accessKeyId, $accessKeySecret);
$res = $api
    ->setAction('DescribeInstances')
    ->setRequestParams(['Region'=>'cn-xmdev2']) //请求参数
    ->do();
print_r($res);

// POST请求示例(查询云手机)
$res = $api
    ->setAction('ListCloudPhone')
    ->setHttpMethod('POST')                 //请求方式，默认GET
    ->setRequestParams(['Status'=>'START']) //请求参数
    ->do();
print_r($res);

/***文件上传示例***/
// 获取文件服务器地址
$res = $api->setAction('GetCpfsUrl')->setHttpMethod('POST')->setRequestParams(['Region'=>'cn-cloudPhone2'])->do();
$url = json_decode($res['Info'], 1)['data']['WebFsUrl'];

// 获取上传令牌
$res = $api->setAction('GetUploadToken')->setHttpMethod('POST')->setRequestParams(['Region'=>'cn-cloudPhone2'])->do();
$token = json_decode($res['Info'], 1)['data']['Token'];

// 上传文件
$file = new ChinacFileApi($accessKeyId, $accessKeySecret);
$file->setFileUrl($url)->setToken($token)->setUploadFileName('test.txt');
try {
    $res = $file->do();
    $fileId = json_decode($res['Info'], 1)['ResponseData']['FileId'];
    print_r($fileId);
} catch (\Throwable $e) {
    print_r($e->getMessage());
}
