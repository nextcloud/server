<?php
/**
 * SPDX-FileCopyrightText: 2020 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
trait Mail {
	// CommandLine trait is expected to be used in the class that uses this
	// trait.

	/**
	 * @var string
	 */
	private $fakeSmtpServerPid;

	/**
	 * @AfterScenario
	 */
	public function killDummyMailServer() {
		if (!$this->fakeSmtpServerPid) {
			return;
		}

		exec('kill ' . $this->fakeSmtpServerPid);

		$this->invokingTheCommand('config:system:delete mail_smtpport');
	}

	/**
	 * @Given /^dummy mail server is listening$/
	 */
	public function dummyMailServerIsListening() {
		// Default smtpport (25) is restricted for regular users, so the
		// FakeSMTP uses 2525 instead.
		$this->invokingTheCommand('config:system:set mail_smtpport --value=2525 --type integer');

		$this->fakeSmtpServerPid = exec('php features/bootstrap/FakeSMTPHelper.php >/dev/null 2>&1 & echo $!');
	}
}
