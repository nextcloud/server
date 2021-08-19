--TEST--
tests if pax global / extended headers are ignored
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$tar = new Archive_Tar(dirname(__FILE__) . '/testpax.tar');
$phpunit->assertEquals(1, count($tar->listContent()), "count should be 1");
echo 'tests done';
?>
--CLEAN--
--EXPECT--
tests done
