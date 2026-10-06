<?php

if (!function_exists('jkdContext')) {
    /**
     * 获取当前协程上下文，用于存放请求级数据。
     * 相比 $GLOBALS（进程级、跨协程共享），Context 是协程隔离的，可避免并发串数据。
     */
    function jkdContext()
    {
        $context = \Swoole\Coroutine::getContext();
        if ($context === null) {
            static $fallback = null;
            if ($fallback === null) {
                $fallback = new \Swoole\Coroutine\Context();
            }
            $context = $fallback;
        }
        return $context;
    }
}


if (!function_exists('dump')) {
    function dump($var, $echo = true, $label = null, $flags = ENT_SUBSTITUTE)
    {
        $label = (null === $label) ? '' : rtrim($label) . ':';
        ob_start();
        print_r($var);
        $output = ob_get_clean();
        $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
        if (Yaf\Dispatcher::getInstance()->getRequest()->isCli()) {
            $output = PHP_EOL . $label . $output . PHP_EOL;
        } else {
            if (!extension_loaded('xdebug')) {
                $output = htmlspecialchars($output, $flags);
            }

            $output = '<pre>' . $label . $output . '</pre>';
        }
        if ($echo) {
            echo($output);
            return;
        } else {
            return $output;
        }
    }
}


if (!function_exists('dd')) {
    function dd(...$vars)
    {
        echo '<pre>';
        foreach ($vars as $v) {
            print_r($v);
            echo ' ';
        }
        echo '</pre>';
        exit(1);
    }
}


/**
 * 获取环境
 */
if (!function_exists('environ')) {
    function environ()
    {
        return Yaf\Application::app()->environ();
    }
}


/**
 * 检查对应Service
 */
if (!function_exists('getService')) {
    function getService()
    {
        $req = \Yaf\Application::app()->getDispatcher()->getRequest();
        return 'app\services\\' . $req->module . '\\' . $req->controller;
    }
}


/**
 * 检查是否线上环境
 */
if (!function_exists('checkEnv')) {
    function checkEnv()
    {
        return \Yaf\Application::app()->environ() != 'product' ? true : false;
    }
}


/**
 * 获取用户KEY
 */
if (!function_exists('UserKey')) {
    function UserKey()
    {
        return jkdContext()['UserKey'] ?? '';
    }
}


/**
 * 获取用户IP
 */
if (!function_exists('getClientIp')) {
    function getClientIp()
    {
        $headerData = jkdContext()['REQUEST_HEADER'] ?? [];
        return $headerData['x-real-ip'] ?? '';
    }
}


/**
 * 数组分组
 */
if (!function_exists('array_group')) {
    function array_group(array $data, string $key)
    {
        $list = [];
        foreach ($data as $da) {
            $list[$da[$key]][] = $da;
        }

        return $list;
    }
}


/**
 * 获取文件下的所有目录
 */
if (!function_exists('getDirContent')) {
    function getDirContent($path)
    {
        if (!is_dir($path)) {
            return false;
        }
        //scandir方法
        $arr = [];
        $data = scandir($path);
        foreach ($data as $value) {
            if ($value != '.' && $value != '..') {
                $arr[] = $value;
            }
        }

        return $arr;
    }
}


/**
 * xss过滤函数
 */
if (!function_exists('remove_xss')) {
    function remove_xss($string)
    {
        if (!is_string($string) || $string === '') {
            return $string;
        }

        static $pattern = null;
        if ($pattern === null) {
            $parm1 = array('javascript', 'vbscript', 'expression', 'applet', 'meta', 'xml', 'blink', 'link', 'script', 'embed', 'object', 'iframe', 'frame', 'frameset', 'ilayer', 'layer', 'bgsound', 'title', 'base');

            $parm2 = array('onabort', 'onactivate', 'onafterprint', 'onafterupdate', 'onbeforeactivate', 'onbeforecopy', 'onbeforecut', 'onbeforedeactivate', 'onbeforeeditfocus', 'onbeforepaste', 'onbeforeprint', 'onbeforeunload', 'onbeforeupdate', 'onblur', 'onbounce', 'oncellchange', 'onchange', 'onclick', 'oncontextmenu', 'oncontrolselect', 'oncopy', 'oncut', 'ondataavailable', 'ondatasetchanged', 'ondatasetcomplete', 'ondblclick', 'ondeactivate', 'ondrag', 'ondragend', 'ondragenter', 'ondragleave', 'ondragover', 'ondragstart', 'ondrop', 'onerror', 'onerrorupdate', 'onfilterchange', 'onfinish', 'onfocus', 'onfocusin', 'onfocusout', 'onhelp', 'onkeydown', 'onkeypress', 'onkeyup', 'onlayoutcomplete', 'onload', 'onlosecapture', 'onmousedown', 'onmouseenter', 'onmouseleave', 'onmousemove', 'onmouseout', 'onmouseover', 'onmouseup', 'onmousewheel', 'onmove', 'onmoveend', 'onmovestart', 'onpaste', 'onpropertychange', 'onreadystatechange', 'onreset', 'onresize', 'onresizeend', 'onresizestart', 'onrowenter', 'onrowexit', 'onrowsdelete', 'onrowsinserted', 'onscroll', 'onselect', 'onselectionchange', 'onselectstart', 'onstart', 'onstop', 'onsubmit', 'onunload');

            // 关键字字符之间可被插入的 HTML 实体（换行/回车/Tab），用于识别被拆分的 XSS 关键字
            $entity = '((&#[x|X]0([9][a][b]);?)?|(&#0([9][10][13]);?)?)?';

            $alternatives = array();
            foreach (array_merge($parm1, $parm2) as $word) {
                $len = strlen($word);
                $sub = '';
                for ($j = 0; $j < $len; $j++) {
                    if ($j > 0) {
                        $sub .= $entity;
                    }
                    $sub .= $word[$j];
                }
                $alternatives[] = '(?:' . $sub . ')';
            }
            $pattern = '/' . implode('|', $alternatives) . '/i';
        }

        $string = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]+/S', '', $string);
        return preg_replace($pattern, ' ', $string);
    }
}


