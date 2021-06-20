<?php
/**
 * @copyright Copyright (c) 2020, Daniel Calviño Sánchez (danxuliu@gmail.com)
 *
 * @author Daniel Calviño Sánchez <danxuliu@gmail.com>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
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

		exec("kill " . $this->fakeSmtpServerPid);

		$this->invokingTheCommand('config:system:delete mail_smtpport');
	}

	/**
	 * @Given /^dummy mail server is listening$/
	 */
	public function dummyMailServerIsListening() {
		// Default smtpport (25) is restricted for regular users, so the
		// FakeSMTP uses 2525 instead.
		$this->invokingTheCommand('config:system:set mail_smtpport --value=2525 --type integer');

		$this->fakeSmtpServerPid = exec("php features/bootstrap/FakeSMTPHelper.php >/dev/null 2>&1 & echo $!");
	}
}
