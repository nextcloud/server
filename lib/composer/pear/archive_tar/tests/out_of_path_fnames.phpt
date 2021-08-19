--TEST--
tests writes to out-of-path filenames
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$tar = new Archive_Tar(dirname(__FILE__) . '/out_of_path_symlink.tar');
$tar->extract();
$phpunit->assertErrors(array(array('package' => 'PEAR_Error', 'message' => "Out-of-path file extraction {symlink --> /tmp/}")), 'after 1');
$phpunit->assertFileNotExists('symlink/whatever-filename', 'Out-of-path filename should not have succeeded');
echo 'tests done';
?>
--CLEAN--
<?php
@unlink("symlink");
?>
--EXPECT--
tests done
