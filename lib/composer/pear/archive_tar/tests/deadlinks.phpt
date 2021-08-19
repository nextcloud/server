--TEST--
test saving of dead symbolic links
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';

function fileName($item){
    return rtrim($item['filename'],'/').' => '.$item['link'];
}

//prepare filesystem
@mkdir('test');
@mkdir('test/a');
@touch('test/b');
@symlink('a', 'test/dir_link');
@symlink('b', 'test/file_link');
@symlink('dead', 'test/dead_link');

//prepare reference tar
system('tar -cf test1.tar test');
$tar1=new Archive_Tar('test1.tar');
$tar1List=array_map('fileName',$tar1->listContent());
//create tar
$tar2=new Archive_Tar('test2.tar');
$tar2->create(array('test','nonExisting'));// to make sure we are still report nonExisting
$tar2List=array_map('fileName',$tar2->listContent());

$phpunit->assertErrors(array(
    array('package' => 'PEAR_Error', 'message' => "File 'nonExisting' does not exist")), 'after 1');
$phpunit->assertEquals($tar1List, $tar2List, 'bla');
echo 'tests done';
?>
--CLEAN--
<?php
@rmdir('test/a');
@unlink('test/b');
@unlink('test/dir_link');
@unlink('test/file_link');
@unlink('test/dead_link');
@rmdir('test');
@unlink('test1.tar');
@unlink('test2.tar');
?>
--EXPECT--
tests done
