--TEST--
test symbolic links
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$me = dirname(__FILE__) . '/testit';
$tar = new Archive_Tar(dirname(__FILE__) . '/testsymlink.tar');
$tar->extract();
$phpunit->assertNoErrors('after');
$phpunit->assertFileExists('testme', 'dir');
$phpunit->assertFileExists('testme/file1.txt', 'file1.txt');
$phpunit->assertFileExists('testme/symlink.txt', 'symlink.txt');
$phpunit->assertTrue(is_link('testme/symlink.txt'), 'is link');
echo 'tests done';
?>
--CLEAN--
<?php
@unlink('testme/file1.txt');
@unlink('testme/symlink.txt');
@rmdir('testme');
?>
--EXPECT--
tests done
