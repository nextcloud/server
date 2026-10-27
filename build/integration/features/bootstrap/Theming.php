<?php

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

use Behat\Hook\AfterScenario;
use Behat\Step\When;

require __DIR__ . '/autoload.php';

trait Theming {

	private bool $undoAllThemingChangesAfterScenario = false;

	#[AfterScenario]
	public function undoAllThemingChanges() {
		if (!$this->undoAllThemingChangesAfterScenario) {
			return;
		}

		$this->loggingInUsingWebAs('admin');
		$this->sendingAToWithRequesttoken('POST', '/index.php/apps/theming/ajax/undoAllChanges');

		$this->undoAllThemingChangesAfterScenario = false;
	}

	/**
	 *
	 * @param string $key
	 * @param string $source
	 */
	#[When('logged in admin uploads theming image for :key from file :source')]
	public function loggedInAdminUploadsThemingImageForFromFile(string $key, string $source) {
		$this->undoAllThemingChangesAfterScenario = true;

		$file = \GuzzleHttp\Psr7\Utils::streamFor(fopen($source, 'r'));

		$this->sendingAToWithRequesttoken('POST', '/index.php/apps/theming/ajax/uploadImage?key=' . $key,
			[
				'multipart' => [
					[
						'name' => 'image',
						'contents' => $file
					]
				]
			]);
		$this->theHTTPStatusCodeShouldBe('200');
	}
}
