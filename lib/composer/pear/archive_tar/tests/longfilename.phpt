--TEST--
test trimming of characters in long filenames
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';
$dirname = dirname(__FILE__) . '/longfilenamedir/';
for ($i = 0; $i < 8; $i++) {
    $dirname .= str_pad('', 64, 'a') . '/';
}
$longfilename = $dirname . "b   ";
mkdir($dirname, 0777, true);
touch($longfilename);
$tar = new Archive_Tar(dirname(__FILE__) . '/testlongfilename.tar');
$tar->addModify(array($longfilename), '', dirname(__FILE__));
$tar = new Archive_Tar(dirname(__FILE__) . '/testlongfilename.tar');
$files = $tar->listContent();
$file = reset($files);
$lastChar = $file['filename'][strlen($file['filename']) - 1];
$phpunit->assertEquals(' ', $lastChar, 'should contain space as last character');
echo 'tests done';
?>
--CLEAN--
<?php
$dirname = dirname(__FILE__);
unlink($dirname . '/testlongfilename.tar');
system("rm -r $dirname/longfilenamedir");
?>
--EXPECT--
tests done
