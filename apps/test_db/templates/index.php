<?php

$t1 = new Test();
$t1->init();
$t1->show();
$testid = OC_DB4App::store('test_db','main',OC_User::getUser(),$t1);
echo "id in db is $testid<br/>\n";

$t2 = OC_DB4App::get_object('test_db','main',$testid);
$t2->show();

print_r(OC_DB4App::get_objects('test_db','main',OC_User::getUser()));

OC_DB4App::delete_object('test_db','main',$testid);

OC_DB4App::drop('test_db','main');
?>
