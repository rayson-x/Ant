<?php
namespace Ant\Database;

use PDO;
use PDOStatement;
use PDOException;
use Exception;
use InvalidArgumentException;

abstract class Connector{
    //链接实例
    protected $connect;
    //事务状态
    protected $inTransaction;
    //标识符
    protected $identifierSymbol = '`';
    //查询生成器实例
    protected $builder;
    //配置信息
    protected $config = [];
    //是否开启日志
    protected $supportLog = false;
    //日志驱动
    protected $log = '';

    protected $options = [
        PDO::ATTR_CASE               => PDO::CASE_NATURAL,
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_STATEMENT_CLASS    => ['Ant\\Database\\Statement'],
    ];

    /**
     * 获取最后一个自增ID
     *
     * @return mixed
     */
    abstract public function lastId();

    /**
     * 获取所有数据表信息
     *
     * @return mixed
     */
    abstract public function getTables();

    /**
     * 获取一个表的字段属性
     *
     * @return mixed
     */
    abstract public function getColumns($table);

    /**
     * 获取表索引
     *
     * @return mixed
     */
    abstract public function getIndexes();

    /**
     * Connector constructor.
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        if(empty($config['dsn'])){
            throw new InvalidArgumentException('Invalid database config, require "dsn" key.');
        }
        $this->config = $config;
    }

    /**
     * 调用PDO函数
     *
     * @param $method
     * @param array $args
     * @return mixed
     * @throws Exception
     */
    public function __call($method, array $args) {
        return $args
            ? call_user_func_array([$this->connect(), $method], $args)
            : $this->connect()->$method();
    }

    /**
     * 序列化时断开
     */
    public function __sleep()
    {
        $this->disconnect();
    }

    /**
     * 断开连接
     */
    public function __destruct()
    {
        $this->disconnect();
    }

    /**
     * 连接数据库
     *
     * @return PDO
     * @throws Exception
     */
    public function connect()
    {
        if($this->isConnected()){
            return $this->connect;
        }

        $dsn        = $this->getConfig('dsn');
        $user       = $this->getConfig('user') ?: null;
        $password   = $this->getConfig('password') ?: null;
        $options    = $this->getConfig('options') ?: [];

        //设置PDO默认参数
        $options = array_merge($this->options,$options);

        //TODO::日志,记录连接
        try{
            $connect = new PDO($dsn,$user,$password,$options);
        }catch(PDOException $e){
            throw new Exception($e);
        }

        return $this->connect = $connect;
    }

    /**
     * 检查是否连接
     *
     * @return bool
     */
    public function isConnected()
    {
        return $this->connect instanceof PDO;
    }

    /**
     * 断开连接
     *
     * @return $this
     */
    public function disconnect()
    {
        if($this->isConnected()){
            $this->rollbackAll();
            $this->connect = null;
        }
        return $this;
    }

    /**
     * 回滚所有事务
     */
    public function rollbackAll()
    {
        //TODO::取缔PDO事务
        $max = 9;
        while($this->inTransaction() && $max-->0){
            $this->rollback();
        }
    }

    /**
     * 为SQL语句中的字符串添加引号
     *
     * @param $value
     * @return array|string
     * @throws Exception
     */
    public function quote($value)
    {
        if (is_array($value)){
            return array_map([$this, 'quote'], $value);
        }

        if ($value instanceof Expression) {
            return $value;
        }

        if ($value === null) {
            return 'NULL';
        }

        return $this->connect()->quote($value);
    }

    /**
     * 添加数据库标识符
     *
     * @param $identifier
     * @return Expression|array|mixed
     */
    public function quoteIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return array_map([$this, 'quoteIdentifier'], $identifier);
        }

        if ($identifier instanceof Expression) {
            return $identifier;
        }

        $symbol = $this->identifierSymbol;
        $identifier = str_replace(['"', "'", ';', $symbol], '', $identifier);

        $result = [];
        foreach (explode('.', $identifier) as $s) {
            $result[] = $symbol.$s.$symbol;
        }

        return new Expression(implode('.', $result));
    }

    /**
     * 获取配置信息
     *
     * @param null $key
     * @return array|bool
     */
    public function getConfig($key = null)
    {
        if($key === null){
            return $this->config;
        }

        return isset($this->config[$key])
                ? $this->config[$key]
                : false;
    }

    /**
     * 选择sql生成器,一个表对应一个实例
     *
     * @param $table string
     * @return SqlBuilder
     */
    public function select($table)
    {
        if(!($this->builder[$table] instanceof SqlBuilder)){
            $this->builder[$table] = new SqlBuilder($this,$table);
        }

        return $this->builder[$table];
    }

    /**
     * 只负责预处理,不返回执行结果
     *
     * @param $sql
     * @param array $bind
     * @return PDOStatement|Statement
     * @throws Exception
     */
    public function execute($sql,$bind = [])
    {
        try{
            $stat = $sql instanceof PDOStatement
                ? $sql
                : ($this->connect()->prepare($sql));

            $stat->execute($bind);

            return $stat;
        }catch(PDOException $e){
            throw new Exception($e->getMessage());
        }
    }

    /* 使用日志 */
    protected function log($content)
    {
        //TODO::日志功能
    }
}