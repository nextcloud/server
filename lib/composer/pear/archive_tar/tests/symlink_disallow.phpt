--TEST--
test symbolic links
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$me = dirname(__FILE__) . '/testit';
$tar = new Archive_Tar(dirname(__FILE__) . '/testsymlink.tar');
$tar->extract('', false, false);
$phpunit->assertErrors(array(
    array(
        'package' => 'PEAR_Error',
        'message' => 'Symbolic links are not allowed. Unable to extract {testme/symlink.txt}'
    ),
), 'Warning thrown');
$phpunit->assertFileExists('testme', 'dir');
$phpunit->assertFileNotExists('testme/file1.txt', 'file1.txt');
$phpunit->assertFileNotExists('testme/symlink.txt', 'symlink.txt');
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
