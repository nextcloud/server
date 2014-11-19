<?php
/**
 * Copyright (c) 2014 Tom Needham <tom@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

class Test_Migrate extends PHPUnit_Framework_TestCase {

	public $users;
	public $tmpfiles = array();

	/** @var \OC\Files\Storage\Storage */
	private $originalStorage;

	protected function setUp() {
		parent::setUp();

		$this->originalStorage = \OC\Files\Filesystem::getStorage('/');
	}

	protected function tearDown() {
		$u = new OC_User();
		foreach($this->users as $user) {
			$u->deleteUser($user);
		}
		foreach($this->tmpfiles as $file) {
			\OC_Helper::rmdirr($file);
		}

		\OC\Files\Filesystem::mount($this->originalStorage, array(), '/');
		parent::tearDown();
	}

	/**
	 * Generates a test user and sets up their file system
	 * @return string the test users id
	 */
	public function generateUser() {
		$username = uniqid();
		\OC_User::createUser($username, 'password');
		\OC_Util::tearDownFS();
		\OC_User::setUserId('');
		\OC\Files\Filesystem::tearDown();
		\OC_Util::setupFS($username);
		$this->users[] = $username;
		return $username;
	}

	/**
	 * validates an export for a user
	 * checks for existence of export_info.json and file folder
	 * @param string $exportedUser the user that was exported
	 * @param string $path the path to the .zip export
	 * @param string $exportedBy
	 */
	public function validateUserExport($exportedBy, $exportedUser, $path) {
		$this->assertTrue(file_exists($path));
		// Extract
		$extract = get_temp_dir() . '/oc_import_' . uniqid();
		//mkdir($extract);
		$this->tmpfiles[] = $extract;
		$zip = new ZipArchive;
		$zip->open($path);
		$zip->extractTo($extract);
		$zip->close();
		$this->assertTrue(file_exists($extract.'/export_info.json'));
		$exportInfo = file_get_contents($extract.'/export_info.json');
		$exportInfo = json_decode($exportInfo);
		$this->assertNotNull($exportInfo);
		$this->assertEquals($exportedUser, $exportInfo->exporteduser);
		$this->assertEquals($exportedBy, $exportInfo->exportedby);
		$this->assertTrue(file_exists($extract.'/'.$exportedUser.'/files'));
	}

	public function testUserSelfExport() {
		// Create a user
		$user = $this->generateUser();
		\OC_User::setUserId($user);
		$export = \OC_Migrate::export($user);
		// Check it succeeded and exists
		$this->assertTrue(json_decode($export)->success);
		// Validate the export
		$this->validateUserExport($user, $user, json_decode($export)->data);
	}

	public function testUserOtherExport() {
		$user = $this->generateUser();
		$user2 = $this->generateUser();
		\OC_User::setUserId($user2);
		$export = \OC_Migrate::export($user);
		// Check it succeeded and exists
		$this->assertTrue(json_decode($export)->success);
		// Validate the export
		$this->validateUserExport($user2, $user, json_decode($export)->data);
	}
}
