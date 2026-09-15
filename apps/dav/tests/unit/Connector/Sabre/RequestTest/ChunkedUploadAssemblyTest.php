<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\Connector\Sabre\RequestTest;

use OC\Files\Storage\Local;
use OC\Files\Storage\Wrapper\Wrapper;
use OC\Files\View;
use OCA\DAV\Connector\Sabre\CachingTree;
use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\LockPlugin;
use OCA\DAV\Connector\Sabre\Server;
use OCA\DAV\Upload\ChunkingPlugin;
use OCA\DAV\Upload\ChunkingV2Plugin;
use OCA\DAV\Upload\CleanupService;
use OCA\DAV\Upload\UploadHome;
use OCP\AppFramework\Http;
use OCP\Files\IRootFolder;
use OCP\ICacheFactory;
use OCP\IUserSession;
use Sabre\DAV\Exception as DavException;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\ICollection;
use Sabre\DAV\SimpleCollection;

/**
 * End-to-end test of the chunked upload assembly done by the final
 * `MOVE uploads/<user>/<transfer>/.file` against real storage: the request goes
 * through the real UploadHome/UploadFolder tree, ChunkingPlugin, FutureFile and
 * AssemblyStream into Directory::createFile()/File::put().
 *
 * The encryption subclasses run the identical scenarios on encrypted storage.
 */
#[\PHPUnit\Framework\Attributes\Group(name: 'DB')]
class ChunkedUploadAssemblyTest extends RequestTestCase {
	private const CHUNKS = [
		'00001' => [300000, 'a'],
		'00002' => [170000, 'b'],
	];

	protected function getSabreServer(View $view, $user, $password, ExceptionPlugin $exceptionPlugin) {
		$authBackend = new Auth($user, $password);
		$authPlugin = new \Sabre\DAV\Auth\Plugin($authBackend);

		$server = new Server();
		$server->setBaseUri('/');
		$server->addPlugin($authPlugin);
		$server->addPlugin(new LockPlugin());
		$server->addPlugin(new ChunkingV2Plugin(\OCP\Server::get(ICacheFactory::class)));
		$server->addPlugin(new ChunkingPlugin());
		$server->addPlugin($exceptionPlugin);

		// like ServerFactory, build the tree after authentication set up the
		// session and the filesystem
		$treeBuilt = false;
		$server->on('beforeMethod:*', function () use ($server, $view, $user, &$treeBuilt): void {
			if ($treeBuilt) {
				return;
			}
			$treeBuilt = true;
			$filesDir = new Directory($view, $view->getFileInfo(''));
			$uploads = $this->buildUploadsCollection($user);
			$server->tree = new CachingTree(new SimpleCollection('root', [$filesDir, $uploads]));
		}, 30);

		return $server;
	}

	/**
	 * The `uploads` branch of the tree, resolving to the real UploadHome the
	 * production RootCollection would return for this principal.
	 */
	private function buildUploadsCollection(string $user): ICollection {
		return new class($user) implements ICollection {
			private ?UploadHome $home = null;

			public function __construct(
				private string $user,
			) {
			}

			private function home(): UploadHome {
				if ($this->home === null) {
					$this->home = new UploadHome(
						['uri' => 'principals/' . $this->user],
						\OCP\Server::get(CleanupService::class),
						\OCP\Server::get(IRootFolder::class),
						\OCP\Server::get(IUserSession::class),
						\OCP\Server::get(\OCP\Share\IManager::class),
					);
				}
				return $this->home;
			}

			public function createFile($name, $data = null) {
				throw new Forbidden();
			}

			public function createDirectory($name) {
				throw new Forbidden();
			}

			public function getChild($name) {
				if ($name === $this->user) {
					return $this->home();
				}
				throw new NotFound();
			}

			public function getChildren() {
				return [$this->home()];
			}

			public function childExists($name) {
				return $name === $this->user;
			}

			public function delete() {
				throw new Forbidden();
			}

			public function getName() {
				return 'uploads';
			}

			public function setName($name) {
				throw new Forbidden();
			}

			public function getLastModified() {
				return 0;
			}
		};
	}

	private function expectedContent(): string {
		$content = '';
		foreach (self::CHUNKS as [$size, $char]) {
			$content .= str_repeat($char, $size);
		}
		return $content;
	}

	/**
	 * MKCOL the upload session and PUT the chunks, like a sync client does.
	 */
	private function uploadChunks(View $view, string $user, string $transfer): void {
		$response = $this->request($view, $user, 'pass', 'MKCOL', '/uploads/' . $user . '/' . $transfer);
		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());

