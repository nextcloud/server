--TEST--
test directory traversal security vulnerability
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$me = dirname(__FILE__) . '/testit';
$tar = new Archive_Tar(dirname(__FILE__) . '/hamidTARtester2.tar');
$tar->listContent();
$phpunit->assertErrors(array(
    array('package' => 'PEAR_Error', 'message' => 'Malicious .tar detected, file "/../../../../../../../../../../../../../../AAAAAAAAAAAAAAAAA/BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB.txt" will not install in desired directory tree')
), 'after 1');
$tar->extract();
$phpunit->assertErrors(array(
    array('package' => 'PEAR_Error', 'message' => 'Malicious .tar detected, file "/../../../../../../../../../../../../../../AAAAAAAAAAAAAAAAA/BBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBBB.txt" will not install in desired directory tree')
), 'after 2');
echo 'tests done';
?>
--CLEAN--
<?php
@rmdir('AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAa');
?>
--EXPECT--
tests done
