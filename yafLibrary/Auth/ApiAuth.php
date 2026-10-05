<?php
/**
 * 签名
 */

namespace Auth;


class ApiAuth
{

    const KEY = 'PI$V1aLYs5bx5eBB!&4KtHokbpTfi46Hr38hiP3mOG9eCqlZnRCyEq8Eoapes37@';

    /**
     * 获取签名
     * @param array $arr
     * @return string
     */
    public function getSign($arr)
    {
        //去除空值
        $arr = array_filter($arr);
        if (isset($arr['sign'])) {
            unset($arr['sign']);
        }
        //按照键名字典排序(升序)
        ksort($arr);
        //生成url格式的字符串
        $str = $this->arrToUrl($arr) . '&key=' . self::KEY;
        return strtoupper(md5($str));
    }

    /**
     * 获取带签名的数组
     * @param array $arr
     * @return array
     */
    public function setSign($arr)
    {
        $arr['sign'] = $this->getSign($arr);;
        return $arr;
    }

    /**
     * 数组转URL格式的字符串
     * @param array $arr
     * @return string
     */
    public function arrToUrl($arr)
    {
        return urldecode(http_build_query($arr));
    }

}
