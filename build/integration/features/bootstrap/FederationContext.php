<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

/**
 * Federation context.
 */
class FederationContext implements Context, SnippetAcceptingContext {
	use WebDav;
	use AppConfiguration;
	use CommandLine;

	/** @var string */
	private static $phpFederatedServerPid = '';

	/** @var string */
	private $lastAcceptedRemoteShareId;

	/**
	 * @BeforeScenario
	 * @AfterScenario
	 *
	 * The server is started also after the scenarios to ensure that it is
	 * properly cleaned up if stopped.
	 */
	public function startFederatedServer() {
		if (self::$phpFederatedServerPid !== '') {
			return;
		}

		$port = getenv('PORT_FED');

		self::$phpFederatedServerPid = exec('php -S localhost:' . $port . ' -t ../../ >/dev/null & echo $!');
	}

	/**
	 * @BeforeScenario
	 */
	public function cleanupRemoteStoragesAndShares() {
		// Ensure that dangling remote storages from previous tests will not
		// interfere with the current scenario.
		// The storages must be cleaned before each scenario; they can not be
		// cleaned after each scenario, as this hook is executed before the hook
		// that removes the users, so the shares would be still valid and thus
		// the storages would not be dangling yet.
		$this->runOcc(['sharing:cleanup-remote-storages']);

		// Even if the groups are removed after each scenario there might be
		// dangling remote group shares that could interfere with the current
		// scenario, so the remote shares need to be explicitly cleared.
		$this->runOcc(['app:enable', 'testing']);

		$user = $this->currentUser;
		$this->currentUser = 'admin';

		$this->sendingTo('DELETE', "/apps/testing/api/v1/remote_shares");
		$this->theHTTPStatusCodeShouldBe('200');
		if ($this->apiVersion === 1) {
			$this->theOCSStatusCodeShouldBe('100');
		}

		$this->currentUser = $user;
	}

	/**
	 * @Given /^User "([^"]*)" from server "(LOCAL|REMOTE)" shares "([^"]*)" with user "([^"]*)" from server "(LOCAL|REMOTE)"$/
	 *
	 * @param string $sharerUser
	 * @param string $sharerServer "LOCAL" or "REMOTE"
	 * @param string $sharerPath
	 * @param string $shareeUser
	 * @param string $shareeServer "LOCAL" or "REMOTE"
	 */
	public function federateSharing($sharerUser, $sharerServer, $sharerPath, $shareeUser, $shareeServer) {
		if ($shareeServer == 'REMOTE') {
			$shareWith = "$shareeUser@" . substr($this->remoteBaseUrl, 0, -4);
		} else {
			$shareWith = "$shareeUser@" . substr($this->localBaseUrl, 0, -4);
		}
		$previous = $this->usingServer($sharerServer);
		$this->createShare($sharerUser, $sharerPath, 6, $shareWith, null, null, null);
		$this->usingServer($previous);
	}


	/**
	 * @Given /^User "([^"]*)" from server "(LOCAL|REMOTE)" shares "([^"]*)" with group "([^"]*)" from server "(LOCAL|REMOTE)"$/
	 *
	 * @param string $sharerUser
	 * @param string $sharerServer "LOCAL" or "REMOTE"
	 * @param string $sharerPath
	 * @param string $shareeUser
	 * @param string $shareeServer "LOCAL" or "REMOTE"
	 */
	public function federateGroupSharing($sharerUser, $sharerServer, $sharerPath, $shareeGroup, $shareeServer) {
		if ($shareeServer == 'REMOTE') {
			$shareWith = "$shareeGroup@" . substr($this->remoteBaseUrl, 0, -4);
		} else {
			$shareWith = "$shareeGroup@" . substr($this->localBaseUrl, 0, -4);
		}
		$previous = $this->usingServer($sharerServer);
		$this->createShare($sharerUser, $sharerPath, 9, $shareWith, null, null, null);
		$this->usingServer($previous);
	}

