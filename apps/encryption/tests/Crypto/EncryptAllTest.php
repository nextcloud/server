<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Björn Schießle <bjoern@schiessle.org>
 * @author Joas Schilling <coding@schilljs.com>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */


namespace OCA\Encryption\Tests\Crypto;


use OCA\Encryption\Crypto\EncryptAll;
use Test\TestCase;

class EncryptAllTest extends TestCase {

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCA\Encryption\KeyManager */
	protected $keyManager;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCA\Encryption\Util */
	protected $util;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\IUserManager */
	protected $userManager;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCA\Encryption\Users\Setup */
	protected $setupUser;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OC\Files\View */
	protected $view;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\IConfig */
	protected $config;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\Mail\IMailer */
	protected $mailer;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\IL10N */
	protected $l;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \Symfony\Component\Console\Helper\QuestionHelper */
	protected $questionHelper;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \Symfony\Component\Console\Input\InputInterface */
	protected $inputInterface;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \Symfony\Component\Console\Output\OutputInterface */
	protected $outputInterface;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\UserInterface */
	protected $userInterface;

	/** @var  \PHPUnit_Framework_MockObject_MockObject | \OCP\Security\ISecureRandom  */
	protected $secureRandom;

	/** @var  EncryptAll */
	protected $encryptAll;

	function setUp() {
		parent::setUp();
		$this->setupUser = $this->getMockBuilder('OCA\Encryption\Users\Setup')
			->disableOriginalConstructor()->getMock();
		$this->keyManager = $this->getMockBuilder('OCA\Encryption\KeyManager')
			->disableOriginalConstructor()->getMock();
		$this->util = $this->getMockBuilder('OCA\Encryption\Util')
			->disableOriginalConstructor()->getMock();
		$this->userManager = $this->getMockBuilder('OCP\IUserManager')
			->disableOriginalConstructor()->getMock();
		$this->view = $this->getMockBuilder('OC\Files\View')
			->disableOriginalConstructor()->getMock();
		$this->config = $this->getMockBuilder('OCP\IConfig')
			->disableOriginalConstructor()->getMock();
		$this->mailer = $this->getMockBuilder('OCP\Mail\IMailer')
			->disableOriginalConstructor()->getMock();
		$this->l = $this->getMockBuilder('OCP\IL10N')
			->disableOriginalConstructor()->getMock();
		$this->questionHelper = $this->getMockBuilder('Symfony\Component\Console\Helper\QuestionHelper')
			->disableOriginalConstructor()->getMock();
		$this->inputInterface = $this->getMockBuilder('Symfony\Component\Console\Input\InputInterface')
			->disableOriginalConstructor()->getMock();
		$this->outputInterface = $this->getMockBuilder('Symfony\Component\Console\Output\OutputInterface')
			->disableOriginalConstructor()->getMock();
		$this->userInterface = $this->getMockBuilder('OCP\UserInterface')
			->disableOriginalConstructor()->getMock();


		$this->outputInterface->expects($this->any())->method('getFormatter')
			->willReturn($this->getMock('\Symfony\Component\Console\Formatter\OutputFormatterInterface'));

		$this->userManager->expects($this->any())->method('getBackends')->willReturn([$this->userInterface]);
		$this->userInterface->expects($this->any())->method('getUsers')->willReturn(['user1', 'user2']);

		$this->secureRandom = $this->getMockBuilder('OCP\Security\ISecureRandom')->disableOriginalConstructor()->getMock();
		$this->secureRandom->expects($this->any())->method('getMediumStrengthGenerator')->willReturn($this->secureRandom);
		$this->secureRandom->expects($this->any())->method('getLowStrengthGenerator')->willReturn($this->secureRandom);
		$this->secureRandom->expects($this->any())->method('generate')->willReturn('12345678');


		$this->encryptAll = new EncryptAll(
			$this->setupUser,
			$this->userManager,
			$this->view,
			$this->keyManager,
			$this->util,
			$this->config,
			$this->mailer,
			$this->l,
			$this->questionHelper,
			$this->secureRandom
		);
	}

