--TEST--
test symbolic links
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$me = dirname(__FILE__) . '/testit';
$tar = new Archive_Tar(dirname(__FILE__) . '/relativesymlink.tar');
$tar->extract();
$phpunit->assertNoErrors('after');
$phpunit->assertFileExists('testme', 'dir');
$phpunit->assertFileExists('testme/a/file1.txt', 'file1.txt');
$phpunit->assertFileExists('testme/b/symlink.txt', 'symlink.txt');
$phpunit->assertTrue(is_link('testme/b/symlink.txt'), 'is link');
echo 'tests done';
?>
--CLEAN--
<?php
@unlink('testme/a/file1.txt');
@unlink('testme/b/symlink.txt');
@rmdir('testme/a');
@rmdir('testme/b');
@rmdir('testme');
?>
--EXPECT--
tests done
