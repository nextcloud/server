<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use Behat\Behat\Context\Context;
use PHPUnit\Framework\Assert;

require __DIR__ . '/autoload.php';


/**
 * Files reminders context.
 */
class FilesRemindersContext implements Context {
	use BasicStructure;
	use WebDav;

	/**
	 * @When the user sets a reminder for :path with due date :dueDate
	 */
	public function settingAReminderForFileWithDueDate($path, $dueDate) {
		$fileId = $this->getFileIdForPath($this->currentUser, $path);
		$this->sendRequestForJSON(
			'PUT',
			'/apps/files_reminders/api/v1/' . $fileId,
			['dueDate' => $dueDate],
			['OCS-APIREQUEST' => 'true']
		);
	}

	/**
	 * @Then the user sees the reminder for :path is set to :dueDate
	 */
	public function retrievingTheReminderForFile($path, $dueDate) {
		$fileId = $this->getFileIdForPath($this->currentUser, $path);
		$this->sendRequestForJSON(
			'GET',
			'/apps/files_reminders/api/v1/' . $fileId,
			null,
			['OCS-APIREQUEST' => 'true']
		);
		$response = $this->getDueDateFromOCSResponse();
		Assert::assertEquals($dueDate, $response);
	}

	/**
	 * @Then the user sees the reminder for :path is not set
	 */
	public function retrievingTheReminderForFileIsNotSet($path) {
		$fileId = $this->getFileIdForPath($this->currentUser, $path);
		$this->sendRequestForJSON(
			'GET',
			'/apps/files_reminders/api/v1/' . $fileId,
			null,
			['OCS-APIREQUEST' => 'true']
		);
		$response = $this->getDueDateFromOCSResponse();
		Assert::assertNull($response);
	}

	/**
	 * @When the user removes the reminder for :path
	 */
	public function removingTheReminderForFile($path) {
		$fileId = $this->getFileIdForPath($this->currentUser, $path);
		$this->sendRequestForJSON(
			'DELETE',
			'/apps/files_reminders/api/v1/' . $fileId,
			null,
			['OCS-APIREQUEST' => 'true']
		);
	}

	/**
	 * Check the due date from OCS response
	 */
	private function getDueDateFromOCSResponse(): ?string {
		if ($this->response === null) {
			throw new \RuntimeException('No response available');
		}

		$body = (string)$this->response->getBody();
		if (str_starts_with($body, '<')) {
			$body = simplexml_load_string($body);
			if ($body === false) {
				throw new \RuntimeException('Could not parse OCS response, body is not valid XML');
			}
			return $body->data->dueDate;
		}

		$body = json_decode($body, true);
		return $body['ocs']['data']['dueDate'] ?? null;
	}
}