	/**
	 * @Then remote share :count is returned with
	 *
	 * @param int $number
	 * @param TableNode $body
	 */
	public function remoteShareXIsReturnedWith(int $number, TableNode $body) {
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');

		if (!($body instanceof TableNode)) {
			return;
		}

		$returnedShare = $this->getXmlResponse()->data[0];
		if ($returnedShare->element) {
			$returnedShare = $returnedShare->element[$number];
		}

		$defaultExpectedFields = [
			'id' => 'A_NUMBER',
			'remote_id' => 'A_NUMBER',
			'accepted' => '1',
		];
		$expectedFields = array_merge($defaultExpectedFields, $body->getRowsHash());

		foreach ($expectedFields as $field => $value) {
			$this->assertFieldIsInReturnedShare($field, $value, $returnedShare);
		}
	}

	/**
	 * @When /^User "([^"]*)" from server "(LOCAL|REMOTE)" accepts last pending share$/
	 * @param string $user
	 * @param string $server
	 */
	public function acceptLastPendingShare($user, $server) {
		$previous = $this->usingServer($server);
		$this->asAn($user);
		$this->sendingToWith('GET', '/apps/files_sharing/api/v1/remote_shares/pending', null);
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');
		$share_id = simplexml_load_string($this->response->getBody())->data[0]->element[0]->id;
		$this->sendingToWith('POST', "/apps/files_sharing/api/v1/remote_shares/pending/{$share_id}", null);
		$this->theHTTPStatusCodeShouldBe('200');
		$this->theOCSStatusCodeShouldBe('100');
		$this->usingServer($previous);

		$this->lastAcceptedRemoteShareId = $share_id;
	}

	/**
	 * @When /^user "([^"]*)" deletes last accepted remote share$/
	 * @param string $user
	 */
	public function deleteLastAcceptedRemoteShare($user) {
		$this->asAn($user);
		$this->sendingToWith('DELETE', '/apps/files_sharing/api/v1/remote_shares/' . $this->lastAcceptedRemoteShareId, null);
	}

	/**
	 * @When /^user "([^"]*)" deletes last accepted remote group share$/
	 * @param string $user
	 */
	public function deleteLastAcceptedRemoteGroupShare($user) {
		$this->asAn($user);

		// Accepting the group share creates an additional share exclusive for
		// the user which needs to be got from the list of remote shares.
		$this->sendingToWith('DELETE', "/apps/files_sharing/api/v1/remote_shares/" . $this->getRemoteShareWithParentId($this->lastAcceptedRemoteShareId), null);
	}

	private function getRemoteShareWithParentId($parentId) {
		// Ensure that the id is a string rather than a SimpleXMLElement.
		$parentId = (string)$parentId;

		$this->sendingToWith('GET', "/apps/files_sharing/api/v1/remote_shares", null);

		$returnedShare = $this->getXmlResponse()->data[0];
		if ($returnedShare->element) {
			for ($i = 0; $i < count($returnedShare->element); $i++) {
				if (((string)$returnedShare->element[$i]->parent) === $parentId) {
					return (string)$returnedShare->element[$i]->id;
				}
			}
		} elseif (((string)$returnedShare->parent) === $parentId) {
			return (string)$returnedShare->id;
		}

		Assert::fail("No remote share found with parent id $parentId");
	}

	/**
	 * @When /^remote server is started$/
	 */
	public function remoteServerIsStarted() {
		$this->startFederatedServer();

		$retryCount = 10;

		while (!$this->isRemoteServerReady()) {
			if ($retryCount > 0) {
				sleep(1);

				$retryCount--;
			} else {
				Assert::fail("Remote server not ready yet after 10 seconds");
			}
		}
	}

	private function isRemoteServerReady() {
		$port = getenv('PORT_FED');
		$remoteServerUrl = 'http://localhost:' . $port;

		$client = new Client();

		try {
			$client->request('GET', $remoteServerUrl);
		} catch (ClientException $exception) {
			return false;
		} catch (ConnectException $exception) {
			return false;
		}

		return true;
	}

	/**
	 * @When /^remote server is stopped$/
	 */
	public function remoteServerIsStopped() {
		if (self::$phpFederatedServerPid === '') {
			return;
		}

		exec('kill ' . self::$phpFederatedServerPid);

		self::$phpFederatedServerPid = '';
	}

	protected function resetAppConfigs() {
		$this->deleteServerConfig('files_sharing', 'incoming_server2server_group_share_enabled');
		$this->deleteServerConfig('files_sharing', 'outgoing_server2server_group_share_enabled');
	}
}
