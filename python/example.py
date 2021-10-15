#!/usr/bin/env python3
# -*- coding: utf-8 -*-
# openapi接口测试
# 需要requests库，pip install requests

import json
from chinac.chinac_open_api import ChinacOpenApi
from chinac.chinac_file_api import ChinacFileApi

if __name__ == '__main__':
    # 用户ak、sk
    access_key_id     = 'aaaaaaaaaaaaaaaaaaaa'
    access_key_secret = 'bbbbbbbbbbbbbbbbbbbb'

    # GET请求示例(查询云主机)
    api = ChinacOpenApi(access_key_id, access_key_secret)
    api.set_action('DescribeInstances')
    api.set_request_params({'Region': 'cn-xmdev2'}) #请求参数
    res = api.do()
    print(res)

    # POST请求示例(查询云手机)
    api.set_action('ListCloudPhone')
    api.set_http_method('POST')                 #请求方式，默认GET
    api.set_request_params({'Status': 'START'}) #请求参数
    res = api.do()
    print(res)

    """文件上传示例"""
    # 获取文件服务器地址
    api.set_action('GetCpfsUrl')
    api.set_http_method('POST')
    api.set_request_params({'Region': 'cn-cloudPhone2'})
    res = api.do()
    res = json.loads(res['Info'])
    url = res['data']['WebFsUrl']

    # 获取上传令牌
    api.set_action('GetUploadToken')
    api.set_http_method('POST')
    api.set_request_params({'Region': 'cn-cloudPhone2'})
    res = api.do()
    res = json.loads(res['Info'])
    token = res['data']['Token']

    # 上传文件
    file = ChinacFileApi(access_key_id, access_key_secret)
    file.set_file_url(url)
    file.set_token(token)
    file.set_upload_file_name('test.txt')
    try:
        res = file.do()
        res = json.loads(res['Info'])
        file_id = res['ResponseData']['FileId']
        print(file_id)
    except Exception as e:
        print(e)
