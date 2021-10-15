/**
 * openapi文件上传接口
 * 执行完Do后会重置参数
 * 不会重置设置的accessKeyId、accessKeySecret
 * @author Tu
 */
package chinac

import (
	"bytes"
	"io"
	"io/ioutil"
	"mime/multipart"
	"net/http"
	"os"
	"path/filepath"
	"strings"
	"time"
)

type ChinacFileApi interface {
	SetFileUrl(fileUrl string)
	SetToken(token string)
	SetUploadFileName(uploadFileName string)
	Do() (map[string]interface{}, error)
}

type fileapi struct {
	/**
	 * 用户Access Key，可以通过用控新增、查看
	 */
	accessKeyId string

	/**
	 * 用户Access Key Secret，可以通过用控新增、查看
	 */
	accessKeySecret string

	/**
	 * 上传文件文件地址
	 * 通过GetCpfsUrl获取
	 */
	fileUrl string

	/**
	 * 文件上传所需token
	 * 通过GetCpfsUrl获取
	 */
	token string

	/**
	 * 上传的文件路径
	 */
	uploadFileName string

	/**
	 * 请求头map
	 */
	headers map[string]string
}

// 设置文件上传通信地址
func (f *fileapi) SetFileUrl(fileUrl string) {
	f.fileUrl = fileUrl
}

// 设置通信token
func (f *fileapi) SetToken(token string) {
	f.token = token
}

// 设置上传的文件路径
func (f *fileapi) SetUploadFileName(uploadFileName string) {
	f.uploadFileName = uploadFileName
}

// 请求并返回结果
func (f *fileapi) Do() (map[string]interface{}, error) {
	f.generateHeaders()
	res, err := f.request()
	f.refresh()
	return res, err
}

// 生成请求头
func (f *fileapi) generateHeaders() {
	f.headers = map[string]string{
		"Accept-Encoding": "*",
		"Date":            time.Now().Format("2006-01-02T15:04:05 +0800"),
	}
}

// 请求通信
func (f *fileapi) request() (map[string]interface{}, error) {
	data := map[string]interface{}{
		"Status": 500,
		"Info":   ""}
	url := strings.Join([]string{f.fileUrl, "/cp/fs/upload?token=", f.token}, "")

	// 处理图片上传
	file, err := os.Open(f.uploadFileName)
	if err != nil {
		return data, err
	}
	defer file.Close()
	body := &bytes.Buffer{}
	writer := multipart.NewWriter(body)
	iw, err := writer.CreateFormFile("file", filepath.Base(f.uploadFileName))
	if err != nil {
		return data, err
	}
	_, err = io.Copy(iw, file)
	err = writer.Close()
	if err != nil {
		return data, err
	}
	f.headers["Content-Type"] = writer.FormDataContentType()

	client := &http.Client{}
	req, err := http.NewRequest("POST", url, body)
	if err != nil {
		return data, err
	}
	for k, _ := range f.headers {
		req.Header.Set(k, f.headers[k])
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
func (f *fileapi) refresh() {
	f.fileUrl = ""
	f.token = ""
	f.uploadFileName = ""
	f.headers = map[string]string{}
}

func NewChinacFileApi(accessKeyId, accessKeySecret string) ChinacFileApi {
	return &fileapi{
		accessKeyId:     accessKeyId,
		accessKeySecret: accessKeySecret}
}
