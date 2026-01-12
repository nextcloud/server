<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace Test\Share20;

use OC\Share20\Share;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IUserManager;
use OCP\Share\Exceptions\IllegalIDChangeException;
use OCP\Share\IShare;
use PHPUnit\Framework\MockObject\MockObject;

/**
 * Class ShareTest
 *
 * @package Test\Share20
 */
class ShareTest extends \Test\TestCase {
	/** @var IRootFolder|MockObject */
	protected $rootFolder;
	/** @var IUserManager|MockObject */
	protected $userManager;
	/** @var IShare */
	protected $share;

	protected function setUp(): void {
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userManager = $this->createMock(IUserManager::class);
		$this->share = new Share($this->rootFolder, $this->userManager);
	}

	public function testSetIdInvalidType(): void {
		$this->expectException(\TypeError::class);

		$this->share->setId(1.2);
	}

	public function testSetIdIntType(): void {
		$this->expectException(\TypeError::class);

		$this->share->setId(42);
	}

	public function testSetIdString(): void {
		$this->share->setId('foo');
		$this->assertEquals('foo', $this->share->getId());
	}

	public function testSetIdTrimsWhitespace(): void {
		$this->share->setId('  foo	');
		$this->assertEquals('foo', $this->share->getId());
	}

	public function testSetIdOnce(): void {
		$this->expectException(IllegalIDChangeException::class);
		$this->expectExceptionMessage('Not allowed to assign a new internal id to a share');

		$this->share->setId('foo');
		$this->share->setId('bar');
	}

	public function testGetIdBeforeSet(): void {
		$this->assertNull($this->share->getId());
	}

	public function testSetProviderIdInvalidType(): void {
		$this->expectException(\TypeError::class);

		$this->share->setProviderId(42);
	}

	public function testSetProviderIdString(): void {
		$this->share->setProviderId('foo');
		$this->share->setId('bar');
		$this->assertEquals('foo:bar', $this->share->getFullId());
	}

	public function testSetProviderIdTrimsWhitespace(): void {
		$this->share->setProviderId('  foo	');
		$this->share->setId('bar');
		$this->assertEquals('foo:bar', $this->share->getFullId());
	}

	public function testSetProviderIdOnce(): void {
		$this->expectException(IllegalIDChangeException::class);
		$this->expectExceptionMessage('Not allowed to assign a new provider id to a share');

		$this->share->setProviderId('foo');
		$this->share->setProviderId('bar');
	}

	public function testGetFullIdWithoutProviderId(): void {
		$this->expectException(\UnexpectedValueException::class);

		$this->share->setId('bar');
		$this->share->getFullId();
	}

	public function testGetFullIdWithoutId(): void {
		$this->expectException(\UnexpectedValueException::class);

		$this->share->setProviderId('foo');
		$this->share->getFullId();
	}

	public function testSetAndGetNode(): void {
		$node = $this->createMock(File::class);

		$this->share->setNode($node);
		$this->assertSame($node, $this->share->getNode());
	}

	public function testSetNodeResetsFileIdAndType(): void {
		$node = $this->createMock(File::class);

		$this->share->setNodeId(42);
		$this->share->setNodeType('file');
		$this->share->setNode($node);

		// After setting node, the internal fileId and nodeType should be reset
		$this->assertSame($node, $this->share->getNode());
	}

	public function testGetNodeWithoutSet(): void {
		$this->expectException(NotFoundException::class);
		$this->expectExceptionMessage('Share owner and file ID must be set');

		$this->share->getNode();
	}

	public function testSetAndGetNodeId(): void {
		$this->share->setNodeId(123);

		$node = $this->createMock(File::class);
		$node->method('getId')->willReturn(123);

		$this->share->setNode($node);
		$this->assertEquals(123, $this->share->getNodeId());
	}

	public function testSetNodeTypeFile(): void {
		$this->share->setNodeType('file');

		$file = $this->createMock(File::class);
		$this->share->setNode($file);

		$this->assertEquals('file', $this->share->getNodeType());
	}

	public function testSetNodeTypeFolder(): void {
		$this->share->setNodeType('folder');

		$folder = $this->createMock(Folder::class);
		$this->share->setNode($folder);

		$this->assertEquals('folder', $this->share->getNodeType());
	}

	public function testSetNodeTypeInvalid(): void {
		$this->expectException(\InvalidArgumentException::class);
		$this->expectExceptionMessage('Node type must be "file" or "folder"');

		$this->share->setNodeType('invalid');
	}

	public function testGetNodeTypeFromNode(): void {
		$file = $this->createMock(File::class);
		$this->share->setNode($file);

		$this->assertEquals('file', $this->share->getNodeType());
	}

	public function testSetAndGetShareType(): void {
		$this->share->setShareType(IShare::TYPE_USER);
		$this->assertEquals(IShare::TYPE_USER, $this->share->getShareType());
	}

	public function testGetShareTypeBeforeSet(): void {
		$this->assertNull($this->share->getShareType());
	}

	public function testSetAndGetSharedWith(): void {
		$this->share->setSharedWith('user1');
		$this->assertEquals('user1', $this->share->getSharedWith());
	}

	public function testSetAndGetSharedWithDisplayName(): void {
		$this->share->setSharedWithDisplayName('User One');
		$this->assertEquals('User One', $this->share->getSharedWithDisplayName());
	}

	public function testSetAndGetSharedWithAvatar(): void {
		$this->share->setSharedWithAvatar('avatar.jpg');
		$this->assertEquals('avatar.jpg', $this->share->getSharedWithAvatar());
	}

