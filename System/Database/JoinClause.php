<?php
namespace Ant\Database;

use Closure;

class JoinClause{

    protected $table;

    protected $type;

    public function __construct($table,$type){
        $this->table = $table;
        $this->type = $type;
    }

    public function on(){

    }

    public function orOn(){

    }
}