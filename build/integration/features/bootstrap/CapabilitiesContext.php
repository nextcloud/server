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

	private $apacheUser = '';

	/**
	 * @Given /^parameter "([^"]*)" is set to "([^"]*)"$/
	 */
	public function modifyServerConfig($parameter, $value){
		$this->apacheUser = exec('ps axho user,comm|grep -E "httpd|apache"|uniq|grep -v "root"|awk \'END {if ($1) print $1}\'');
		$comando = 'sudo -u ' . $this->apacheUser . ' ../../occ config:app:set ' . $parameter . ' ' . $value;
		echo "COMANDO: $comando\n";
		$expectedAnswer = "Config value $value for app $parameter set to";
		$output = exec($comando);
		PHPUnit_Framework_Assert::assertEquals(
					$output, 
					$expectedAnswer, 
					"Failed setting $parameter to $value"
		);

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
					$row['value'], 
					"Failed field: " . $row['capability'] . " " . $row['feature'] . " " . $row['value_or_subfeature']
				);
			}
		}
	}

}
