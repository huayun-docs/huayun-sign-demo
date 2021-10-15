package com.chinac.util;

import com.fasterxml.jackson.core.JsonParser.Feature;
import com.fasterxml.jackson.databind.DeserializationFeature;
import com.fasterxml.jackson.databind.MapperFeature;
import com.fasterxml.jackson.databind.ObjectMapper;

import java.io.IOException;

public class JacksonUtil {
	private static final ObjectMapper objectMapper = new ObjectMapper();
	static {
		objectMapper.configure(Feature.ALLOW_SINGLE_QUOTES, true);  //可以用单引号设置
		objectMapper.configure(Feature.ALLOW_UNQUOTED_FIELD_NAMES, true);  //json属性可以没有设置转义
		objectMapper.configure(Feature.IGNORE_UNDEFINED, true);
		objectMapper.configure(DeserializationFeature.FAIL_ON_NULL_FOR_PRIMITIVES, true);
		objectMapper.configure(DeserializationFeature.ACCEPT_EMPTY_STRING_AS_NULL_OBJECT, true);
		objectMapper.configure(DeserializationFeature.ACCEPT_EMPTY_STRING_AS_NULL_OBJECT, true);
		objectMapper.configure(DeserializationFeature.FAIL_ON_UNKNOWN_PROPERTIES, false);  //json中有属性但在bean中没有   设置成功
		objectMapper.configure(MapperFeature.USE_ANNOTATIONS, true); //使用注解
	}


	public static <T> T readerJsonToBean(String jsonStr, Class<T> clzz) throws IOException{
		return objectMapper.readValue(jsonStr, clzz);
	}

	public static String writeBeanToJson(Object bean) throws IOException{
		return objectMapper.writeValueAsString(bean);
	}
}
