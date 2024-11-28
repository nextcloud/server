<?php
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Gherkin\Node\TableNode;
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

trait ExternalStorage {
	private array $storageIds = [];

	private array $lastExternalStorageData;

	/**
	 * @AfterScenario
	 **/
	public function deleteCreatedStorages(): void {
		foreach ($this->storageIds as $storageId) {
			$this->deleteStorage($storageId);
		}
		$this->storageIds = [];
	}

	private function deleteStorage(string $storageId): void {
		// Based on "runOcc" from CommandLine trait
		$args = ['files_external:delete', '--yes', $storageId];
		$args = array_map(function ($arg) {
			return escapeshellarg($arg);
		}, $args);
		$args[] = '--no-ansi --no-warnings';
		$args = implode(' ', $args);

		$descriptor = [
			0 => ['pipe', 'r'],
			1 => ['pipe', 'w'],
			2 => ['pipe', 'w'],
		];
		$process = proc_open('php console.php ' . $args, $descriptor, $pipes, $ocPath = '../..');
		$lastStdOut = stream_get_contents($pipes[1]);
		proc_close($process);
	}

	/**
	 * @When logged in user creates external global storage
	 *
	 * @param TableNode $fields
	 */
	public function loggedInUserCreatesExternalGlobalStorage(TableNode $fields): void {
		$this->sendJsonWithRequestTokenAndBasicAuth('POST', '/index.php/apps/files_external/globalstorages', $fields);
		$this->theHTTPStatusCodeShouldBe('201');

		$this->lastExternalStorageData = json_decode($this->response->getBody(), $asAssociativeArray = true);

		$this->storageIds[] = $this->lastExternalStorageData['id'];
	}

	/**
	 * @When logged in user updates last external userglobal storage
	 *
	 * @param TableNode $fields
	 */
	public function loggedInUserUpdatesLastExternalUserglobalStorage(TableNode $fields): void {
		$this->sendJsonWithRequestTokenAndBasicAuth('PUT', '/index.php/apps/files_external/userglobalstorages/' . $this->lastExternalStorageData['id'], $fields);
		$this->theHTTPStatusCodeShouldBe('200');

		$this->lastExternalStorageData = json_decode($this->response->getBody(), $asAssociativeArray = true);
	}

	/**
	 * @Then fields of last external storage match with
	 *
	 * @param TableNode $fields
	 */
	public function fieldsOfLastExternalStorageMatchWith(TableNode $fields): void {
		foreach ($fields->getRowsHash() as $expectedField => $expectedValue) {
			if (!array_key_exists($expectedField, $this->lastExternalStorageData)) {
				Assert::fail("$expectedField was not found in response");
			}

			Assert::assertEquals($expectedValue, $this->lastExternalStorageData[$expectedField], "Field '$expectedField' does not match ({$this->lastExternalStorageData[$expectedField]})");
		}
	}

	private function sendJsonWithRequestToken(string $method, string $url, TableNode $fields): void {
		$isFirstField = true;
		$fieldsAsJsonString = '{';
		foreach ($fields->getRowsHash() as $key => $value) {
			$fieldsAsJsonString .= ($isFirstField ? '' : ',') . '"' . $key . '":' . $value;
			$isFirstField = false;
		}
		$fieldsAsJsonString .= '}';

		$body = [
			'headers' => [
				'Content-Type' => 'application/json',
			],
			'body' => $fieldsAsJsonString,
		];
		$this->sendingAToWithRequesttoken($method, $url, $body);
	}

	private function sendJsonWithRequestTokenAndBasicAuth(string $method, string $url, TableNode $fields): void {
		$isFirstField = true;
		$fieldsAsJsonString = '{';
		foreach ($fields->getRowsHash() as $key => $value) {
			$fieldsAsJsonString .= ($isFirstField ? '' : ',') . '"' . $key . '":' . $value;
			$isFirstField = false;
		}
		$fieldsAsJsonString .= '}';

		$body = [
			'headers' => [
				'Content-Type' => 'application/json',
				'Authorization' => 'Basic ' . base64_encode('admin:admin'),
			],
			'body' => $fieldsAsJsonString,
		];
		$this->sendingAToWithRequesttoken($method, $url, $body);
	}
}
