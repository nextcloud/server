<?php

namespace Test\DirectEditing;

use OC\DirectEditing\Manager;
use OC\Files\Node\File;
use OCP\AppFramework\Http\DataResponse;
use OCP\AppFramework\Http\NotFoundResponse;
use OCP\AppFramework\Http\Response;
use OCP\AppFramework\Http\TemplateResponse;
use OCP\DirectEditing\ACreateEmpty;
use OCP\DirectEditing\IEditor;
use OCP\DirectEditing\IToken;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\IDBConnection;
use OCP\IUserSession;
use OCP\Security\ISecureRandom;
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
	 * @var \PHPUnit\Framework\MockObject\MockObject
	 */
	private $random;

	protected function setUp() {
		parent::setUp();

		$this->editor = new Editor();

		$this->random = $this->createMock(ISecureRandom::class);
		$this->connection = \OC::$server->getDatabaseConnection();
		$this->userSession = $this->createMock(IUserSession::class);
		$this->rootFolder = $this->createMock(IRootFolder::class);
		$this->userFolder = $this->createMock(Folder::class);


		$this->rootFolder->expects($this->any())
			->method('getUserFolder')
			->willReturn($this->userFolder);

		$this->manager = new Manager(
			$this->random, $this->connection, $this->userSession, $this->rootFolder
		);

		$this->manager->registerDirectEditor($this->editor);
	}

	public function testEditorRegistration() {
		$this->assertEquals($this->manager->getEditors(), ['testeditor' => $this->editor]);
	}


	public function testCreateToken() {
		$expectedToken = 'TOKEN' . time();
		$file = $this->createMock(File::class);
		$file->expects($this->any())
			->method('getId')
			->willReturn(123);
		$this->random->expects($this->once())
			->method('generate')
			->willReturn($expectedToken);
		$this->userFolder->expects($this->once())
			->method('newFile')
			->willReturn($file);
		$token = $this->manager->create('/File.txt', 'testeditor', 'createEmpty');
		$this->assertEquals($token, $expectedToken);
	}

	public function testCreateTokenAccess() {
		$expectedToken = 'TOKEN' . time();
		$file = $this->createMock(File::class);
		$file->expects($this->any())
			->method('getId')
			->willReturn(123);
		$this->random->expects($this->once())
			->method('generate')
			->willReturn($expectedToken);
		$this->userFolder->expects($this->once())
			->method('newFile')
			->willReturn($file);
		$this->manager->create('/File.txt', 'testeditor', 'createEmpty');
		$firstResult = $this->manager->edit($expectedToken);
		$secondResult = $this->manager->edit($expectedToken);
		$this->assertInstanceOf(DataResponse::class, $firstResult);
		$this->assertInstanceOf(NotFoundResponse::class, $secondResult);
	}

}
