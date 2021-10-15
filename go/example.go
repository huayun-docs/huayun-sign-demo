package main

import (
	"fmt"

	"github.com/json-iterator/go"

	"chinacapi/chinac"
)

var Json = jsoniter.ConfigCompatibleWithStandardLibrary

func main() {
	// 用户ak、sk
	accessKeyId, accessKeySecret := "aaaaaaaaaaaaaaaaaaaa", "bbbbbbbbbbbbbbbbbbbb"
	// GET请求示例(查询云主机)
	api := chinac.NewChinacOpenApi(accessKeyId, accessKeySecret)
	api.SetAction("DescribeInstances")
	// GET请求参数，键值对map，值为字符串（POST不限制），如map[string]interface{}{"A": "1"}
	api.SetRequestParams(map[string]interface{}{"Region": "cn-xmdev2"})
	res, err := api.Do()
	if err != nil {
		panic(err)
	}
	fmt.Println(res)

	// POST请求示例(查询云手机)
	api.SetAction("ListCloudPhone")
	api.SetHttpMethod("POST") //请求方式，默认GET
	// 请求参数
	api.SetRequestParams(map[string]interface{}{"Status": "START"})
	res, err = api.Do()
	if err != nil {
		panic(err)
	}
	fmt.Println(res)

	/***文件上传示例***/
	// 获取文件服务器地址
	api.SetAction("GetCpfsUrl")
	api.SetHttpMethod("POST")
	api.SetRequestParams(map[string]interface{}{"Region": "cn-cloudPhone2"})
	res, err = api.Do()
	if err != nil {
		panic(err)
	}
	var r map[string]interface{}
	err = Json.UnmarshalFromString(res["Info"].(string), &r)
	if err != nil {
		panic(err)
	}
	url := r["data"].(map[string]interface{})["WebFsUrl"].(string)

	// 获取上传令牌
	api.SetAction("GetUploadToken")
	api.SetHttpMethod("POST")
	api.SetRequestParams(map[string]interface{}{"Region": "cn-cloudPhone2"})
	res, err = api.Do()
	if err != nil {
		panic(err)
	}
	err = Json.UnmarshalFromString(res["Info"].(string), &r)
	if err != nil {
		panic(err)
	}
	token := r["data"].(map[string]interface{})["Token"].(string)

	// 上传文件
	file := chinac.NewChinacFileApi(accessKeyId, accessKeySecret)
	file.SetFileUrl(url)
	file.SetToken(token)
	file.SetUploadFileName("test.txt")
	res, err = file.Do()
	if err != nil {
		panic(err)
	}
	err = Json.UnmarshalFromString(res["Info"].(string), &r)
	if err != nil {
		panic(err)
	}
	fileId := r["ResponseData"].(map[string]interface{})["FileId"].(string)
	fmt.Print(fileId)
}
