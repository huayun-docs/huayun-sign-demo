package com.chinac;

import com.chinac.dto.BaseResponseDto;
import com.chinac.util.DateUtil;
import com.chinac.util.JacksonUtil;
import com.chinac.util.Md5Util;
import com.chinac.util.UrlUtil;
import org.apache.http.HttpEntity;
import org.apache.http.HttpResponse;
import org.apache.http.client.entity.EntityBuilder;
import org.apache.http.client.methods.HttpPost;
import org.apache.http.impl.client.CloseableHttpClient;
import org.apache.http.impl.client.HttpClients;
import org.apache.http.util.EntityUtils;

import java.net.URI;
import java.util.Date;
import java.util.HashMap;
import java.util.Map;
import java.util.TreeMap;

public class SignatureDemo {

    private static final String AccessKeyId ="AccessKeyIdExample";
    private static final String AccessKeySecret ="AccessKeySecretExample";
    private static final String Version ="1.0";
    private static final String ApiUri = "http://api.chinac.com/v2";

    public static void main(String[] args) throws Exception{
        new SignatureDemo().testCreateCloudPhone();
    }


    /**
     * 创建云手机
     * @throws Exception
     */
    public void testCreateCloudPhone() throws Exception{
        Map<String, Object> queryParam = new HashMap<>();
        queryParam.put("Action","OpenCloudPhone");

        Map<String, Object> bodyParam = new HashMap<>();
        bodyParam.put("Region","cn-szyh-cloudphone");
        bodyParam.put("CloudPhoneImageId","i-6l11ak25eet49s");
        bodyParam.put("PayType","PREPAID");
        bodyParam.put("ProductModelId",861);


        BaseResponseDto responseDto = sendPostRequest(ApiUri,queryParam,bodyParam,BaseResponseDto.class);
        System.out.println(JacksonUtil.writeBeanToJson(responseDto));
    }

    /**
     * post请求实例
     * @param uri
     * @param queryMap
     * @param bodyMap
     * @param clazz
     * @param <T>  返回值根据实际自行定义
     * @return
     * @throws Exception
     */
    public <T> T sendPostRequest(String uri, Map<String, Object> queryMap,Map<String, Object> bodyMap,
                                                 Class<T> clazz) throws Exception{
        if(queryMap == null){
            queryMap = new HashMap<>();
        }

        //设置公共参数
        String date = DateUtil.getUTCDateStr(new Date());
        queryMap.put("AccessKeyId", AccessKeyId);
        queryMap.put("Version", Version);
        queryMap.put("Date", date);

        //按照参数名称升序排列并编码
        Map<String, Object> treeQueryMap = new TreeMap<String, Object>(queryMap);
        StringBuilder params = new StringBuilder();
        for(String key : treeQueryMap.keySet()){
            if(treeQueryMap.get(key) != null){
                params.append(key).append("=").append(UrlUtil.urlEncode(treeQueryMap.get(key).toString())).append("&");
            }
        }
        String paramsStrForMd5 = params.deleteCharAt(params.length() - 1).toString();

        //被签名字符串 :  METHOD + "\n" + MD5(属性key-value) + "\n" + ContentType + "\n" + 时间 + "\n"
        String stringToSign = HttpPost.METHOD_NAME+"\n" + Md5Util.md5(paramsStrForMd5) + "\napplication/json\n" + UrlUtil.urlEncode(date) + "\n";

        //构造签名
        String signature = UrlUtil.getSign(AccessKeySecret, stringToSign, "HmacSHA256");
        params.append("&Signature=").append(UrlUtil.urlEncode(signature));

        //以下为http请求简单示例
        CloseableHttpClient httpClient = HttpClients.createDefault();
        EntityBuilder entityBuilder = EntityBuilder.create();
        if(bodyMap != null){
            entityBuilder.setText(JacksonUtil.writeBeanToJson(bodyMap));
        }
        HttpEntity httpEntity = entityBuilder.build();
        HttpPost httpPost = new HttpPost(uri);
        httpPost.addHeader("Content-Type", "application/json");
        httpPost.setURI(new URI(httpPost.getURI().toString() + "?" + params.toString()));
        httpPost.setEntity(httpEntity);

        HttpResponse httpResponse = httpClient.execute(httpPost);
        String returnStr = EntityUtils.toString(httpResponse.getEntity(), "UTF-8");
        return JacksonUtil.readerJsonToBean(returnStr,clazz);
    }

}