/**
 * 安全过滤函数
 */
if (!function_exists('safe_replace')) {
    function safe_replace($string)
    {
        return str_replace(
            ['%20', '%27', '%2527', '*', "'", '"', ';', '{', '}', '\\'],
            '',
            $string
        );
    }
}


/**
 * 输出一个字符串 多少位 长度；
 */
if (!function_exists('str_strlen')) {
    function str_strlen($str)
    {
        $i = 0;
        $count = 0;
        $len = strlen($str);
        while ($i < $len) {
            $chr = ord($str[$i]);
            $count++;
            $i++;
            if ($i >= $len) break;
            if ($chr & 0x80) {
                $chr <<= 1;
                while ($chr & 0x80) {
                    $i++;
                    $chr <<= 1;
                }
            }
        }
        return $count;
    }
}


/**
 * 清空目录
 */
if (!function_exists('clean_dir')) {
    function clean_dir($dir)
    {
        if (!is_dir($dir)) {
            return true;
        }
        $files = scandir($dir);
        unset($files[0], $files[1]);
        $result = 0;
        foreach ($files as &$f) {
            $result += @unlink($dir . $f);
        }
        unset($files);
        return $result;
    }
}


/**
 * 字符串截取，支持中文和其他编码
 * @static
 * @access public
 * @param string $str 需要转换的字符串
 * @param string $start 开始位置
 * @param string $length 截取长度
 * @param string $charset 编码格式
 * @param string $suffix 截断显示字符
 * @return string
 */
if (!function_exists('msubstr')) {
    function msubstr($str, $start = 0, $length, $charset = "utf-8", $suffix = true)
    {
        if (function_exists("mb_substr"))
            $slice = mb_substr($str, $start, $length, $charset);
        elseif (function_exists('iconv_substr')) {
            $slice = iconv_substr($str, $start, $length, $charset);
            if (false === $slice) {
                $slice = '';
            }
        } else {
            $re['utf-8'] = "/[\x01-\x7f]|[\xc2-\xdf][\x80-\xbf]|[\xe0-\xef][\x80-\xbf]{2}|[\xf0-\xff][\x80-\xbf]{3}/";
            $re['gb2312'] = "/[\x01-\x7f]|[\xb0-\xf7][\xa0-\xfe]/";
            $re['gbk'] = "/[\x01-\x7f]|[\x81-\xfe][\x40-\xfe]/";
            $re['big5'] = "/[\x01-\x7f]|[\x81-\xfe]([\x40-\x7e]|\xa1-\xfe])/";
            preg_match_all($re[$charset], $str, $match);
            $slice = join("", array_slice($match[0], $start, $length));
        }
        return $suffix ? $slice . '...' : $slice;
    }
}


if (!function_exists('array_only')) {
    /**
     * Get a subset of the items from the given array.
     *
     * @param array $array
     * @param array|string $keys
     * @return array
     */
    function array_only($array, $keys)
    {
        return array_intersect_key($array, array_flip((array)$keys));
    }
}


if (!function_exists('importFile')) {
    function importFile($firstPath)
    {
        $data = getDirContent($firstPath);
        foreach ($data as $da) {
            if (is_file($firstPath . $da)) {
                \Yaf\Loader::import($firstPath . $da);
            } else {
                $thisPath = $firstPath . $da;
                $files = getDirContent($thisPath);
                foreach ($files as $file) {
                    \Yaf\Loader::import($thisPath . '/' . $file);
                }
            }
        }
        $data = null;
    }
}


if (!function_exists('checkIoStatus')) {
    function checkIoStatus($type)
    {
        static $ioConfig = null;
        if ($ioConfig === null) {
            $ioConfig = \Yaf\Registry::get('config')['io'] ?? false;
        }
        return $ioConfig ? ($ioConfig->$type ?? false) : false;
    }
}


if (!function_exists('changeReqTime')) {
    function changeReqTime($time)
    {
        switch($time) {
            case $time < 0.001:
                return number_format(($time * 1000000), 4, '.', '') . 'μs';
                break;
            case $time < 1:
                return number_format(($time * 1000), 4, '.', '') . 'ms';
                break;
            case $time < 60:
                return number_format($time, 4, '.', '') . 's';
                break;
            default:
                return number_format(($time / 60), 4, '.', '') . 'm';
                break;
        }
    }
}

if (!function_exists('debug')) {
    function debug($data) {
        return \Jkd\JkdResponse::Debug($data);
    }
}