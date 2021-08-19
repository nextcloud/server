--TEST--
Console_Getopt [bug 13140]
--SKIPIF--
--FILE--
<?php
$_SERVER['argv'] = $argv =
    array('--bob', '--foo' , '-bar', '--test', '-rq', 'thisshouldbehere');

require_once 'Console/Getopt.php';
$cg = new Console_GetOpt();

print_r($cg->getopt2($cg->readPHPArgv(), 't', array('test'), true));
print_r($cg->getopt2($cg->readPHPArgv(), 'bar', array('foo'), true));
?>
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => Array
                (
                    [0] => --test
                    [1] => 
                )

        )

    [1] => Array
        (
            [0] => thisshouldbehere
        )

)
Array
(
    [0] => Array
        (
            [0] => Array
                (
                    [0] => --foo
                    [1] => 
                )

            [1] => Array
                (
                    [0] => b
                    [1] => 
                )

            [2] => Array
                (
                    [0] => a
                    [1] => 
                )

            [3] => Array
                (
                    [0] => r
                    [1] => 
                )

            [4] => Array
                (
                    [0] => r
                    [1] => 
                )

        )

    [1] => Array
        (
            [0] => thisshouldbehere
        )

)
