--TEST--
test saving of dead symbolic links
--SKIPIF--
--FILE--
<?php
require_once dirname(__FILE__) . '/setup.php.inc';

$tar1 = new Archive_Tar(dirname(__FILE__) . '/text-0.txt');
$tar1->listContent();

$phpunit->assertErrors(array(
    array('package' => 'PEAR_Error', 'message' => "Invalid checksum for file \"4&2Vib~Au.=ZqMTw~=}UoF/~5Gs;JTXF*<FyG\"\n" .
                                                                               '1Hvu#nol Y 21/{G9i|D=$GTI KREuA\wI<wM@dYOKzS{6&]QE8ud|EPb:f?-Gt' . "\n" .
                                                                               'I#)E9)@;_.0y9`P,&jY! :\.J8uG/a8,.vT[$Fe;>}hm\"#xO7:46y_tg6c-[A&S]z0fke?(B6?$:W$X{E%PO>~UWl8(' . "\n" .
                                                                               '.%RWPjN55)cd&?oJPgFFZj#+U<qrF:9yIRe\" : UPJX05Zz extracted')), 'after 1');
echo 'tests done';
?>
--CLEAN--
--EXPECT--
tests done
