--TEST--
test files that happen to contain the endblock
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$tar = new Archive_Tar(dirname(__FILE__) . '/testblock.tar.gz');
$tar->add(dirname(__FILE__) . '/testblock1');
$tar->add(dirname(__FILE__) . '/testblock2');
$tar = new Archive_Tar(dirname(__FILE__) . '/testblock.tar.gz');
$tar->listContent();
$phpunit->assertNoErrors('after');
echo 'tests done';
?>
--CLEAN--
<?php
@unlink(dirname(__FILE__) . '/testblock.tar.gz');
?>
--EXPECT--
tests done
