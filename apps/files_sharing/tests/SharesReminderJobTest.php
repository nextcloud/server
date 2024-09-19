<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files_Sharing\Tests;

use OC\SystemConfig;
use OCA\Files_Sharing\SharesReminderJob;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\Constants;
use OCP\Defaults;
use OCP\Files\IMimeTypeLoader;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Mail\IMailer;
use OCP\Mail\IMessage;
use OCP\Share\IManager;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;

/**
 * Class SharesReminderJobTest
 *
 * @group DB
 *
 * @package OCA\Files_Sharing\Tests
 */
class SharesReminderJobTest extends \Test\TestCase {
	private SharesReminderJob $job;
	private IDBConnection $db;
	private IManager $shareManager;
	private IUserManager $userManager;
	private IMailer|MockObject $mailer;
	private string $user1;
	private string $user2;

	protected function setUp(): void {
		parent::setUp();

		$this->db = \OC::$server->get(IDBConnection::class);
		$this->shareManager = \OC::$server->get(IManager::class);
		$this->userManager = \OC::$server->get(IUserManager::class);
		$this->mailer = $this->createMock(IMailer::class);

		// Clear occasional leftover shares from other tests
		$this->db->executeUpdate('DELETE FROM `*PREFIX*share`');

		$this->user1 = $this->getUniqueID('user1_');
		$this->user2 = $this->getUniqueID('user2_');

		$user1 = $this->userManager->createUser($this->user1, 'longrandompassword');
		$user2 = $this->userManager->createUser($this->user2, 'longrandompassword');
		$user1->setSystemEMailAddress('user1@test.com');
		$user2->setSystemEMailAddress('user2@test.com');

		\OC::registerShareHooks(\OC::$server->get(SystemConfig::class));

		$this->job = new SharesReminderJob(
			\OC::$server->get(ITimeFactory::class),
			$this->db,
			\OC::$server->get(IManager::class),
			$this->userManager,
			\OC::$server->get(LoggerInterface::class),
			\OC::$server->get(IURLGenerator::class),
			\OC::$server->get(IFactory::class),
			$this->mailer,
			\OC::$server->get(Defaults::class),
			\OC::$server->get(IMimeTypeLoader::class),
		);
	}

	protected function tearDown(): void {
		$this->db->executeUpdate('DELETE FROM `*PREFIX*share`');

		$userManager = \OC::$server->get(IUserManager::class);
		$user1 = $userManager->get($this->user1);
		if ($user1) {
			$user1->delete();
		}
		$user2 = $userManager->get($this->user2);
		if ($user2) {
			$user2->delete();
		}

		$this->logout();

		parent::tearDown();
	}

	public function dataSharesReminder() {
		$someMail = 'test@test.com';
		$noExpirationDate = null;
		$today = new \DateTime();
		// For expiration dates, the time is always automatically set to zero by ShareAPIController
		$today->setTime(0, 0);
		$nearFuture = new \DateTime();
		$nearFuture->setTimestamp($today->getTimestamp() + 86400 * 1);
		$farFuture = new \DateTime();
		$farFuture->setTimestamp($today->getTimestamp() + 86400 * 2);
		$permissionRead = Constants::PERMISSION_READ;
		$permissionCreate = $permissionRead | Constants::PERMISSION_CREATE;
		$permissionUpdate = $permissionRead | Constants::PERMISSION_UPDATE;
		$permissionDelete = $permissionRead | Constants::PERMISSION_DELETE;
		$permissionAll = Constants::PERMISSION_ALL;

		return [
			// No reminders for folders without expiration date
			[$noExpirationDate, '', false, $permissionRead, false],
			[$noExpirationDate, '', false, $permissionCreate, false],
			[$noExpirationDate, '', true, $permissionDelete, false],
			[$noExpirationDate, '', true, $permissionCreate, false],
			[$noExpirationDate, $someMail, false, $permissionUpdate, false],
			[$noExpirationDate, $someMail, false, $permissionCreate, false],
			[$noExpirationDate, $someMail, true, $permissionRead, false],
			[$noExpirationDate, $someMail, true, $permissionAll, false],
			// No reminders for folders with expiration date in the far future
			[$farFuture, '', false, $permissionRead, false],
			[$farFuture, '', false, $permissionCreate, false],
			[$farFuture, '', true, $permissionDelete, false],
			[$farFuture, '', true, $permissionCreate, false],
			[$farFuture, $someMail, false, $permissionUpdate, false],
			[$farFuture, $someMail, false, $permissionCreate, false],
			[$farFuture, $someMail, true, $permissionRead, false],
			[$farFuture, $someMail, true, $permissionAll, false],
			/* Should send reminders for folders with expiration date in the near future
			if the folder is empty and the user has write permission */
			[$nearFuture, '', false, $permissionRead, false],
			[$nearFuture, '', false, $permissionCreate, false],
			[$nearFuture, '', true, $permissionDelete, false],
			[$nearFuture, '', true, $permissionCreate, true],
			[$nearFuture, $someMail, false, $permissionUpdate, false],
			[$nearFuture, $someMail, false, $permissionCreate, false],
			[$nearFuture, $someMail, true, $permissionRead, false],
			[$nearFuture, $someMail, true, $permissionAll, true],
		];
	}

	/**
	 * @dataProvider dataSharesReminder
	 *
	 * @param \DateTime|null $expirationDate Share expiration date
	 * @param string $email Share with this email. If empty, the share is of type TYPE_USER and the sharee is user2
	 * @param bool $isEmpty Is share folder empty?
	 * @param int $permissions
	 * @param bool $shouldBeReminded
	 */
	public function testSharesReminder(
		?\DateTime $expirationDate, string $email, bool $isEmpty, int $permissions, bool $shouldBeReminded,
	): void {
		$this->loginAsUser($this->user1);

		$user1Folder = \OC::$server->get(IRootFolder::class)->getUserFolder($this->user1);
		$testFolder = $user1Folder->newFolder('test');

		if (!$isEmpty) {
			$testFolder->newFile('some_file.txt', 'content');
		}

		$share = $this->shareManager->newShare();

		$share->setNode($testFolder)
			->setShareType(($email ? IShare::TYPE_EMAIL : IShare::TYPE_USER))
			->setPermissions($permissions)
			->setSharedBy($this->user1)
			->setSharedWith(($email ?: $this->user2))
			->setExpirationDate($expirationDate);
		$share = $this->shareManager->createShare($share);

		$this->logout();
		$messageMock = $this->createMock(IMessage::class);
		$this->mailer->method('createMessage')->willReturn($messageMock);
		$this->mailer
			->expects(($shouldBeReminded ? $this->once() : $this->never()))
			->method('send')
			->with($messageMock);
		$messageMock
			->expects(($shouldBeReminded ? $this->once() : $this->never()))
			->method('setTo')
			->with([$email ?: $this->userManager->get($this->user2)->getSystemEMailAddress()]);
		$this->assertSame(false, $share->getReminderSent());
		$this->job->run([]);
		$share = $this->shareManager->getShareById($share->getFullId());
		$this->assertEquals($shouldBeReminded, $share->getReminderSent());
	}
}
