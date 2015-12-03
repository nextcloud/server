<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Capabilities context.
 */
class CapabilitiesContext implements Context, SnippetAcceptingContext {

	use BasicStructure;
	use Provisioning;
	use Sharing;

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
