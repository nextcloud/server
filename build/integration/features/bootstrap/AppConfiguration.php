<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Hook\Scope\AfterScenarioScope;
use Behat\Behat\Hook\Scope\BeforeScenarioScope;
use PHPUnit\Framework\Assert;
use Psr\Http\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';

trait AppConfiguration {
	/** @var string */
	private $currentUser = '';

	/** @var ResponseInterface */
	private $response = null;

	abstract public function sendingTo($verb, $url);
	abstract public function sendingToWith($verb, $url, $body);
	abstract public function theOCSStatusCodeShouldBe($statusCode);
	abstract public function theHTTPStatusCodeShouldBe($statusCode);

	/**
	 * @Given /^parameter "([^"]*)" of app "([^"]*)" is set to "([^"]*)"$/
	 * @param string $parameter
	 * @param string $app
	 * @param string $value
	 */
	public function serverParameterIsSetTo($parameter, $app, $value) {
		$user = $this->currentUser;
		$this->currentUser = 'admin';

		$this->modifyServerConfig($app, $parameter, $value);

		$this->currentUser = $user;
	}

	/**
	 * @param string $app
	 * @param string $parameter
	 * @param string $value
	 */
	protected function modifyServerConfig($app, $parameter, $value) {
		$body = new \Behat\Gherkin\Node\TableNode([['value', $value]]);
		$this->sendingToWith('post', "/apps/testing/api/v1/app/{$app}/{$parameter}", $body);
		$this->theHTTPStatusCodeShouldBe('200');
		if ($this->apiVersion === 1) {
			$this->theOCSStatusCodeShouldBe('100');
		}
	}

	/**
	 * @param string $app
	 * @param string $parameter
	 * @param string $value
	 */
	protected function deleteServerConfig($app, $parameter) {
		$this->sendingTo('DELETE', "/apps/testing/api/v1/app/{$app}/{$parameter}");
		$this->theHTTPStatusCodeShouldBe('200');
		if ($this->apiVersion === 1) {
			$this->theOCSStatusCodeShouldBe('100');
		}
	}

	protected function setStatusTestingApp($enabled) {
		$this->sendingTo(($enabled ? 'post' : 'delete'), '/cloud/apps/testing');
		$this->theHTTPStatusCodeShouldBe('200');
		if ($this->apiVersion === 1) {
			$this->theOCSStatusCodeShouldBe('100');
		}

		$this->sendingTo('get', '/cloud/apps?filter=enabled');
		$this->theHTTPStatusCodeShouldBe('200');
		if ($enabled) {
			Assert::assertStringContainsString('testing', $this->response->getBody()->getContents());
		} else {
			Assert::assertStringNotContainsString('testing', $this->response->getBody()->getContents());
		}
	}

	abstract protected function resetAppConfigs();

	/**
	 * @BeforeScenario
	 *
	 * Enable the testing app before the first scenario of the feature and
	 * reset the configs before each scenario
	 * @param BeforeScenarioScope $event
	 */
	public function prepareParameters(BeforeScenarioScope $event) {
		$user = $this->currentUser;
		$this->currentUser = 'admin';

		$scenarios = $event->getFeature()->getScenarios();
		if ($event->getScenario() === reset($scenarios)) {
			$this->setStatusTestingApp(true);
		}

		$this->resetAppConfigs();

		$this->currentUser = $user;
	}

	/**
	 * @AfterScenario
	 *
	 * Reset the values after the last scenario of the feature and disable the testing app
	 * @param AfterScenarioScope $event
	 */
	public function undoChangingParameters(AfterScenarioScope $event) {
		$scenarios = $event->getFeature()->getScenarios();
		if ($event->getScenario() === end($scenarios)) {
			$user = $this->currentUser;
			$this->currentUser = 'admin';

			$this->resetAppConfigs();

			$this->setStatusTestingApp(false);
			$this->currentUser = $user;
		}
	}
}
