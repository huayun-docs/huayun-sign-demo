package com.chinac.util;


import java.io.UnsupportedEncodingException;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;

public class Md5Util {
    private static String ALGORITHM_MD5 = "MD5";
    private static String[] hexDigits = {"0", "1", "2", "3", "4", "5", "6", "7", "8", "9", "a", "b", "c", "d",
        "e", "f"};


    public static String md5(String text) throws UnsupportedEncodingException,NoSuchAlgorithmException {
        MessageDigest msgDigest = MessageDigest.getInstance(ALGORITHM_MD5);
        byte[] bytes = msgDigest.digest(text.getBytes("utf-8"));
        return byteArrayToHexString(bytes);
    }

    /**
     * 转换字节数组为16进制字串
     * @param byteArray 字节数组
     * @return 16进制字串
     */
    private static String byteArrayToHexString(byte[] byteArray){
        StringBuffer resultSb = new StringBuffer();
        for (int i = 0; i < byteArray.length; i++){
            int nb = byteArray[i];
            if (nb < 0){
                nb = 256 + nb;
            }
            int d1 = nb / 16;
            int d2 = nb % 16;
            resultSb.append(hexDigits[d1] + hexDigits[d2]);
        }
        return resultSb.toString();
    }

}