	public function testSetAndGetPermissions(): void {
		$this->share->setPermissions(19);
		$this->assertEquals(19, $this->share->getPermissions());
	}

	public function testSetAndGetStatus(): void {
		$this->share->setStatus(IShare::STATUS_ACCEPTED);
		$this->assertEquals(IShare::STATUS_ACCEPTED, $this->share->getStatus());
	}

	public function testSetAndGetNote(): void {
		$this->share->setNote('Test note');
		$this->assertEquals('Test note', $this->share->getNote());
	}

	public function testGetNoteDefault(): void {
		$this->assertEquals('', $this->share->getNote());
	}

	public function testSetAndGetLabel(): void {
		$this->share->setLabel('Public link label');
		$this->assertEquals('Public link label', $this->share->getLabel());
	}

	public function testGetLabelDefault(): void {
		$this->assertEquals('', $this->share->getLabel());
	}

	public function testSetAndGetExpirationDate(): void {
		$date = new \DateTime('2026-12-31');
		$this->share->setExpirationDate($date);
		$this->assertEquals($date, $this->share->getExpirationDate());
	}

	public function testSetExpirationDateNull(): void {
		$this->share->setExpirationDate(null);
		$this->assertNull($this->share->getExpirationDate());
	}

	public function testIsExpiredFalse(): void {
		$date = new \DateTime('+1 day');
		$this->share->setExpirationDate($date);
		$this->assertFalse($this->share->isExpired());
	}

	public function testIsExpiredTrue(): void {
		$date = new \DateTime('-1 day');
		$this->share->setExpirationDate($date);
		$this->assertTrue($this->share->isExpired());
	}

	public function testIsExpiredNoDate(): void {
		$this->assertFalse($this->share->isExpired());
	}

	public function testSetAndGetNoExpirationDate(): void {
		$this->share->setNoExpirationDate(true);
		$this->assertTrue($this->share->getNoExpirationDate());

		$this->share->setNoExpirationDate(false);
		$this->assertFalse($this->share->getNoExpirationDate());
	}

	public function testSetAndGetSharedBy(): void {
		$this->share->setSharedBy('user2');
		$this->assertEquals('user2', $this->share->getSharedBy());
	}

	public function testSetAndGetShareOwner(): void {
		$this->share->setShareOwner('owner');
		$this->assertEquals('owner', $this->share->getShareOwner());
	}

	public function testSetAndGetPassword(): void {
		$this->share->setPassword('secret');
		$this->assertEquals('secret', $this->share->getPassword());
	}

	public function testSetPasswordNull(): void {
		$this->share->setPassword(null);
		$this->assertNull($this->share->getPassword());
	}

	public function testSetAndGetPasswordExpirationTime(): void {
		$date = new \DateTime('2026-06-01');
		$this->share->setPasswordExpirationTime($date);
		$this->assertEquals($date, $this->share->getPasswordExpirationTime());
	}

	public function testSetAndGetSendPasswordByTalk(): void {
		$this->share->setSendPasswordByTalk(true);
		$this->assertTrue($this->share->getSendPasswordByTalk());

		$this->share->setSendPasswordByTalk(false);
		$this->assertFalse($this->share->getSendPasswordByTalk());
	}

	public function testGetSendPasswordByTalkDefault(): void {
		$this->assertFalse($this->share->getSendPasswordByTalk());
	}
	
	public function testSetTokenNull(): void {
		$this->share->setToken(null);
		$this->assertNull($this->share->getToken());
	}

	public function testSetAndGetToken(): void {
		$this->share->setToken('abc123');
		$this->assertEquals('abc123', $this->share->getToken());
	}

	public function testSetAndGetParent(): void {
		$this->share->setParent(42);
		$this->assertEquals(42, $this->share->getParent());
	}

	public function testGetParentBeforeSet(): void {
		$this->assertNull($this->share->getParent());
	}

	public function testSetAndGetTarget(): void {
		$this->share->setTarget('/shared/folder');
		$this->assertEquals('/shared/folder', $this->share->getTarget());
	}

	public function testSetAndGetShareTime(): void {
		$date = new \DateTime('2026-01-01');
		$this->share->setShareTime($date);
		$this->assertEquals($date, $this->share->getShareTime());
	}

	public function testSetAndGetMailSend(): void {
		$this->share->setMailSend(true);
		$this->assertTrue($this->share->getMailSend());

		$this->share->setMailSend(false);
		$this->assertFalse($this->share->getMailSend());
	}

	public function testSetAndGetHideDownload(): void {
		$this->share->setHideDownload(true);
		$this->assertTrue($this->share->getHideDownload());

		$this->share->setHideDownload(false);
		$this->assertFalse($this->share->getHideDownload());
	}

	public function testGetHideDownloadDefault(): void {
		$this->assertFalse($this->share->getHideDownload());
	}

	public function testSetAndGetReminderSent(): void {
		$this->share->setReminderSent(true);
		$this->assertTrue($this->share->getReminderSent());

		$this->share->setReminderSent(false);
		$this->assertFalse($this->share->getReminderSent());
	}

	public function testGetReminderSentDefault(): void {
		$this->assertFalse($this->share->getReminderSent());
	}

	public function testFluentInterface(): void {
		$result = $this->share
			->setId('id1')
			->setProviderId('provider1')
			->setShareType(IShare::TYPE_USER)
			->setSharedWith('user1')
			->setPermissions(31);

		$this->assertInstanceOf(Share::class, $result);
		$this->assertEquals('id1', $this->share->getId());
		$this->assertEquals('provider1:id1', $this->share->getFullId());
	}
}
