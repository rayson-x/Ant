<?php
namespace Ant\Database;

use \Closure;
use Ant\Exception;
use \UnexpectedValueException;
use \InvalidArgumentException;

class SqlBuilder{
    /**
     * 连接器实例
     *
     * @var Connector
     */
    protected $connector;
    /**
     * 数据表
     *
     * @var string|Closure|Expression
     */
    protected $table;
    /**
     * 查询条件
     *
     * @var array
     */
    protected $where = [];
    /**
     * 查询的字段
     *
     * @var array
     */
    protected $columns = [];
    /**
     * 联合查询
     *
     * @var array
     */
    protected $join = [];
    /**
     * 分组条件
     *
     * @var array
     */
    protected $groupBy = [];
    /**
     * 排序条件
     *
     * @var array
     */
    protected $orderBy = [];
    /**
     * 预处理参数
     *
     * @var array
     */
    protected $params = [];
    /**
     * 单次查询数量
     *
     * @var int
     */
    protected $limit = 0;
    /**
     * 语句参数
     *
     * @var int
     */
    protected $offset = 0;

    /**
     * @param Connector $connector  连接器实例
     * @param $table
     */
    public function __construct(Connector $connector,$table)
    {
        $this->connector = $connector;
        $this->table = $table;
    }

    public function __toString()
    {
        list($sql) = $this->compile();
        return $sql;
    }

    public function __clone()
    {
        $this->free();

        return $this;
    }

    /**
     * 析构函数，断开连接
     */
    public function __destruct()
    {
        $this->connector = null;
    }

    /**
     * @param $params
     * @return $this
     *
     * @example
     * setColumns(['foo','bar'])
     * setColumns('foo','bar')
     */
    public function setColumns($params)
    {
        $this->columns = is_array($params) ? $params : func_get_args();

        return $this;
    }

    /**
     * 设置查询数据表
     *
     * @param $table string|Closure|Expression
     * @return $this
     */
    public function setFrom($table)
    {
        if($table instanceof Closure){
            $this->table = $this->subQuery($table);
        }else{
            $this->table = $table;
        }

        return $this;
    }

    /**
     * @param $where
     * @param null $params
     * @return SqlBuilder
     * @throws Exception
     *
     * @example
     * 支持的以下语法类型
     * // select * from `student` where `age` > '18'
     * where( '`age` > '18' ' )
     *
     * // select * from `student` where `score` = '80'
     * where( ['score' => '80'] )
     *
     * // select * from `student` where `score` > '80' and `age` > '18'
     * where( '`score` > ? and `age` > ? ', 80 , 18 )
     *
     * // select * from `student` where `name` in ('alex','ben','annie') and `age` <> '18'
     * where( [ 'name'=>'in' , 'age'=>'<>' ] , [ [ 'alex','ben','annie' ] , '18' ] )
     */
    public function where($where,$params = null)
    {
        $params = ($params === null)
            ? []
            : ( is_array($params) ? $params : array_slice(func_get_args(),1) );

        return $this->parseWhereExp($where,$params, 'AND');
    }

    /**
     * @param $where
     * @param null $params
     * @return SqlBuilder
     * @throws Exception
     *
     * @example
     * 同上,只不过将条件表达式变为了OR
     */
    public function orWhere($where,$params = null)
    {
        $params = ($params === null)
            ? []
            : ( is_array($params) ? $params : array_slice(func_get_args(),1) );

        return $this->parseWhereExp($where,$params, 'OR');
    }

    /**
     * @param $column string
     * @param $logic string
     * @param Closure $func
     * @return $this
     *
     * @example
     * 闭包嵌套
     * //SELECT * FROM `demo` WHERE `id` IN (SELECT `id` FROM `users` WHERE `name` = "powers")
     * whereSub('id','IN',function(){
     *     $this->setFrom = 'users';
     *     $this->setColumns('id')->where(['name'=>'power']);
     *  })
     */
    public function whereSub($column,$logic,$func)
    {
        list($sql,$params) = $this->subQuery($func)->compile();

        $this->where['AND'][] = ["$column $logic ({$sql})",$params];

        return $this;
    }

    /**
     * @param $expressions
     * @return $this
     *
     * 支持的语法
     * order('foo','name','baz','desc');
     * order(['foo'=>'desc','asd'=>'asc']);
     */
    public function order($expressions)
    {
        //获取条件
        $expressions = is_array($expressions) ? $expressions : func_get_args();
        $orderBy = [];
        foreach($expressions as $key => $expression){
            if (is_numeric($key)) {
                if(array_search(strtoupper($expression),['ASC','DESC']) !== false){
                    $orderBy[] = array_pop($orderBy).$expression;
                    continue;
                }

                $column = $this->connector->quoteIdentifier($expression);
                $sort = '';
            } else {
                $column = $this->connector->quoteIdentifier($key);
                $sort = $expression;
            }

            $sort = (strtoupper($sort) === 'DESC') ? 'DESC' : '';

            $orderBy[] = $column.' '.$sort;
        }

        $this->orderBy = array_merge($this->orderBy,$orderBy);

        return $this;
    }

