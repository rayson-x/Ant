<?php
namespace Ant\Database\Connector;

use Ant\Database\Connector;

class Mysql extends Connector{

    protected $identifierSymbol = '`';

    public function lastId()
    {
        return $this->connect()->lastInsertId();
    }

    public function getTables()
    {
        return $this->select('information_schema.TABLES')
                    ->setColumns('TABLE_NAME')
                    ->where('TABLE_SCHEMA = database()')
                    ->execute()
                    ->getCols();
    }

    public function getColumns($table)
    {
        // TODO: Implement getColumns() method.
    }

    public function getIndexes()
    {
        // TODO: Implement getIndexes() method.
    }
}