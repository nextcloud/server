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
		$shareWith = "$userRemote@" . substr($this->remoteBaseUrl, 0, -4);
		$this->createShare($userLocal, $pathLocal, 6, $shareWith, null, null, null);
	}

}
