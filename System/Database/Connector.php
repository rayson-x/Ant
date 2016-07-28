<?php
namespace Ant\Database;
//连接数据库
use Ant\Exception;
use PDO;

class Connector{
    //链接实例
    protected $con;
    //事务状态
    protected $inTransaction;
    //查询生成器实例
    protected $builder;
    //配置信息
    protected $config = [];
    //是否开启日志
    protected $supportLog = false;
    //日志驱动
    protected $log = '';

    public function __construct(array $config = [])
    {
        if(empty($config['dsn'])){
            throw new \InvalidArgumentException('Invalid database config, require "dsn" key.');
        }
        $this->config = $config;
    }

    /* 连接数据库 */
    public function connect()
    {
        if($this->isConnected()){
            return $this->con;
        }

        $dsn = $this->getConfig('dsn');
        $user = $this->getConfig('user');
        $password = $this->getConfig('password');
        $options = $this->getConfig('options');

        //配置PDO
        $options[\PDO::ATTR_CASE] = isset($options[\PDO::ATTR_CASE]) ? : \PDO::CASE_LOWER;
        $options[\PDO::ATTR_ERRMODE] = isset($options[\PDO::ATTR_ERRMODE]) ? : \PDO::ERRMODE_EXCEPTION;
        $options[\PDO::ATTR_STATEMENT_CLASS] = isset($options[\PDO::ATTR_STATEMENT_CLASS]) ? : ['Ant\\Database\\Statement'];
        $options[\PDO::ATTR_DEFAULT_FETCH_MODE] = isset($options[\PDO::ATTR_DEFAULT_FETCH_MODE]) ? : \PDO::FETCH_ASSOC;

        try{
            $con = new \PDO($dsn,$user,$password,$options);
        }catch(\PDOException $e){
            throw new Exception($e);
        }

        return $this->con = $con;
    }

    /* 检查是否连接 */
    public function isConnected()
    {
        return $this->con instanceof \PDO;
    }

    /* 断开连接 */
    public function disconnect()
    {
        if($this->isConnected()){
            $max = 9;
            while($this->inTransaction() && $max-->0){
                $this->rollback();
            }
            $this->con = null;
        }

        return $this;
    }

    /* 执行查询语句,返回结果集 */
    public function query($sql, $bind = null)
    {
        $bind = empty($bind)
            ? []
            : is_array($bind) ? $bind : array_slice(func_get_args(),1);

        $stat = $this->execute($sql,$bind);

        return $stat->getAll();
    }

    /* 执行语句,返回影响行数 */
    public function exec($sql,$bind = null)
    {
        $bind = empty($bind)
            ? []
            : is_array($bind) ? $bind : array_slice(func_get_args(),1);

        $stat = $this->execute($sql,$bind);

        return $stat->rowCount();
    }

    /* 只负责预处理,不返回执行结果 */
    protected function execute($sql,$bind = [])
    {
        $stat = $sql instanceof \PDOStatement
            ? $sql
            : $this->connect()->prepare($sql);

        $stat->execute($bind);

        return $stat;
    }

    public function quote(){
        $this->connect()->quote();
    }

    /* 事务操作 */
    public function begin()
    {
        $this->connect()->beginTransaction();
    }

    public function rollback()
    {
        $this->connect()->rollBack();
    }

    public function commit()
    {
        $this->connect()->commit();
    }

    public function inTransaction()
    {
        $this->connect()->inTransaction();
    }

    /* 获取sql生成器实例 */
    public function getBuilder($table = 'database')
    {
        if(!($this->builder[$table] instanceof SqlBuilder)){
            $this->builder[$table] = new SqlBuilder($this);
        }
    }

    /* 获取配置信息 */
    public function getConfig($key = null)
    {
        if($key === null){
            return $this->config;
        }
        return isset($this->config[$key])
                ? $this->config[$key]
                : false;
    }

    /* 使用日志 */
    protected function log($content)
    {
        if($this->supportLog){
            if($log = new $this->log instanceof \Ant\logInterface){
                $log->save($content);
            }else{
                throw new \Exception($this->log." Not using logInterface");
            }
        }
    }


    /* 序列化时断开连接 */
    public function __sleep()
    {
        $this->disconnect();
    }

    public function __call($method,array $args)
    {
        //使用查询生成器前必须指定表名
        $this->getBuilder();
        return call_user_func_array([$this->builder['database'],$method],$args);
    }
}