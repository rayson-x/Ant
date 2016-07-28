<?php
namespace Ant\Database;
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
    protected $groupBy;
    //排序条件
    protected $orderBy;
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

    }


    /**
     * @param $where
     * @param null $params
     * @param $expr
     *
     * 支持的语法类型
     * where( "$field > $value" )
     * where( [$field => $value] )
     * where( "$field = ?",$value )
     */
    public function where($where,$params = null,$expr = 'AND')
    {
        $params = $params === null
            ? []
            : is_array($params) ? $params : array_slice(func_get_args(), 1);

        if(is_array($where) && $params === []){
            foreach($where as $key => $value){
                $this->where[] = ["$key = ?",$value];
            }
        }else{
            $this->where[] = [$where,$params];
        }

        return $this;
    }

    public function whereIn()
    {

    }

    /**
     * @param $expressions
     *
     * 支持的语法
     * order('foo,name,baz desc');
     * order('foe','name','baz asc');
     * order('foo','name','baz','desc');
     * order(['foo'=>'desc','asd'=>'asc']);
     */
    public function order($expressions)
    {
        $expressions = is_array($expressions) ? $expressions : func_get_args();
        $orderBy = [];
        while(list($key,$expression) = each($expressions)){
            if (is_numeric($key)) {
                $column = $expression;
                $sort = 'ASC';
            } else {
                $column = $key;
                $sort = $expression;
            }

            $sort = (strtoupper($sort) === 'DESC') ? 'DESC' : '';
            $orderBy[] = $sort ? $column.' '.$sort : $column;

            if(!current($expressions)){
                if(array_search(strtoupper(trim(end($orderBy))),['ASC','DESC'])){
                    $sort = strtoupper(trim(array_pop($orderBy)));
                    $orderBy[] = array_pop($orderBy).' '.$sort;
                }
                break;
            }
        }

        array_push($this->orderBy,$orderBy);
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
     * 把查询条件参数编译为where子句.
     *
     * @return
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

        $where = $params = [];
        foreach($this->where as $condition){
            list($whereSql , $whereParams) = $condition;

            $where[] = $whereSql;
            if($whereParams){
                $params = array_merge($params, $whereParams);
            }
        }
        $where = '('.implode(') AND (', $where).')';

        return [$where,$params];
    }



}