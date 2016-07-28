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
        return $this->as ? $this->table." as ".$this->as : $this->table;
    }


    /**
     * @param $where
     * @param null $params
     * @param string $expr
     * @return $this
     * @throws Exception
     *
     * @example
     * 支持的语法类型,待添加,闭包嵌套
     * // select * from foobar where foo>123
     * where( 'foo > 123' )
     *
     * // select * from foobar where foo = 123
     * where( ['foo' => '123'] )
     *
     * // select * from foobar where foo in (a,b,c) and bar <> asd
     * where(['foo'=>'in','bar'=>'<>'],[['a','b','c'],'asd'])
     *
     * // select * from foobar foo = 123 AND bar = 456
     * where( 'foo = ? AND bar = ?',123 456 )
     */
    public function where($where,$params = null,$expr = 'AND')
    {
        //因为执行语句为预处理方式，所以要保证条件最后格式必须为 'foo = ?','123'
        $params = $params === null
            ? []
            : is_array($params) ? $params : array_slice(func_get_args(), 1);

        if(is_array($where)){
            //将条件表达式进行匹配
            if($params === []){
                foreach($where as $key => $value){
                    $this->where[] = ["$key = ?",[$value],$expr];
                }
            }else{
                /* 本来想用foreach结果发现有点麻烦，于是为了装逼就写了这个简短的遍历 */
                while(list($key,$value) = each($where)){
                    if(!current($params)) throw new Exception('参数不足');
                    $param = !is_array(current($params))
                           ? current($params)
                           : implode(' , ',current($params));//添加分号，测试中，最后是否在此添加功能，由逻辑复杂程度决定

                    if('IN' === strtoupper($value)){
                        $this->where[] = [sprintf('%s IN (%s)', $key, $param),[]];
                    }else{
                        $this->where[] = ["$key $value ?",[$param]];
                    }
                    next($params);
                }
            }
        }else{
            $this->where[] = [$where,$params,$expr];
        }

        return $this;
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
        list($sql,$params) = $this->compile();

        $this->connector->execute($sql,$params);
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
        $sql = 'SELECT * FROM '.$this->connector->quote($this->getTable());

        list($where,$params) = $this->compileWhere();
        if($where){
            $sql .= ' WHERE '.$where;
        }
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