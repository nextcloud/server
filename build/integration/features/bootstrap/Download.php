<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
use PHPUnit\Framework\Assert;

require __DIR__ . '/../../vendor/autoload.php';

trait Download {
	/** @var string **/
	private $downloadedFile;

	/** @AfterScenario **/
	public function cleanupDownloadedFile() {
		$this->downloadedFile = null;
	}

	/**
	 * @When user :user downloads zip file for entries :entries in folder :folder
	 */
	public function userDownloadsZipFileForEntriesInFolder($user, $entries, $folder) {
		$this->asAn($user);
		$this->sendingToDirectUrl('GET', "/index.php/apps/files/ajax/download.php?dir=" . $folder . "&files=[" . $entries . "]");
		$this->theHTTPStatusCodeShouldBe('200');

		$this->getDownloadedFile();
	}

	private function getDownloadedFile() {
		$this->downloadedFile = '';

		$body = $this->response->getBody();
		while (!$body->eof()) {
			$this->downloadedFile .= $body->read(8192);
		}
		$body->close();
	}

	/**
	 * @Then the downloaded zip file is a zip32 file
	 */
	public function theDownloadedZipFileIsAZip32File() {
		// assertNotContains is not used to prevent the whole file from being
		// printed in case of error.
		Assert::assertTrue(
			strpos($this->downloadedFile, "\x50\x4B\x06\x06") === false,
			"File contains the zip64 end of central dir signature"
		);
	}

	/**
	 * @Then the downloaded zip file is a zip64 file
	 */
	public function theDownloadedZipFileIsAZip64File() {
		// assertNotContains is not used to prevent the whole file from being
		// printed in case of error.
		Assert::assertTrue(
			strpos($this->downloadedFile, "\x50\x4B\x06\x06") !== false,
			"File does not contain the zip64 end of central dir signature"
		);
	}

	/**
	 * @Then the downloaded zip file contains a file named :fileName with the contents of :sourceFileName from :user data
	 */
	public function theDownloadedZipFileContainsAFileNamedWithTheContentsOfFromData($fileName, $sourceFileName, $user) {
		$fileHeaderRegExp = '/';
		$fileHeaderRegExp .= "\x50\x4B\x03\x04"; // Local file header signature
		$fileHeaderRegExp .= '.{22,22}'; // Ignore from "version needed to extract" to "uncompressed size"
		$fileHeaderRegExp .= preg_quote(pack('v', strlen($fileName)), '/'); // File name length
		$fileHeaderRegExp .= '(.{2,2})'; // Get "extra field length"
		$fileHeaderRegExp .= preg_quote($fileName, '/'); // File name
		$fileHeaderRegExp .= '/s'; // PCRE_DOTALL, so all characters (including bytes that happen to be new line characters) match

		// assertRegExp is not used to prevent the whole file from being printed
		// in case of error and to be able to get the extra field length.
		Assert::assertEquals(
			1, preg_match($fileHeaderRegExp, $this->downloadedFile, $matches),
			"Local header for file did not appear once in zip file"
		);

		$extraFieldLength = unpack('vextraFieldLength', $matches[1])['extraFieldLength'];
		$expectedFileContents = file_get_contents($this->getDataDirectory() . "/$user/files" . $sourceFileName);

		$fileHeaderAndContentRegExp = '/';
		$fileHeaderAndContentRegExp .= "\x50\x4B\x03\x04"; // Local file header signature
		$fileHeaderAndContentRegExp .= '.{22,22}'; // Ignore from "version needed to extract" to "uncompressed size"
		$fileHeaderAndContentRegExp .= preg_quote(pack('v', strlen($fileName)), '/'); // File name length
		$fileHeaderAndContentRegExp .= '.{2,2}'; // Ignore "extra field length"
		$fileHeaderAndContentRegExp .= preg_quote($fileName, '/'); // File name
		$fileHeaderAndContentRegExp .= '.{' . $extraFieldLength . ',' . $extraFieldLength . '}'; // Ignore "extra field"
		$fileHeaderAndContentRegExp .= preg_quote($expectedFileContents, '/'); // File contents
		$fileHeaderAndContentRegExp .= '/s'; // PCRE_DOTALL, so all characters (including bytes that happen to be new line characters) match

		// assertRegExp is not used to prevent the whole file from being printed
		// in case of error.
		Assert::assertEquals(
			1, preg_match($fileHeaderAndContentRegExp, $this->downloadedFile),
			"Local header and contents for file did not appear once in zip file"
		);
	}

	/**
	 * @Then the downloaded zip file contains a folder named :folderName
	 */
	public function theDownloadedZipFileContainsAFolderNamed($folderName) {
		$folderHeaderRegExp = '/';
		$folderHeaderRegExp .= "\x50\x4B\x03\x04"; // Local file header signature
		$folderHeaderRegExp .= '.{22,22}'; // Ignore from "version needed to extract" to "uncompressed size"
		$folderHeaderRegExp .= preg_quote(pack('v', strlen($folderName)), '/'); // File name length
		$folderHeaderRegExp .= '.{2,2}'; // Ignore "extra field length"
		$folderHeaderRegExp .= preg_quote($folderName, '/'); // File name
		$folderHeaderRegExp .= '/s'; // PCRE_DOTALL, so all characters (including bytes that happen to be new line characters) match

		// assertRegExp is not used to prevent the whole file from being printed
		// in case of error.
		Assert::assertEquals(
			1, preg_match($folderHeaderRegExp, $this->downloadedFile),
			"Local header for folder did not appear once in zip file"
		);
	}
}
