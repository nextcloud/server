<?php
/**
* ownCloud
*
* @author Robin Appelman
* @copyright 2012 Robin Appelman icewind@owncloud.com
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

require_once '../lib/base.php';
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/mock_objects.php';
require_once 'simpletest/collector.php';
require_once 'simpletest/default_reporter.php';

$testSuiteName="ownCloud Unit Test Suite";

// prepare the reporter
if(OC::$CLI){
	$reporter=new TextReporter;
	$test=isset($_SERVER['argv'][1])?$_SERVER['argv'][1]:false;
	if($test=='xml')
	{
		$reporter= new XmlReporter;
		$test=false;

		if(isset($_SERVER['argv'][2])){
			$testSuiteName=$testSuiteName." (".$_SERVER['argv'][2].")";
		}
	}
}else{
	$reporter='HtmlReporter';
	$test=isset($_GET['test'])?$_GET['test']:false;
}

// test suite instance
$testSuite=new TestSuite($testSuiteName);

//load core test cases
loadTests(dirname(__FILE__), $testSuite, $test);

//load app test cases

//
// TODO: define a list of apps to be enabled + enable them
//

$apps=OC_App::getEnabledApps();
foreach($apps as $app){
	if(is_dir(OC::$SERVERROOT.'/apps/'.$app.'/tests')){
		loadTests(OC::$SERVERROOT.'/apps/'.$app.'/tests', $testSuite, $test);
	}
}

// run the suite
if($testSuite->getSize()>0){
	$testSuite->run($reporter);
}

// helper below
function loadTests($dir,$testSuite, $test){
	if($dh=opendir($dir)){
		while($name=readdir($dh)){
			if($name[0]!='.'){//no hidden files, '.' or '..'
				$file=$dir.'/'.$name;
				if(is_dir($file)){
					loadTests($file, $testSuite, $test);
				}elseif(substr($file,-4)=='.php' and $file!=__FILE__){
					$name=getTestName($file);
					if($test===false or $test==$name or substr($name,0,strlen($test))==$test){
					        $extractor = new SimpleFileLoader();
						$loadedSuite=$extractor->load($file);
						if ($loadedSuite->getSize() > 0)
						        $testSuite->add($loadedSuite);
					}
				}
			}
		}
	}
}

function getTestName($file){
// 	//TODO: get better test names
	$file=substr($file,strlen(OC::$SERVERROOT));
	return substr($file,0,-4);//strip .php
}
