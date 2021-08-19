--TEST--
Console_Getopt [bug 11068]
--SKIPIF--
--FILE--
<?php
$_SERVER['argv'] =
$argv = array('hi', '-fjjohnston@mail.com', '--to', 'hi', '-');
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
Array
(
    [0] => Array
        (
            [0] => Array
                (
                    [0] => f
                    [1] => jjohnston@mail.com
                )

            [1] => Array
                (
                    [0] => --to
                    [1] => hi
                )

        )

    [1] => Array
        (
            [0] => -
        )

)