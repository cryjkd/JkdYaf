<?php
/**
 * mysql操作类
 */

namespace Db;

use Log\JkdLog;

class MysqlHandle implements DbInterface
{

    private static $_instances;
    public $_dbh;
    private $_sth;
    private $_sql;

    static public function getInstance()
    {
    }


    function halt($msg = '', $sql = '')
    {
        $error_info = $this->_sth->errorInfo();
        $s = '<pre>';
        $s .= '<b>Error:</b>' . $error_info[2] . '<br />';
        $s .= '<b>Errno:</b>' . $error_info[1] . '<br />';
        $s .= '<b>Sql:</b>' . $this->_sql;

        JkdLog::error($s);
        die();
    }


    function execute($sql, $values = array())
    {
        $this->_sql = $sql;
        $this->_sth = $this->_dbh->prepare($sql);
        $bool = $this->_sth->execute($values);

        if ('00000' !== $this->_sth->errorCode()) {
            $this->halt();
        }

        // 储存Sql操作记录
        if (checkIoStatus('sqlLogStatus')) {
            \Task\JkdTask::dispatch(\Job\JkdSqlLog::class, ['sqlStr' => $sql, 'values' => $values]);
        }
        return $bool;
    }


    /**
     * 获取全部数据
     *
     * @param $sql
     * @param array $values
     * @param int $fetch_style
     * @return mixed
     */
    function getAll($sql, $values = array(), $fetch_style = \PDO::FETCH_ASSOC)
    {
        $this->execute($sql, $values);
        return $this->_sth->fetchAll($fetch_style);
    }


    /**
     * 获取指定字段
     *
     * @param $sql
     * @param array $params
     * @param int $column_number
     * @return array
     */
    function getCol($sql, $params = array(), $column_number = 0)
    {
        $columns = array();
        $results = array();
        $this->execute($sql, $params);
        $results = $this->_sth->fetchAll(\PDO::FETCH_NUM);
        foreach ($results as $result) {
            $columns[] = $result[$column_number];
        }
        return $columns;
    }


    /**
     * 获取一条数据
     *
     * @param $sql
     * @param array $values
     * @param int $fetch_style
     * @return mixed
     */
    function getRow($sql, $values = array(), $fetch_style = \PDO::FETCH_ASSOC)
    {
        $this->execute($sql, $values);
        return $this->_sth->fetch($fetch_style);
    }


    /**
     * 获取单个字段数据
     *
     * @param $sql
     * @param array $values
     * @param int $column_number
     * @return mixed
     */
    function getOne($sql, $values = array(), $column_number = 0)
    {
        $this->execute($sql, $values);
        return $this->_sth->fetchColumn($column_number);
    }


    /**
     * 新增
     *
     * @param string $table
     * @param array $data
     * @return bool|string
     */
    function insert($table, $data)
    {
        $fields = array_keys($data);
        $marks = array_fill(0, count($fields), '?');

        $sql = "INSERT INTO $table (`" . implode('`,`', $fields) . "`) VALUES (" . implode(", ", $marks) . " )";
        $this->execute($sql, array_values($data));
        $lastInsertId = $this->_dbh->lastInsertId();
        if ($lastInsertId)
            return $lastInsertId;
        else
            return true;
    }


    /**
     * 处理事务
     */
    function transaction($sql)
    {
        try {
            $this->_dbh->beginTransaction();
            $this->_dbh->exec($sql);
            $this->_dbh->commit();
        } catch (\PDOException $ex) {
            $this->_dbh->rollBack();
        }
    }


    /**
     * 更新
     *
     * @param string $table
     * @param array $data
     * @param array $where
     * @return bool
     */
    function update($table, $data, $where)
    {
        $values = $bits = $wheres = [];
        foreach ($data as $k => $v) {
            $bits[] = "`$k` = ?";
            $values[] = $v;
        }

        foreach ($where as $c => $v) {
            $wheres[] = "$c = ?";
            $values[] = $v;
        }

        $sql = "UPDATE $table SET " . implode(', ', $bits) . ' WHERE ' . implode(' AND ', $wheres);
        return $this->execute($sql, $values);
    }


    /**
     * 删除
     *
     * @param $table
     * @param $where
     * @return bool
     */
    function delete($table, $where)
    {
        $values = $wheres = array();
        foreach ($where as $key => $val) {
            $wheres[] = "$key = ?";
            $values[] = $val;
        }

        $sql = "DELETE FROM $table WHERE " . implode(' AND ', $wheres);
        return $this->execute($sql, $values);
    }


    /**
     * 关闭连接
     */
    function close()
    {
        unset($this->_instances);
        unset($this->_dbh);
    }

}
