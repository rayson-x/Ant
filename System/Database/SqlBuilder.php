<?php
namespace Ant\Database;

use Ant\Exception;
//查询表达式生成器
class SqlBuilder{
    //连接器实例
    protected $connector;
    //数据表
    protected $table;
    //数据表别名
    protected $as;
    //查询条件
    protected $where = [];
    //查询的字段
    protected $columns = [];
    //联合查询
    protected $join = [];
    //分组条件
    protected $groupBy = [];
    //排序条件
    protected $orderBy = [];
    //单次查询数量
    protected $limit = 0;
    //语句参数
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

    /**
     * 析构函数，断开连接
     */
    public function __destruct()
    {
        $this->connector = null;
    }

    public function getTable(){
        return $this->as ? $this->table." as ".$this->as : $this->table;
    }


    /**
     * @param $where
     * @param null $params
     * @return SqlBuilder
     * @throws Exception
     *
     * @example
     * 支持的语法类型,待添加,闭包嵌套
     * // select * from `student` where `age` > '18'
     * where( 'age > 18' )
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
        //因为执行语句为预处理方式，所以要保证条件最后格式必须为 'foo = ?','123'
        $params = ($params === null)
            ? []
            : ( is_array($params) ? $params : array_slice(func_get_args(),1) );

        return $this->parseWhereExp($where,$params, 'AND');
    }

    public function orWhere(){

    }

    public function whereIn()
    {

    }

    public function whereNotIn()
    {

    }

    public function whereExists()
    {

    }

    /**
     * @param $expressions
     * @return $this
     *
     * @param $expressions
     *
     * 支持的语法
     * order('foo,name,baz desc');
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
                //加上引号
                $column = $this->connector->quoteIdentifier($expression);
                $sort = 'ASC';
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

    /**
     * @param $as
     * as语句
     */
    public function alias($as)
    {
        $this->as = $as;
    }

    public function select($params)
    {
        $this->columns = is_array($params) ? $params : func_get_args();

        return $this;
    }

    /**
     * @param $sql
     * @param string $type
     * 支持闭包Closure
     *
     * alias('a')->join('table_b as b','a.id','=','b.id'')
     * alias('a')->join('table_b as b',function($join){
     *     $join->on('a.id','>','b.id')->orOn(....);
     * })
     * join('table_b',function($join){
     *     $join->
     * })
     */
    public function join($table, $one, $operator = null, $two = null, $type = 'inner')
    {
        //检查是否闭包
        if($one instanceof \Closure){
            $join = new JoinClause($table,$type);
            call_user_func($one,$join);

        }else{

        }
    }

    public function union()
    {

    }

    public function get()
    {
        list($sql,$params) = $this->compile();

        return $this->connector->execute($sql,$params);
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
        $sql = 'SELECT * FROM '.$this->connector->quoteIdentifier($this->getTable());

        $mysql = 'SELECT %COLUMN% FROM %TABLE% %JOIN% %%WHERE% %GROUP% %HAVING% %ORDER% %LIMIT% %UNION%';
        list($where,$params) = $this->compileWhere();

        if($where)
            $sql .= ' WHERE '.$where;


        if ($this->orderBy)
            $sql .= ' ORDER BY '.implode(', ', $this->orderBy);

        return [$sql,$params];
    }

    /**
     * where in 子查询语句.
     *
     * @param string       $column
     * @param array|Select $relation
     * @param bool         $in
     *
     * @return $this
     */
    protected function whereSub($column,$relation,$in)
    {
        if($relation instanceof SqlBuilder){

        }
    }

    /**
     * 解析where条件
     * @param $where
     * @param $params
     * @param $expr
     * @return $this
     * @throws Exception
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
                while(list($field,$logic) = each($where)){
                    //预处理语句数量跟参数数量是否匹配
                    if(!current($params)) throw new Exception('参数不足');
                    //添加引号
                    $field = $this->connector->quoteIdentifier($field);
                    $param = $this->connector->quote(current($params));

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
     * 把查询条件参数编译为where子句.
     *
     * @return array
     * array(
     *     (string),    // where 子句
     *     (array),     // 查询参数
     * )
     */
    protected function compileWhere()
    {
        if(!$this->where){
            return ['',[]];
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
                ? $where = implode(' '.$logic.' ', $sql)
                : $where = $where.' '.$logic.' '.implode(' '.$logic.' ', $sql);
        }

        return [$where,$params];
    }



}