package com.chinac.util;

import org.apache.commons.codec.binary.Base64;

import javax.crypto.Mac;
import javax.crypto.spec.SecretKeySpec;
import java.io.UnsupportedEncodingException;
import java.net.URLEncoder;

public class UrlUtil {

	public static String urlEncode(String raw) throws UnsupportedEncodingException {
		String encodedStr = URLEncoder.encode(raw, "UTF-8");
		encodedStr = encodedStr.replace("+", "%20");
		encodedStr = encodedStr.replace("*", "%2A");
		return encodedStr.replace("%7E", "~");
	}

	public static String getSign(String secretKey, String stringToSign, String signatureMethod) throws Exception{
        if (secretKey == null || stringToSign == null) {
            return "";
        }
		signatureMethod = signatureMethod == null ?  "HmacSHA256" : signatureMethod;
		Mac mac = Mac.getInstance(signatureMethod);
		mac.init(new SecretKeySpec(secretKey.getBytes("UTF-8"), signatureMethod));
		byte[] bytes = mac.doFinal(stringToSign.getBytes("UTF-8"));
		String signature = new String(Base64.encodeBase64(bytes), "UTF-8");
		return signature;
    }
}
