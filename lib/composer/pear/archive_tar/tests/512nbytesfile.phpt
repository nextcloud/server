--TEST--
test file size that happens to be 512 * n bytes
--SKIPIF--
--FILE--
<?php
$dirname = dirname(__FILE__);
require_once $dirname . '/setup.php.inc';

$tar = new Archive_Tar($dirname . '/512nbytesfile.tar.gz', null, 2048);
$tar->add($dirname .'/testblock3');
$tar->listContent();
$phpunit->assertNoErrors('after tar archive listing');

$returnval = shell_exec('tar -Ptf ' . $dirname . '/512nbytesfile.tar.gz | sort');
$phpunit->assertNoErrors('after shell tar listing');

$expectedvalue = 
<<< EOD
$dirname/testblock3
$dirname/testblock3/1024bytes.txt
$dirname/testblock3/randombytes.txt
EOD;
$phpunit->assertEquals($expectedvalue, $returnval, 'wrong output for shell tar verification');

echo 'test done'
?>
--CLEAN--
<?php
$dirname = dirname(__FILE__);
@unlink($dirname.'/512nbytesfile.tar.gz');
?>
--EXPECT--
test done