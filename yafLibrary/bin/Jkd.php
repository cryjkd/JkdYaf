<?php

include __DIR__ . "/ConsoleTable.php";

class Jkd
{

    const RED_FONT = '0;31';
    const GREEN_FONT = '0;32';
    const YELLOW_FONT = '0;33';
    const BLUE_FONT = '0;34';
    const LIGHT_BLUE_FONT = '1;34';
    const WHITE_FONT = '1;37';

    /**
     * 设置颜色
     *
     * @param $str
     * @param $color
     * @return string
     */
    private static function setFont($str, $color)
    {
        return "\033[" . $color . "m" . $str . "\033[0m";
    }

    public static function start($ip, $port, $daemonize)
    {
        self::getTitle();
        $str = 'JkdYaf Start Success' . PHP_EOL;
        $str = self::setFont($str, self::GREEN_FONT);
        echo $str;
        self::getComponents($ip, $port, $daemonize);
//        echo '[' . date('Y-m-d H:i:s') . '] [TRACE] Swoole is running, see "ps -ef|grep JkdYaf".' . PHP_EOL;
        if ($daemonize) {
            echo '[' . date('Y-m-d H:i:s') . '] [TRACE] Swoole is running in daemon mode, see "ps -ef|grep JkdYaf".' . PHP_EOL;
        } else {
            echo 'Swoole is running, press Ctrl+C to quit.' . PHP_EOL;
        }
    }

    public static function echoStr($str, $type = 1)
    {
        if ($type == 1) {
            $color = self::GREEN_FONT;
        } elseif ($type == 2) {
            $color = self::YELLOW_FONT;
        } else {
            $color = self::RED_FONT;
        }
        echo self::setFont($str . PHP_EOL, $color);
        return true;
    }

    private static function getTitle()
    {
        $str = '
       ____ __ ______  _____    ______
      / / //_// __ \ \/ /   |  / ____/
 __  / / ,<  / / / /\  / /| | / /_
/ /_/ / /| |/ /_/ / / / ___ |/ __/
\____/_/ |_/_____/ /_/_/  |_/_/
        ' . PHP_EOL;

        echo self::setFont($str, self::GREEN_FONT);;
    }


    private static function getComponents($ip, $port, $daemonize)
    {
        $phpVer = PHP_VERSION;  //php版本
        $swooleVer = SWOOLE_VERSION;    //swoole版本
        $yafVer = Yaf\VERSION;  //yaf版本
        $yacVer = phpversion('yac');    //yac版本
        $jkdYarVer = JKDYAF_VERSION;    //JkdYaf版本

        $str1 = self::setFont('>>> Components', self::YELLOW_FONT);
        echo $str1 . PHP_EOL;
        $list = [
            ['PHP', $phpVer ?? '', '7.0 +'],
            ['Swoole', $swooleVer ?? '', '4.5 +'],
            ['YAF', $yafVer ?? '', '3.3 +'],
        ];
        if ($yacVer) {
            $list[] = ['YAC', $yacVer, '2.3 +'];
        }

        $table = new \ConsoleTable(CONSOLE_TABLE_ALIGN_LEFT, CONSOLE_TABLE_BORDER_ASCII);
        echo $table->fromArray(
            ['Component', 'Version', 'Requirement'],
            $list
        );
        echo PHP_EOL;

        $table = new \ConsoleTable(CONSOLE_TABLE_ALIGN_LEFT, CONSOLE_TABLE_BORDER_ASCII);
        echo $table->fromArray(
            ['Protocol', 'Listen At', 'Daemon Mode', 'Version'],
            [['Main HTTP', $ip . ':' . $port, $daemonize ? 'On' : 'Off', $jkdYarVer]]
        );
        echo PHP_EOL;
    }


    public static function reqMsg($_startTime, $_endTime, $status)
    {
        $serverData = jkdContext()['REQUEST_SERVER'] ?? [];
        $time = changeReqTime($_endTime - $_startTime);
        $num = 10 - mb_strlen($time);
        if ( $num > 0 ) {
            for ($i = 0; $i < $num; $i ++) {
                $time .= ' ';
            }
        }

        $ip = getClientIp() ?: '-';
        $num = 15 - mb_strlen($ip);
        if ( $num > 0 ) {
            for ($i = 0; $i < $num; $i ++) {
                $ip .= ' ';
            }
        }

        $str = '[JkdYaf] ' . date('Y/m/d-H:i:s', $_startTime) .
            ' | ' . self::getStatusTxt($status) .
            ' | ' . $time .
            ' | ' . $ip .
            ' | ' . self::getMethodTxt($serverData['request_method']) .
            ' "' . $serverData['request_uri'] . (isset($serverData['query_string']) ? ('?' . $serverData['query_string']) : '') . '"';
        echo $str . PHP_EOL;
    }


    public static function getMethodTxt($method)
    {
        switch ($method) {
            case 'GET':
                $color = self::BLUE_FONT;
                break;
            case 'POST':
                $color = self::LIGHT_BLUE_FONT;
                break;
            default:
                $color = self::YELLOW_FONT;
                break;
        }

        return self::setFont($method, $color);
    }


    public static function getStatusTxt($status)
    {
        switch ($status) {
            case 200:
                $color = self::GREEN_FONT;
                break;
            case 404:
                $color = self::WHITE_FONT;
                break;
            case 500:
                $color = self::RED_FONT;
                break;
            default:
                $color = self::YELLOW_FONT;
                break;
        }

        return self::setFont($status, $color);
    }

}
