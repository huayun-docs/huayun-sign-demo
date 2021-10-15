package com.chinac.util;

import java.text.SimpleDateFormat;
import java.util.Date;
import java.util.Locale;

/**
 * 日期工具类
 */
public class DateUtil {
    private static final  SimpleDateFormat UTCDateFormatter = new SimpleDateFormat("yyyy-MM-dd'T'HH:mm:ss Z",Locale.CHINESE);

    public static String getUTCDateStr(Date date) {
        return UTCDateFormatter.format(date);
    }
}
