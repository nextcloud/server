<?php
/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2010 Robin Appelman icewind1991@gmailc.om
*
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either
* version 3 of the License, or any later version.
*
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*
* You should have received a copy of the GNU Affero General Public
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
*
*/


/**
 * run all test cases
 */
 $RUNTIME_NOSETUPFS=true;
require_once('../lib/base.php');
OC_Util::checkAdminUser();

$testCases=loadFiles(__DIR__,array('index.php','templates'));
@ob_end_clean();
$testResults=array();
foreach($testCases as $testCaseClass){
	$testCase=new $testCaseClass();
	$results=array();
	foreach($testCase->getTests() as $test){
		$testCase->setup();
		try{
			$testCase->$test();
			$results[$test]='Ok';
		}catch(Exception $e){
			$results[$test]=$e->getMessage();
		}
		$testCase->tearDown();
	}
	$testResults[$testCaseClass]=$results;
}

$tmpl = new OC_Template( 'tests', 'index');
$tmpl->assign('tests',$testResults);
$tmpl->printPage();

/**
 * recursively load all files in a folder
 * @param string $path
 * @param array $exclude list of files to exclude
 */
function loadFiles($path,$exclude=false){
	$results=array();
	if(!$exclude){
		$exclude=array();
	}
	$dh=opendir($path);
	while($file=readdir($dh)){
		if($file!='.' && $file!='..' && array_search($file,$exclude)===false){
			if(is_file($path.'/'.$file) and substr($file,-3)=='php'){
				$result=require_once($path.'/'.$file);
				$results[]=$result;
			}elseif(is_dir($path.'/'.$file)){
				$subResults=loadFiles($path.'/'.$file);
				$results=array_merge($results,$subResults);
			}
		}
	}
	return $results;
}
?>
