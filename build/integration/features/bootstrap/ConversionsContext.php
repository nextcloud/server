<?php
/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
require __DIR__ . '/../../vendor/autoload.php';

use Behat\Behat\Context\Context;
use Behat\Behat\Context\SnippetAcceptingContext;
use Behat\Gherkin\Node\TableNode;

class ConversionsContext implements Context, SnippetAcceptingContext {
	use AppConfiguration;
	use BasicStructure;
	use WebDav;

	/** @BeforeScenario */
	public function setUpScenario() {
		$this->asAn('admin');
		$this->setStatusTestingApp(true);
	}

	/** @AfterScenario */
	public function tearDownScenario() {
		$this->asAn('admin');
		$this->setStatusTestingApp(false);
	}

	protected function resetAppConfigs() {
	}

	/**
	 * @When /^user "([^"]*)" converts file "([^"]*)" to "([^"]*)"$/
	 */
	public function userConvertsTheSavedFileId(string $user, string $path, string $mime) {
		$this->userConvertsTheSavedFileIdTo($user, $path, $mime, null);
	}

	/**
	 * @When /^user "([^"]*)" converts file "([^"]*)" to "([^"]*)" and saves it to "([^"]*)"$/
	 */
	public function userConvertsTheSavedFileIdTo(string $user, string $path, string $mime, ?string $destination) {
		try {
			$fileId = $this->getFileIdForPath($user, $path);
		} catch (Exception $e) {
			// return a fake value to keep going and be able to test the error
			$fileId = 0;
		}

		$data = [['fileId', $fileId], ['targetMimeType', $mime]];
		if ($destination !== null) {
			$data[] = ['destination', $destination];
		}

		$this->asAn($user);
		$this->sendingToWith('post', '/apps/files/api/v1/convert', new TableNode($data));
	}
}
