<?php

require_once('../../lib/base.php');

// Check if we are a user
if( !OC_User::isLoggedIn()){
	header( "Location: ".OC_Helper::linkTo( '', 'index.php' ));
	exit();
}

class Test {
    private $test1;
    private $test2;
    public function init() {
        $this->test1 = "test1";
        $this->test2 = 2;
    }
    public function show() {
        echo "test1:".$this->test1."<br/>test2:".$this->test2."<br/>";
    }
};

$tmpl = new OC_Template( 'test_db', 'index', 'user' );

$tmpl->printPage();
?>