    public function group()
    {

    }

    public function join()
    {
    }

    public function union()
    {

    }

    //TODO::mysql聚合函数

    public function execute()
    {
        list($sql, $params) = $this->compile();

        return $this->connector->execute($sql, $params);
    }

    public function get()
    {
        $stat = $this->execute();

        //TODO::预处理函数
        $rows = [];
        while($row = $stat->fetch()){
            $rows[] = $row;
        }

        return $rows;
    }

    public function update()
    {

    }

    public function insert()
    {

    }

    public function delete()
    {

    }

    /* 编译sql语句 */
    public function compile()
    {
        $sql = str_replace(
            ['%COLUMN%',' %TABLE%','%JOIN%','%WHERE%','%GROUP%','%HAVING%','%ORDER%','%LIMIT%','%UNION%'],
            [
                $this->compileColumn(),
                $this->compileFrom(),
                '',
                $this->compileWhere(),
                '',
                '',
                $this->compileOrder(),
                '',
                '',
            ],
            'SELECT %COLUMN% FROM %TABLE%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT%%UNION%');

        return [$sql,$this->params];
    }

    /**
     * 编译sql子查询
     *
     * @param $func Closure
     * @param array $args
     * @return SqlBuilder
     */
    public function subQuery($func,$args = []){
        if(!$func instanceof Closure){
            throw new InvalidArgumentException('Function must be a Closure');
        }
        $sqlBuilder = clone $this;

        call_user_func_array($func->bindTo($sqlBuilder,$sqlBuilder),$args);

        return $sqlBuilder;
    }

    /**
     * 释放当前sql
     */
    public function free(){
        $this->where = [];
        $this->columns = [];
        $this->join = [];
        $this->groupBy = [];
        $this->params = [];
        $this->limit = 0;
        $this->offset = 0;
    }

    /**
     * 解析where条件
     *
     * @param $where
     * @param $params
     * @param $expr
     * @return $this
     * @throws InvalidArgumentException
     */
    protected function parseWhereExp($where,$params,$expr){
        if(is_array($where)){
            //将条件表达式进行匹配
            if($params === []){
                foreach($where as $key => $value){
                    $key = $this->connector->quoteIdentifier($key);
                    //添加到条件数组,等待执行
                    $this->where[$expr][] = ["$key = ?",[$value]];
                }
            }else{
                //TODO::待优化
                while(list($field,$logic) = each($where)){
                    //预处理语句数量跟参数数量是否匹配
                    if(false === $param = current($params)){
                        throw new UnexpectedValueException('Lack of prepare parameters');
                    }
                    $field = $this->connector->quoteIdentifier($field);
                    $param = $this->connector->quote($param);

                    //将数组参数转为为字符串
                    $param = !is_array($param)
                        ? $param
                        : implode(' , ',$param);

                    $logic = strtoupper($logic);
                    $this->where[$expr][] = ["$field $logic ( $param )",[]];

                    next($params);
                }
            }
        }else{
            $this->where[$expr][] = [$where,$params];
        }

        return $this;
    }

    /**
     * 处理字段
     *
     * @return string
     */
    protected function compileColumn(){
        if(empty($this->columns)){
            return '*';
        }

        return implode(',',$this->connector->quoteIdentifier($this->columns));
    }

    /**
     * 处理表
     *
     * @return string
     */
    protected function compileFrom()
    {
        if($this->table instanceof self){
            list($sql,$params) = $this->table->compile();
            $table = sprintf('(%s) AS %s', $sql, $this->connector->quoteIdentifier(uniqid()));
            $this->params = array_merge($this->params,$params);
        }elseif($this->table instanceof Expression){
            $table = (string) $this->table;
        }else{
            $table = $this->connector->quoteIdentifier($this->table);
        }

        return " $table";
    }

    /**
     * 把查询条件参数编译为where子句.
     *
     * @return string (where 子句)
     */
    protected function compileWhere()
    {
        if(!$this->where){
            return '';
        }

        $where = null;
        $params = [];

        foreach($this->where as $logic => $value){
            //获取AND跟OR条件,并且将它们拼接为SQL语句
            $sql = [];
            $logic = strtoupper($logic);

            foreach($value as $condition){
                //获取条件
                list($whereSql , $whereParams) = $condition;

                $sql[] = $whereSql;

                if($whereParams){
                    $params = array_merge($params, $whereParams);
                }
            }

            //开始拼接
            $where === null
                ? $where = implode(" {$logic} ", $sql)
                : $where = $where." {$logic} ".implode(" {$logic} ", $sql);
        }
        $this->params = $params;

        return " WHERE {$where}";
    }

    protected function compileOrder(){
        return empty($this->orderBy)
            ? ''
            : ' ORDER BY '.implode(' , ', $this->orderBy);
    }
}