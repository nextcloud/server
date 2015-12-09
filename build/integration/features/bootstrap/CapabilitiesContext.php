<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Capabilities context.
 */
class CapabilitiesContext implements Context, SnippetAcceptingContext {

	use BasicStructure;
	use Provisioning;
	use Sharing;

	private $apacheUser = NULL;

	/**
	 * @Given /^parameter "([^"]*)" of app "([^"]*)" is set to "([^"]*)"$/
	 */
	public function serverParameterIsSetTo($parameter, $app, $value){
		if (!isset($this->apacheUser)){
			$this->apacheUser = $this->getOSApacheUser();
		}
		$this->modifyServerConfig($this->apacheUser, $parameter, $app, $value);
	}

	/**
	 * @Then /^fields of capabilities match with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $formData
	 */
	public function checkCapabilitiesResponse($formData){
		if ($formData instanceof \Behat\Gherkin\Node\TableNode) {
			$fd = $formData->getHash();
		}
		
		$capabilitiesXML = $this->response->xml()->data->capabilities;
		
		foreach ($fd as $row) {
			if ($row['value'] === ''){
				$answeredValue = (string)$capabilitiesXML->$row['capability']->$row['feature'];
				PHPUnit_Framework_Assert::assertEquals(
					$answeredValue, 
					$row['value_or_subfeature'], 
					"Failed field " . $row['capability'] . " " . $row['feature']
				);
			} else{
				$answeredValue = (string)$capabilitiesXML->$row['capability']->$row['feature']->$row['value_or_subfeature'];
				PHPUnit_Framework_Assert::assertEquals(	
					$answeredValue, 
					$row['value']==="EMPTY" ? '' : $row['value'], 
					"Failed field: " . $row['capability'] . " " . $row['feature'] . " " . $row['value_or_subfeature']
				);
			}
		}
	}

	public static function modifyServerConfig($apacheUser, $parameter, $app, $value){
		$comando = 'sudo -u ' . $apacheUser . ' ../../occ config:app:set ' . $app . " " . $parameter . ' --value=' . $value;
		$expectedAnswer = "Config value $parameter for app $app set to $value";
		$output = exec($comando);
		PHPUnit_Framework_Assert::assertEquals(
					$output, 
					$expectedAnswer, 
					"Failed setting $parameter to $value"
		);

	}

	public static function getOSApacheUser(){
		return exec('ps axho user,comm|grep -E "httpd|apache"|uniq|grep -v "root"|awk \'END {if ($1) print $1}\'');
	}

	/**
	 * @BeforeSuite
	 */
	public static function prepareParameters(){
		$apacheUser = self::getOSApacheUser();
		self::modifyServerConfig($apacheUser, "shareapi_allow_public_upload", "core", "yes");
	}

	/**
	 * @AfterSuite
	 */
	public static function undoChangingParameters(){
		$apacheUser = self::getOSApacheUser();
		self::modifyServerConfig($apacheUser, "shareapi_allow_public_upload", "core", "yes");
	}

}
