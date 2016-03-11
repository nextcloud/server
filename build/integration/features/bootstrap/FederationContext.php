<?php

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use GuzzleHttp\Client;
use GuzzleHttp\Message\ResponseInterface;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Federation context.
 */
class FederationContext implements Context, SnippetAcceptingContext {

	use BasicStructure;
	use Provisioning;
	use Sharing;

	/**
	 * @When /^User "([^"]*)" from server "([^"]*)" shares "([^"]*)" with user "([^"]*)" from server "([^"]*)"$/
	 */
	public function federateSharing($userLocal, $serverLocal, $pathLocal, $userRemote, $serverRemote){
		if ($serverRemote == "REMOTE"){
			$shareWith = "$userRemote@" . substr($this->remoteBaseUrl, 0, -4);
		} elseif ($serverRemote == "LOCAL") {
			$shareWith = "$userRemote@" . substr($this->localBaseUrl, 0, -4);
		}
		$this->createShare($userLocal, $pathLocal, 6, $shareWith, null, null, null);
	}

	/**
	 * @When /^User "([^"]*)" from server "([^"]*)" accepts last pending share$/
	 */
	public function acceptLastPendingShare($user, $server){
		$this->usingServer($server);
		$this->asAn($user);
		$this->sendingToWith('GET', "/apps/files_sharing/api/v1/remote_shares/pending", null);
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');
		$share_id = $this->response->xml()->data[0]->element[0]->id;
		$this->sendingToWith('POST', "/apps/files_sharing/api/v1/remote_shares/pending/{$share_id}", null);
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');
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
		$this->theOCSStatusCodeShouldBe('100');
	}

}
