"""
openapi文件上传接口
执行完do后会重置参数
不会重置设置的accessKeyId、accessKeySecret
"""

import os
import requests
import time


class ChinacFileApi:
    """初始化"""
    def __init__(self, access_key_id, access_key_secret):
        super(ChinacFileApi, self).__init__()
        # 用户Access Key，可以通过用控新增、查看
        self._access_key_id = access_key_id

        # 用户Access Key Secret，可以通过用控新增、查看
        self._access_key_secret = access_key_secret

        # 上传文件文件地址，通过GetCpfsUrl获取
        self.file_url = ''

        # 文件上传所需token，通过GetCpfsUrl获取
        self.token = ''

        # 上传的文件路径
        self.upload_file_name = ''

        # 请求头数组
        self.headers = {}

    # 设置文件上传通信地址
    def set_file_url(self, file_url):
        self.file_url = file_url

    # 设置通信token
    def set_token(self, token):
        self.token = token

    # 设置操作方法Action
    def set_upload_file_name(self, upload_file_name):
        self.upload_file_name = upload_file_name

    # 请求并返回结果
    def do(self):
        if os.path.isfile(self.upload_file_name) == False:
            raise Exception('File not exist')
        self.generate_headers()
        res = self.request()
        self.refresh()
        return res

    # 生成请求头
    def generate_headers(self):
        self.headers = {
            'Accept-Encoding': '*',
            'Date': time.strftime("%Y-%m-%dT%H:%M:%S +0800", time.localtime())
        }

    # 请求通信
    def request(self):
        url = self.file_url + '/cp/fs/upload?token=' + self.token
        res = requests.post(url,
                files={'file': open(self.upload_file_name, 'rb+')},
                    headers=self.headers)
        return {'Status': res.status_code, 'Info': res.text}

    # 请求后重置参数
    def refresh(self):
        self.file_url = ''
        self.token = ''
        self.upload_file_name = ''
        self.headers = {}
