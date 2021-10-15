/**
 * openapi接口
 * 执行完Do后会重置参数
 * 不会重置设置的accessKeyId、accessKeySecret、openApiUrl
 */
package chinac

import (
	"bytes"
	"crypto/hmac"
	"crypto/md5"
	"crypto/sha256"
	"encoding/base64"
	"encoding/hex"
	"io/ioutil"
	"net/http"
	"net/url"
	"strings"
	"time"

	"github.com/json-iterator/go"
)

var Json = jsoniter.ConfigCompatibleWithStandardLibrary

type ChinacOpenApi interface {
	SetOpenApiUrl(openApiUrl string)
	SetHttpMethod(httpMethod string)
	SetAction(action string)
	SetRequestParams(params map[string]interface{})
	Do() (map[string]interface{}, error)
}

type openapi struct {
	/**
	 * 用户Access Key，可以通过用控新增、查看
	 */
	accessKeyId string

	/**
	 * 用户Access Key Secret，可以通过用控新增、查看
	 */
	accessKeySecret string

	/**
	 * openapi通信地址地址，默认线上v2版，可以通过setOpenApiUrl修改
	 * 结尾不含/
	 */
	openApiUrl string

	/**
	 * 处理后的openapi通信地址
	 */
	requestUrl string

	/**
	 * 请求方式，默认GET，可以通过setHttpMethod修改
	 * 支持的有GET、POST、PUT等
	 */
	httpMethod string

	/**
	 * 请求操作Action名称，如DescribeInstances
	 */
	action string

	/**
	 * 请求参数map，键值对应请求参数名称和值
	 * 如果请求为GET，值为字符串
	 */
	params map[string]interface{}

	/**
	 * json参数，一般用于POST、PUT，这边会自动处理
	 */
	jsonBody []byte

	/**
	 * 请求头map
	 */
	headers map[string]string
}

// 修改openapi默认通信地址
func (o *openapi) SetOpenApiUrl(openApiUrl string) {
	o.openApiUrl = openApiUrl
}

// 修改修改请求方式
func (o *openapi) SetHttpMethod(httpMethod string) {
	o.httpMethod = httpMethod
}

// 设置操作方法Action
func (o *openapi) SetAction(action string) {
	o.action = action
}

// 设置请求参数
func (o *openapi) SetRequestParams(params map[string]interface{}) {
	o.params = params
}

// 请求并返回结果
func (o *openapi) Do() (map[string]interface{}, error) {
	o.generateHeaders()
	o.dealParams()
	res, err := o.request()
	o.refresh()
	return res, err
}

// 生成请求头
func (o *openapi) generateHeaders() {
	o.headers = map[string]string{
		"Content-Type":    "application/json;charset=UTF-8",
		"Accept-Encoding": "*",
		"Date":            time.Now().Format("2006-01-02T15:04:05 +0800"),
	}
}

// 处理参数，生成通信签名等
func (o *openapi) dealParams() {
	params := map[string]interface{}{
		"Action":      o.action,
		"Version":     "1.0", //目前固定1.0
		"AccessKeyId": o.accessKeyId,
		"Date":        o.headers["Date"]}
	if len(o.params) > 0 {
		if o.httpMethod == "GET" {
			for k, _ := range o.params {
				params[k] = o.params[k]
			}
		} else {
			o.jsonBody, _ = Json.Marshal(o.params)
		}
	}
	// 生成签名，更新url
	query, signature := o.generateSignature(params)
	o.requestUrl = strings.Join([]string{o.openApiUrl, "?", query, "&Signature=", signature}, "")
}

// 生成签名
func (o *openapi) generateSignature(params map[string]interface{}) (string, string) {
	query := o.percentParams(params)
	signArr := []string{o.httpMethod, "\n"}

	// md5加密
	h := md5.New()
	h.Write([]byte(query))
	cipherStr := h.Sum(nil)
	mdStr := hex.EncodeToString(cipherStr)

	signArr = append(signArr, mdStr, "\n")
	signArr = append(signArr, o.headers["Content-Type"], "\n")
	signArr = append(signArr, encodeQueryEscape(o.headers["Date"]), "\n")
	signStr := strings.Join(signArr, "")

	signature := o.shaHmac256Signature(signStr)
	signature = encodeQueryEscape(signature)
	return query, signature
}

// base64 hmac256加密
func (o *openapi) shaHmac256Signature(signStr string) string {
	key := []byte(o.accessKeySecret)
	h := hmac.New(sha256.New, key)
	h.Write([]byte(signStr))
	return base64.StdEncoding.EncodeToString(h.Sum(nil))
}

// 转成url通信标准RFC 3986
// 如果是get的话，规定值必须是字符串
func (o *openapi) percentParams(params map[string]interface{}) string {
	urlValue := url.Values{}
	for k, _ := range params {
		urlValue.Add(k, params[k].(string))
	}
	urlstr := urlValue.Encode()
	return percentEncode(urlstr)
}

// 请求通信
func (o *openapi) request() (map[string]interface{}, error) {
	data := map[string]interface{}{
		"Status": 500,
		"Info":   ""}
	client := &http.Client{}
	req, err := http.NewRequest(o.httpMethod, o.requestUrl, bytes.NewReader(o.jsonBody))
	if err != nil {
		return data, err
	}
	for k, _ := range o.headers {
		req.Header.Set(k, o.headers[k])
	}
	res, err := client.Do(req)
	if err != nil {
		return data, err
	}
	data["Status"] = res.StatusCode

	defer res.Body.Close()
	by, err := ioutil.ReadAll(res.Body)
	if err != nil {
		return data, err
	}
	data["Info"] = string(by)
	return data, nil
}

// 请求后重置参数
func (o *openapi) refresh() {
	o.requestUrl = ""
	o.httpMethod = "GET"
	o.action = ""
	o.params = map[string]interface{}{}
	o.jsonBody = []byte{}
	o.headers = map[string]string{}
}

func NewChinacOpenApi(accessKeyId, accessKeySecret string) ChinacOpenApi {
	api := &openapi{
		accessKeyId:     accessKeyId,
		accessKeySecret: accessKeySecret}
	api.jsonBody = []byte{}
	api.openApiUrl = "https://api.chinac.com/v2"
	api.httpMethod = "GET"
	return api
}

func percentEncode(urlstr string) string {
	urlstr = strings.Replace(urlstr, `+`, `%20`, -1)
	urlstr = strings.Replace(urlstr, `*`, `%2A`, -1)
	urlstr = strings.Replace(urlstr, `%7E`, `~`, -1)
	return urlstr
}

func encodeQueryEscape(urlstr string) string {
	urlstr = url.QueryEscape(urlstr)
	return percentEncode(urlstr)
}
