package com.chinac.dto;

import java.io.Serializable;

public class BaseResponseDto implements Serializable, Cloneable{

    /**
     * 结果编码
     */
    private Integer code;

    /**
     * 返回数据集
     */
    private Object data;
    
    /**
     * 结果信息
     */
    private String message;

    public Integer getCode() {
        return code;
    }

    public void setCode(Integer code) {
        this.code = code;
    }

    public Object getData() {
        return data;
    }

    public void setData(Object data) {
        this.data = data;
    }

    public String getMessage() {
        return message;
    }

    public void setMessage(String message) {
        this.message = message;
    }
}
