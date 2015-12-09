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

	/**
	 * @Given /^parameter "([^"]*)" of app "([^"]*)" is set to "([^"]*)"$/
	 */
	public function serverParameterIsSetTo($parameter, $app, $value){
		$this->modifyServerConfig($app, $parameter, $value);
	}

	/**
	 * @Then /^fields of capabilities match with$/
	 * @param \Behat\Gherkin\Node\TableNode|null $formData
	 */
	public function checkCapabilitiesResponse(\Behat\Gherkin\Node\TableNode $formData){
		$capabilitiesXML = $this->response->xml()->data->capabilities;

		foreach ($formData->getHash() as $row) {
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

	/**
	 * @BeforeScenario
	 */
	public function prepareParameters(){
		$this->modifyServerConfig('core', 'shareapi_allow_public_upload', 'yes');
	}

	/**
	 * @AfterScenario
	 */
	public function undoChangingParameters(){
		$this->modifyServerConfig('core', 'shareapi_allow_public_upload', 'yes');
	}

	/**
	 * @param string $app
	 * @param string $parameter
	 * @param string $value
	 */
	protected function modifyServerConfig($app, $parameter, $value) {
		$user = $this->currentUser;

		$this->currentUser = 'admin';

		$this->setStatusTestingApp(true);

		$body = new \Behat\Gherkin\Node\TableNode([['value', $value]]);
		$this->sendingToWith('post', "/apps/testing/api/v1/app/{$app}/{$parameter}", $body);
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');

		$this->setStatusTestingApp(false);

		$this->currentUser = $user;
	}

	protected function setStatusTestingApp($enabled) {
		$this->sendingTo(($enabled ? 'post' : 'delete'), '/cloud/apps/testing');
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');

		$this->sendingTo('get', '/cloud/apps?filter=enabled');
		$this->theHTTPStatusCodeShouldBe('200');
		if ($enabled) {
			PHPUnit_Framework_Assert::assertContains('testing', $this->response->getBody()->getContents());
		} else {
			PHPUnit_Framework_Assert::assertNotContains('testing', $this->response->getBody()->getContents());
		}
	}
}
