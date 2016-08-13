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

    /* 支持直接使用PDO函数 */
    public function __call($method, array $args) {
        return $args
            ? call_user_func_array([$this->connect(), $method], $args)
            : $this->connect()->$method();
    }

    /* 序列化时断开连接 */
    public function __sleep()
    {
        $this->disconnect();
    }

    /* 程序结束断开连接,减少损耗 */
    public function __destruct()
    {
//        $this->disconnect();
    }

    /* 连接数据库 */
    public function connect()
    {
        if($this->isConnected()){
            return $this->con;
        }

        $dsn        = $this->getConfig('dsn');
        $user       = $this->getConfig('user') ?: null;
        $password   = $this->getConfig('password') ?: null;
        $options    = $this->getConfig('options') ?: [];

        //配置PDO
        $options[\PDO::ATTR_CASE] = isset($options[\PDO::ATTR_CASE]) ? : \PDO::CASE_LOWER;
        $options[\PDO::ATTR_ERRMODE] = isset($options[\PDO::ATTR_ERRMODE]) ? : \PDO::ERRMODE_EXCEPTION;
        $options[\PDO::ATTR_STATEMENT_CLASS] = isset($options[\PDO::ATTR_STATEMENT_CLASS]) ? : ['Ant\\Database\\Statement'];
        $options[\PDO::ATTR_DEFAULT_FETCH_MODE] = isset($options[\PDO::ATTR_DEFAULT_FETCH_MODE]) ? : \PDO::FETCH_ASSOC;

        try{
            $con = new \PDO($dsn,$user,$password,$options);

            //此处需要添加日志功能
        }catch(\PDOException $e){
            //同上
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
            $this->rollbackAll();
            $this->con = null;
        }
        return $this;
    }

    public function rollbackAll(){
        $max = 9;
        while($this->inTransaction() && $max-->0){
            $this->rollback();
        }
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

    /**
     * 为SQL语句中的字符串添加引号
     * @param $value
     * @return array|string
     * @throws Exception
     */
    public function quote($value)
    {
        if (is_array($value))
            return array_map([$this, 'quote'], $value);


        if ($value instanceof Expression) {
            return $value;
        }

        if ($value === null) {
            return 'NULL';
        }

        //PDO::quote 返回一个带引号的字符串，理论上可以安全的传递到SQL语句中并执行。如果该驱动程序不支持则返回FALSE
        return $this->connect()->quote($value);
    }

    public function quoteIdentifier($identifier)
    {
        if (is_array($identifier)) {
            return array_map([$this, 'quoteIdentifier'], $identifier);
        }

        if ($identifier instanceof Expression) {
            return $identifier;
        }
        $symbol = '`';
        $identifier = str_replace(['"', "'", ';', $symbol], '', $identifier);

        $result = [];
        foreach (explode('.', $identifier) as $s) {
            $result[] = $symbol.$s.$symbol;
        }

        return new Expression(implode('.', $result));
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

    /* 使用查询生成器 */
    public function table($table)
    {
        if(!($this->builder[$table] instanceof SqlBuilder)){
            $this->builder[$table] = new SqlBuilder($this,$table);
        }
        return $this->builder[$table];
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

    /* 只负责预处理,不返回执行结果 */
    public function execute($sql,$bind = [])
    {
        try{
            $stat = $sql instanceof \PDOStatement
                ? $sql
                : ($this->connect()->prepare($sql));

            $stat->execute($bind);
        }catch(\PDOException $e){
            throw new Exception($e->getMessage());
        }

        return $stat;
    }



}