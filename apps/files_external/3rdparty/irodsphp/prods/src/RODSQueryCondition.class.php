<?php

class RODSQueryCondition
{
    public $name;
    public $value;
    public $op;

    public function __construct($name, $value, $op = "=")
    {
        $this->name = $name;
        $this->value = $value;
        $this->op = $op;
    }

    public function __toString()
    {
        return "$this->name $this->op '$this->value'";
    }

}  
     