		foreach (self::CHUNKS as $name => [$size, $char]) {
			$response = $this->request(
				$view,
				$user,
				'pass',
				'PUT',
				'/uploads/' . $user . '/' . $transfer . '/' . $name,
				str_repeat($char, $size)
			);
			$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		}
	}

	private function moveToDestination(View $view, string $user, string $transfer, string $target) {
		return $this->request(
			$view,
			$user,
			'pass',
			'MOVE',
			'/uploads/' . $user . '/' . $transfer . '/.file',
			null,
			[
				'Destination' => '/files/' . $target,
				'OC-Total-Length' => (string)strlen($this->expectedContent()),
			]
		);
	}

	private function listPartFiles(View $view): array {
		[$storage, $internalPath] = $view->resolvePath('');
		$files = [];
		foreach (scandir($storage->getLocalFile($internalPath)) as $file) {
			if (str_ends_with($file, '.part')) {
				$files[] = $file;
			}
		}
		return $files;
	}

	public function testAssembleChunkedUpload(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$this->uploadChunks($view, $user, 'chunking-42');
		$response = $this->moveToDestination($view, $user, 'chunking-42', 'target.txt');

		$this->assertEquals(Http::STATUS_CREATED, $response->getStatus());
		$this->assertEquals($this->expectedContent(), $view->file_get_contents('target.txt'));
		$this->assertEquals(strlen($this->expectedContent()), $view->getFileInfo('target.txt')->getSize());
		$this->assertEmpty($this->listPartFiles($view), 'No stray part files');
	}

	public function testAssembleChunkedUploadOverwrite(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('target.txt', 'the original content');

		$this->uploadChunks($view, $user, 'chunking-42');
		$response = $this->moveToDestination($view, $user, 'chunking-42', 'target.txt');

		$this->assertEquals(Http::STATUS_NO_CONTENT, $response->getStatus());
		$this->assertEquals($this->expectedContent(), $view->file_get_contents('target.txt'));
		$this->assertEquals(strlen($this->expectedContent()), $view->getFileInfo('target.txt')->getSize());
		$this->assertEmpty($this->listPartFiles($view), 'No stray part files');
	}

	/**
	 * An assembly whose chunk is shorter on storage than the filecache claims
	 * must fail loudly and must not touch the existing destination file or its
	 * catalog entry.
	 */
	public function testFailedAssemblyKeepsOriginal(): void {
		$user = self::getUniqueID();
		$view = $this->setupUser($user, 'pass');

		$view->file_put_contents('target.txt', 'the original content');
		$originalEtag = $view->getFileInfo('target.txt')->getEtag();

		$this->uploadChunks($view, $user, 'chunking-42');

		// truncate a chunk on storage behind the filecache's back, as an upload
		// interrupted between write and cache update leaves it
		// resolve down to the raw storage: on encrypted storage getLocalFile()
		// would hand out a decrypted temporary copy, not the stored file
		$uploadsView = new View('/' . $user . '/uploads');
		[$chunkStorage, $chunkInternalPath] = $uploadsView->resolvePath('chunking-42/00002');
		while ($chunkStorage instanceof Wrapper) {
			$chunkStorage = $chunkStorage->getWrapperStorage();
		}
		$this->assertInstanceOf(Local::class, $chunkStorage);
		$chunkFile = $chunkStorage->getSourcePath($chunkInternalPath);
		$handle = fopen($chunkFile, 'r+');
		// truncate on a block boundary so that an encrypted chunk still decrypts
		// cleanly, just short - matching a partially flushed write
		ftruncate($handle, 8192 * 11);
		fclose($handle);

		try {
			$status = $this->moveToDestination($view, $user, 'chunking-42', 'target.txt')->getStatus();
		} catch (DavException $e) {
			// the failure made it out of the request as an exception instead
			$status = $e->getHTTPCode();
		}
		// assert outside the try: catching \Exception here would also swallow
		// PHPUnit's own assertion failures
		$this->assertGreaterThanOrEqual(400, $status, 'A truncated assembly must not report success');

		$this->assertEquals('the original content', $view->file_get_contents('target.txt'));
		$this->assertEquals(strlen('the original content'), $view->getFileInfo('target.txt')->getSize());
		$this->assertEquals($originalEtag, $view->getFileInfo('target.txt')->getEtag());
		$this->assertEmpty($this->listPartFiles($view), 'No stray part files');
	}
}
