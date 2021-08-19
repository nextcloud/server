--TEST--
test preserving of permissions
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$tar = new Archive_Tar(dirname(__FILE__) . '/testperms.tar');
$tar->extract('', true);
$phpunit->assertNoErrors('after');
echo 'tests done';
?>
--CLEAN--
<?php
@unlink('a');
@unlink('b');
?>
--EXPECT--
tests done
