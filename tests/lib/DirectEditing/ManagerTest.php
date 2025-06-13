<?php
/**
 * SPDX-FileCopyrightText: 2019-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace Test\DirectEditing;

use OC\DirectEditing\Manager;
use OC\Files\Node\File;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\DirectEditing\ACreateEmpty;
use OCP\DirectEditing\IEditor;
use OCP\DirectEditing\IToken;
use OCP\Encryption\IManager;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IL10N;
use OCP\IUser;
use OCP\IUserSession;
use OCP\L10N\IFactory;
use OCP\Security\ISecureRandom;
use OCP\Server;
use PHPUnit\Framework\MockObject\MockObject;
use Test\TestCase;

class CreateEmpty extends ACreateEmpty {
	public function getId(): string {
		return 'createEmpty';
	}

	public function getName(): string {
		return 'create empty file';
	}

	public function getExtension(): string {
		return '.txt';
	}

	public function getMimetype(): string {
		return 'text/plain';
	}
}

class Editor implements IEditor {
	public function getId(): string {
		return 'testeditor';
	}

	public function getName(): string {
		return 'Test editor';
	}

	public function getMimetypes(): array {
		return [ 'text/plain' ];
	}


	public function getMimetypesOptional(): array {
		return [];
	}

	public function getCreators(): array {
		return [
			new CreateEmpty()
		];
	}

	public function isSecure(): bool {
		return false;
	}


	public function open(IToken $token): Response {
		return new DataResponse('edit page');
	}
}

/**
 * Class ManagerTest
 *
 * @package Test\DirectEditing
 * @group DB
 */
class ManagerTest extends TestCase {
	private $manager;
	/**
	 * @var Editor
	 */
	private $editor;
	/**
	 * @var MockObject|ISecureRandom
	 */
	private $random;
	/**
	 * @var IDBConnection
	 */
	private $connection;
	/**
	 * @var MockObject|IUserSession
	 */
	private $userSession;
	/**
	 * @var MockObject|IRootFolder
	 */
	private $rootFolder;
	/**
	 * @var MockObject|Folder
	 */
	private $userFolder;
	/**
	 * @var MockObject|IL10N
	 */
	private $l10n;
	/**
	 * @var MockObject|IManager
	 */
	private $encryptionManager;

