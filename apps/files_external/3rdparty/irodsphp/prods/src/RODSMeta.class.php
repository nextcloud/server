<?php

class RODSMeta
{
    public $name;
    public $value;
    public $units;
    public $id;
    public $op;

    public function __construct($name, $value, $units = NULL, $id = NULL, $op = "=")
    {
        $this->name = $name;
        $this->value = $value;
        $this->units = $units;
        $this->id = $id;
        $this->op = $op;
    }

}  
     