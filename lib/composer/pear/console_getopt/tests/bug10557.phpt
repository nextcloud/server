--TEST--
Console_Getopt [bug 10557]
--SKIPIF--
--FILE--
<?php
$_SERVER['argv'] =
$argv = array('hi', '-fjjohnston@mail.com', '--to', '--mailpack', '--debug');
require_once 'Console/Getopt.php';
$ret = Console_Getopt::getopt(Console_Getopt::readPHPArgv(), 'f:t:',
array('from=','to=','mailpack=','direction=','verbose','debug'));
if(PEAR::isError($ret))
{
	echo $ret->getMessage()."\n";
	echo 'FATAL';
	exit;
}

print_r($ret);
?>
--EXPECT--
Console_Getopt: option requires an argument --to
FATAL