	protected function setUp(): void {
		parent::setUp();

		$this->editor = new Editor();

		$this->random = $this->createMock(ISecureRandom::class);
		$this->connection = Server::get(IDBConnection::class);
		$this->userSession = $this->createMock(IUserSession::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userFolder = $this->createMock(Folder::class);
		$this->l10n = $this->createMock(IL10N::class);
		$this->encryptionManager = $this->createMock(IManager::class);

		$l10nFactory = $this->createMock(IFactory::class);
		$l10nFactory->expects($this->once())
			->method('get')
			->willReturn($this->l10n);


		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->willReturn($this->userFolder);

		$user = $this->createMock(IUser::class);
		$user->expects(self::any())
			->method('getUID')
			->willReturn('admin');
		$this->userSession->expects(self::any())
			->method('getUser')
			->willReturn($user);

		$this->manager = new Manager(
			$this->random, $this->connection, $this->userSession, $this->rootFolder, $l10nFactory, $this->encryptionManager
		);

		$this->manager->registerDirectEditor($this->editor);
	}

	public function testEditorRegistration(): void {
		$this->assertEquals($this->manager->getEditors(), ['testeditor' => $this->editor]);
	}


	public function testCreateToken(): void {
		$expectedToken = 'TOKEN' . time();
		$file = $this->createMock(File::class);
		$file->expects($this->any())
			->method('getId')
			->willReturn(123);
		$this->random->expects($this->once())
			->method('generate')
			->willReturn($expectedToken);
		$folder = $this->createMock(Folder::class);
		$this->userFolder
			->method('nodeExists')
			->willReturnMap([
				['/File.txt', false],
				['/', true],
			]);
		$this->userFolder
			->method('get')
			->with('/')
			->willReturn($folder);
		$folder->expects($this->once())
			->method('newFile')
			->willReturn($file);
		$token = $this->manager->create('/File.txt', 'testeditor', 'createEmpty');
		$this->assertEquals($token, $expectedToken);
	}

	public function testCreateTokenAccess(): void {
		$expectedToken = 'TOKEN' . time();
		$file = $this->createMock(File::class);
		$file->expects($this->any())
			->method('getId')
			->willReturn(123);
		$this->random->expects($this->once())
			->method('generate')
			->willReturn($expectedToken);
		$folder = $this->createMock(Folder::class);
		$this->userFolder
			->method('nodeExists')
			->willReturnMap([
				['/File.txt', false],
				['/', true],
			]);
		$this->userFolder
			->method('get')
			->with('/')
			->willReturn($folder);
		$folder->expects($this->once())
			->method('newFile')
			->willReturn($file);
		$this->manager->create('/File.txt', 'testeditor', 'createEmpty');
		$firstResult = $this->manager->edit($expectedToken);
		$secondResult = $this->manager->edit($expectedToken);
		$this->assertInstanceOf(DataResponse::class, $firstResult);
		$this->assertInstanceOf(NotFoundResponse::class, $secondResult);
	}

	public function testOpenByPath(): void {
		$expectedToken = 'TOKEN' . time();
		$file = $this->createMock(File::class);
		$file->expects($this->any())
			->method('getId')
			->willReturn(123);
		$file->expects($this->any())
			->method('getPath')
			->willReturn('/admin/files/File.txt');
		$this->random->expects($this->once())
			->method('generate')
			->willReturn($expectedToken);
		$this->userFolder
			->method('nodeExists')
			->willReturnMap([
				['/File.txt', false],
				['/', true],
			]);
		$this->userFolder
			->method('get')
			->with('/File.txt')
			->willReturn($file);
		$this->userFolder
			->method('getRelativePath')
			->willReturn('/File.txt');
		$this->manager->open('/File.txt', 'testeditor');
		$firstResult = $this->manager->edit($expectedToken);
		$secondResult = $this->manager->edit($expectedToken);
		$this->assertInstanceOf(DataResponse::class, $firstResult);
		$this->assertInstanceOf(NotFoundResponse::class, $secondResult);
	}

	public function testOpenById(): void {
		$expectedToken = 'TOKEN' . time();
		$fileRead = $this->createMock(File::class);
		$fileRead->method('getPermissions')
			->willReturn(1);
		$fileRead->expects($this->any())
			->method('getId')
			->willReturn(123);
		$fileRead->expects($this->any())
			->method('getPath')
			->willReturn('/admin/files/shared_file.txt');
		$file = $this->createMock(File::class);
		$file->method('getPermissions')
			->willReturn(1);
		$file->expects($this->any())
			->method('getId')
			->willReturn(123);
		$file->expects($this->any())
			->method('getPath')
			->willReturn('/admin/files/File.txt');
		$this->random->expects($this->once())
			->method('generate')
			->willReturn($expectedToken);
		$folder = $this->createMock(Folder::class);
		$folder->expects($this->any())
			->method('getById')
			->willReturn([
				$fileRead,
				$file
			]);
		$this->userFolder
			->method('nodeExists')
			->willReturnMap([
				['/File.txt', false],
				['/', true],
			]);
		$this->userFolder
			->method('get')
			->with('/')
			->willReturn($folder);
		$this->userFolder
			->method('getRelativePath')
			->willReturn('/File.txt');

		$this->manager->open('/', 'testeditor', 123);
		$firstResult = $this->manager->edit($expectedToken);
		$secondResult = $this->manager->edit($expectedToken);
		$this->assertInstanceOf(DataResponse::class, $firstResult);
		$this->assertInstanceOf(NotFoundResponse::class, $secondResult);
	}

	public function testCreateFileAlreadyExists(): void {
		$this->expectException(\RuntimeException::class);
		$this->userFolder
			->method('nodeExists')
			->with('/File.txt')
			->willReturn(true);

		$this->manager->create('/File.txt', 'testeditor', 'createEmpty');
	}
}