	public function testEncryptAll() {
		/** @var EncryptAll  | \PHPUnit_Framework_MockObject_MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder('OCA\Encryption\Crypto\EncryptAll')
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);
		$encryptAll->expects($this->at(0))->method('createKeyPairs')->with();
		$encryptAll->expects($this->at(1))->method('encryptAllUsersFiles')->with();
		$encryptAll->expects($this->at(2))->method('outputPasswords')->with();

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);

	}

	public function testEncryptAllWithMasterKey() {
		/** @var EncryptAll  | \PHPUnit_Framework_MockObject_MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder('OCA\Encryption\Crypto\EncryptAll')
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['createKeyPairs', 'encryptAllUsersFiles', 'outputPasswords'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(true);
		$encryptAll->expects($this->never())->method('createKeyPairs');
		$this->keyManager->expects($this->once())->method('validateMasterKey');
		$encryptAll->expects($this->at(0))->method('encryptAllUsersFiles')->with();
		$encryptAll->expects($this->never())->method('outputPasswords');

		$encryptAll->encryptAll($this->inputInterface, $this->outputInterface);

	}

	public function testCreateKeyPairs() {
		/** @var EncryptAll  | \PHPUnit_Framework_MockObject_MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder('OCA\Encryption\Crypto\EncryptAll')
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['setupUserFS', 'generateOneTimePassword'])
			->getMock();


		// set protected property $output
		$this->invokePrivate($encryptAll, 'output', [$this->outputInterface]);

		$this->keyManager->expects($this->exactly(2))->method('userHasKeys')
			->willReturnCallback(
				function ($user) {
					if ($user === 'user1') {
						return false;
					}
					return true;
				}
			);

		$encryptAll->expects($this->once())->method('setupUserFS')->with('user1');
		$encryptAll->expects($this->once())->method('generateOneTimePassword')->with('user1')->willReturn('password');
		$this->setupUser->expects($this->once())->method('setupUser')->with('user1', 'password');

		$this->invokePrivate($encryptAll, 'createKeyPairs');

		$userPasswords = $this->invokePrivate($encryptAll, 'userPasswords');

		// we only expect the skipped user, because generateOneTimePassword which
		// would set the user with the new password was mocked.
		// This method will be tested separately
		$this->assertSame(1, count($userPasswords));
		$this->assertSame('', $userPasswords['user2']);
	}

	public function testEncryptAllUsersFiles() {
		/** @var EncryptAll  | \PHPUnit_Framework_MockObject_MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder('OCA\Encryption\Crypto\EncryptAll')
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['encryptUsersFiles'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);

		// set protected property $output
		$this->invokePrivate($encryptAll, 'output', [$this->outputInterface]);
		$this->invokePrivate($encryptAll, 'userPasswords', [['user1' => 'pwd1', 'user2' => 'pwd2']]);

		$encryptAll->expects($this->at(0))->method('encryptUsersFiles')->with('user1');
		$encryptAll->expects($this->at(1))->method('encryptUsersFiles')->with('user2');

		$this->invokePrivate($encryptAll, 'encryptAllUsersFiles');

	}

	public function testEncryptUsersFiles() {
		/** @var EncryptAll  | \PHPUnit_Framework_MockObject_MockObject  $encryptAll */
		$encryptAll = $this->getMockBuilder('OCA\Encryption\Crypto\EncryptAll')
			->setConstructorArgs(
				[
					$this->setupUser,
					$this->userManager,
					$this->view,
					$this->keyManager,
					$this->util,
					$this->config,
					$this->mailer,
					$this->l,
					$this->questionHelper,
					$this->secureRandom
				]
			)
			->setMethods(['encryptFile', 'setupUserFS'])
			->getMock();

		$this->util->expects($this->any())->method('isMasterKeyEnabled')->willReturn(false);

		$this->view->expects($this->at(0))->method('getDirectoryContent')
			->with('/user1/files')->willReturn(
				[
					['name' => 'foo', 'type'=>'dir'],
					['name' => 'bar', 'type'=>'file'],
				]
			);

		$this->view->expects($this->at(3))->method('getDirectoryContent')
			->with('/user1/files/foo')->willReturn(
				[
					['name' => 'subfile', 'type'=>'file']
				]
			);

		$this->view->expects($this->any())->method('is_dir')
			->willReturnCallback(
				function($path) {
					if ($path === '/user1/files/foo') {
						return true;
					}
					return false;
				}
			);

		$encryptAll->expects($this->at(1))->method('encryptFile')->with('/user1/files/bar');
		$encryptAll->expects($this->at(2))->method('encryptFile')->with('/user1/files/foo/subfile');

		$progressBar = $this->getMockBuilder('Symfony\Component\Console\Helper\ProgressBar')
			->disableOriginalConstructor()->getMock();

		$this->invokePrivate($encryptAll, 'encryptUsersFiles', ['user1', $progressBar, '']);

	}

	public function testGenerateOneTimePassword() {
		$password = $this->invokePrivate($this->encryptAll, 'generateOneTimePassword', ['user1']);
		$this->assertTrue(is_string($password));
		$this->assertSame(8, strlen($password));

		$userPasswords = $this->invokePrivate($this->encryptAll, 'userPasswords');
		$this->assertSame(1, count($userPasswords));
		$this->assertSame($password, $userPasswords['user1']);
	}

}
