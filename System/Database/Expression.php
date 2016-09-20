<?php
namespace Ant\Database;

/**
 * 免除SQL语句构造过程中的逃逸处理
 *
 * E.T:
 * 错误：select `count(1)` from `foobar`
 * $select = $db->select('foobar')->setColumns('count(1)');
 *
 * 正确：select count(1) from `foobar`
 * $select = $db->select('foobar')->setColumns(new Expr('count(1)'));
 *
 * Class Expression
 * @package Ant\Database
 */
class Expression
{
    protected $value;

    public function __construct($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function __toString()
    {
        return (string) $this->getValue();
    }
}
