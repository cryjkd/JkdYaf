<?php
/**
 * 配置类
 */
namespace Conf;

class JkdConf
{
    /**
     * 已解析配置缓存（避免重复解析 ini 文件）
     *
     * @var array
     */
    private static $cache = [];

    /**
     * 获取配置
     *
     * @param string $name 配置文件名称
     * @param false $isEnv 是否区分环境
     * @return false|\Yaf\Config\Ini
     */
    public static function get($name, $isEnv = true)
    {
        if (!$name)
            return false;

        $list = explode('.', $name);
        $fileName = $list[0];
        $key = $list[1] ?? '';

        $configFile = CONF_PATH . '/' . $fileName . '.ini';
        if (!is_file($configFile)) {
            return false;
        }

        if (!isset(self::$cache[$configFile])) {
            self::$cache[$configFile] = new \Yaf\Config\Ini($configFile);
        }
        $config = self::$cache[$configFile];
        if ($isEnv != true) {
            if ($key) {
                return $config->get($key);
            } else {
                return $config;
            }
        } else {
            if ($key) {
                return $config[environ()]->get($key);
            } else {
                return $config[environ()];
            }
        }
    }


}
