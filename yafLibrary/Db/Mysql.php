<?php
/**
 * mysql操作类
 */

namespace Db;

use Log\JkdLog;

class Mysql extends MysqlHandle
{

    private static $_instances;

    private function __construct($dbhost, $dbport, $username, $password, $dbname, $dbcharset)
    {
        try {
            $this->_dbh = new \PDO('mysql:dbname=' . $dbname . ';host=' . $dbhost . ';port=' . $dbport . ';charset=' . $dbcharset, $username, $password, [\PDO::ATTR_PERSISTENT => true, \PDO::ATTR_EMULATE_PREPARES  => false]);
        } catch (\PDOException $e) {
            JkdLog::error($e->getMessage());
            // 常驻内存下不能 die()（会终止整个 Worker 进程），改为抛出异常由统一错误处理返回 500
            throw $e;
        }
    }


    static public function getInstance($db_config = '')
    {
        $_db_host = $db_config->host;
        $_db_port = $db_config->port;
        $_db_name = $db_config->dbname;
        $_db_charset = $db_config->charset;
        $_db_usr = $db_config->username;
        $_db_pwd = $db_config->password;

        self::$_instances = new Mysql($_db_host, $_db_port, $_db_usr, $_db_pwd, $_db_name, $_db_charset);
        return self::$_instances;
    }

}
