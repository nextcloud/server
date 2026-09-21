<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Tests\unit\BulkDelete;

use OCA\DAV\BulkDelete\BulkDeletePlugin;
use OCA\DAV\Connector\Sabre\File;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\Server;
use Sabre\DAV\SimpleCollection;
use Sabre\DAV\Tree;
use Sabre\HTTP\Request;
use Sabre\HTTP\Response;

class BulkDeletePluginTest extends TestCase {
   private Server $server;
   private Tree&MockObject $tree;
   private BulkDeletePlugin $plugin;
   private array $nodes = [];
   private array $deleted = [];

   protected function setUp(): void {
      $this->tree = $this->createMock(Tree::class);
      $this->tree->method('getNodeForPath')->willReturnCallback(function (string $path) {
         if (!isset($this->nodes[$path])) {
            throw new NotFound();
         }
         return $this->nodes[$path];
      });
      $this->tree->method('delete')->willReturnCallback(function (string $path): void {
         $this->nodes[$path]->delete();
         $this->deleted[] = $path;
         unset($this->nodes[$path]);
      });
      $this->server = new Server($this->tree);
      $this->server->setBaseUri('/nextcloud/remote.php/dav/');
      $this->plugin = new BulkDeletePlugin('alice', new NullLogger());
      $this->server->addPlugin($this->plugin);
   }

   private function addFile(string $path, int $id): File&MockObject {
      $file = $this->createMock(File::class);
      $file->method('getInternalFileId')->willReturn($id);
      $file->method('getInternalPath')->willReturn('files/' . $path);
      $this->nodes['files/alice/' . $path] = $file;
      return $file;
   }

   private function request(array $files): Response {
      $request = new Request('POST', '/nextcloud/remote.php/dav/bulk-delete', [
         'Content-Type' => 'application/json; charset=utf-8',
      ], json_encode(['files' => $files], JSON_THROW_ON_ERROR));
      $request->setBaseUrl($this->server->getBaseUri());
      $response = new Response();
      $this->server->httpRequest = $request;
      $this->server->httpResponse = $response;
      $this->server->invokeMethod($request, $response, false);
      self::assertSame($request, $this->server->httpRequest);
      self::assertSame($response, $this->server->httpResponse);
      return $response;
   }

   private function results(Response $response): array {
      self::assertSame(200, $response->getStatus());
      return json_decode($response->getBodyAsString(), true, 512, JSON_THROW_ON_ERROR);
   }

   public function testUsesDavLifecycleAndRestoresRequestContext(): void {
      $this->addFile('one.txt', 1);
      $this->addFile('two.txt', 2);
      $before = [];
      $after = [];
      $methods = [];
      $this->server->on('beforeUnbind', function (string $path) use (&$before): void {
         $before[] = $path;
      });
      $this->server->on('afterUnbind', function (string $path) use (&$after): void {
         $after[] = $path;
      });
      $this->server->on('beforeMethod:DELETE', function (Request $request) use (&$methods): void {
         self::assertSame($request, $this->server->httpRequest);
         $methods[] = $request->getMethod();
      });
      $response = $this->request([
         ['path' => '/one.txt', 'fileId' => 1],
         ['path' => '/two.txt', 'fileId' => 2],
      ]);
      self::assertSame([204, 204], array_column($this->results($response)['results'], 'status'));
      self::assertSame(['DELETE', 'DELETE'], $methods);
      self::assertSame($before, $this->deleted);
      self::assertSame($before, $after);
      self::assertSame('post', $this->server->transactionType);
      self::assertSame('no-store', $response->getHeader('Cache-Control'));
   }

   public function testExactlyOneLazyPluginInitializationAcrossSubrequests(): void {
      $initializations = 0;
      $deletes = 0;
      $this->server->once('beforeMethod:*', function () use (&$initializations, &$deletes): void {
         $initializations++;
         $this->server->on('beforeMethod:DELETE', function () use (&$deletes): void {
            $deletes++;
         });
      });
      $this->addFile('one', 1);
      $this->addFile('two', 2);
      $this->request([['path' => '/one', 'fileId' => 1], ['path' => '/two', 'fileId' => 2]]);
      self::assertSame(1, $initializations);
      self::assertSame(2, $deletes);
   }

   public function testFailureStopsBatchAndPreservesEarlierSuccesses(): void {
      $this->addFile('one', 1);
      $this->addFile('two', 2)->method('delete')->willThrowException(new Forbidden());
      $this->addFile('three', 3)->expects(self::never())->method('delete');
      $response = $this->request([
         ['path' => '/one', 'fileId' => 1],
         ['path' => '/two', 'fileId' => 2],
         ['path' => '/three', 'fileId' => 3],
      ]);
      $data = $this->results($response);
      self::assertSame([204, 403, 424], array_column($data['results'], 'status'));
      self::assertSame([true, true, false], array_column($data['results'], 'attempted'));
      self::assertTrue($data['stopped']);
      self::assertSame(['files/alice/one'], $this->deleted);
   }

   public function testStaleFileIdDoesNotDeleteReplacement(): void {
      $this->addFile('replacement', 99)->expects(self::never())->method('delete');
      $data = $this->results($this->request([['path' => '/replacement', 'fileId' => 1]]));
      self::assertSame(412, $data['results'][0]['status']);
      self::assertSame([], $this->deleted);
   }

   public function testMissingFileIsNotReportedAsDeleted(): void {
      $data = $this->results($this->request([['path' => '/missing', 'fileId' => 1]]));
      self::assertSame(404, $data['results'][0]['status']);
   }

   public function testFoldersAreRejectedByServer(): void {
      $this->nodes['files/alice/folder'] = new SimpleCollection('folder');
      $data = $this->results($this->request([['path' => '/folder', 'fileId' => 1]]));
      self::assertSame(403, $data['results'][0]['status']);
   }

   public function testMountRootFileIsRejected(): void {
      $file = $this->createMock(File::class);
      $file->method('getInternalPath')->willReturn('');
      $file->expects(self::never())->method('delete');
      $this->nodes['files/alice/shared-file'] = $file;
      $data = $this->results($this->request([['path' => '/shared-file', 'fileId' => 1]]));
      self::assertSame(403, $data['results'][0]['status']);
   }

   public function testBeforeMethodVetoIsRespected(): void {
      $this->addFile('one', 1)->expects(self::never())->method('delete');
      $this->server->on('beforeMethod:DELETE', function (): void {
         throw new Forbidden();
      }, 150);
      $data = $this->results($this->request([['path' => '/one', 'fileId' => 1]]));
      self::assertSame(403, $data['results'][0]['status']);
   }

   public function testSilentUnbindVetoCannotProduceFalseSuccess(): void {
      $this->addFile('one', 1)->expects(self::never())->method('delete');
      $this->server->on('beforeUnbind', static fn (): bool => false);
      $data = $this->results($this->request([['path' => '/one', 'fileId' => 1]]));
      self::assertSame(500, $data['results'][0]['status']);
   }

   public function testThrowableDoesNotLeakDetailsAndStopsBatch(): void {
      $this->addFile('one', 1)->method('delete')->willThrowException(new \RuntimeException('secret-storage-detail'));
      $this->addFile('two', 2)->expects(self::never())->method('delete');
      $response = $this->request([['path' => '/one', 'fileId' => 1], ['path' => '/two', 'fileId' => 2]]);
      self::assertStringNotContainsString('secret-storage-detail', $response->getBodyAsString());
      self::assertSame([500, 424], array_column($this->results($response)['results'], 'status'));
   }

   public function testUnicodeAndLiteralPercentAreEncodedExactlyOnce(): void {
      $names = ['café + #.txt', '日本語.txt', 'literal%2Fname.txt', 'emoji-😀.txt'];
      $files = [];
      foreach ($names as $index => $name) {
         $this->addFile('folder/' . $name, $index + 1);
         $files[] = ['path' => '/folder/' . $name, 'fileId' => $index + 1];
      }
      $data = $this->results($this->request($files));
      self::assertSame([204, 204, 204, 204], array_column($data['results'], 'status'));
      self::assertSame(array_map(static fn (string $name): string => 'files/alice/folder/' . $name, $names), $this->deleted);
   }

   public function testPathThatLooksLikeAnotherUsersNamespaceStaysUnderCurrentUser(): void {
      $this->addFile('files/bob/one', 1);
      $this->request([['path' => '/files/bob/one', 'fileId' => 1]]);
      self::assertSame(['files/alice/files/bob/one'], $this->deleted);
   }

   #[DataProvider('invalidFiles')]
   public function testInvalidEnvelopeHasNoSideEffects(array $files): void {
      $this->tree->expects(self::never())->method('delete');
      $this->expectException(BadRequest::class);
      $this->request($files);
   }

   public static function invalidFiles(): array {
      $one = ['path' => '/one', 'fileId' => 1];
      return [
         'empty' => [[]],
         'oversize count' => [array_fill(0, 101, $one)],
         'duplicate path and id' => [[$one, $one]],
         'duplicate id' => [[$one, ['path' => '/two', 'fileId' => 1]]],
         'relative' => [[['path' => 'one', 'fileId' => 1]]],
         'root' => [[['path' => '/', 'fileId' => 1]]],
         'traversal' => [[['path' => '/../bob/one', 'fileId' => 1]]],
         'dot segment' => [[['path' => '/foo/./one', 'fileId' => 1]]],
         'double slash' => [[['path' => '/foo//one', 'fileId' => 1]]],
         'backslash' => [[['path' => '/foo\\one', 'fileId' => 1]]],
         'control character' => [[['path' => "/foo\0one", 'fileId' => 1]]],
         'negative id' => [[['path' => '/one', 'fileId' => -1]]],
         'unsafe integer' => [[['path' => '/one', 'fileId' => 9007199254740992]]],
         'string id' => [[['path' => '/one', 'fileId' => '1']]],
         'non-list' => [['named' => $one]],
         'overlong path' => [[['path' => '/' . str_repeat('x', 4096), 'fileId' => 1]]],
         'late malformed item' => [[$one, ['path' => '/../bad', 'fileId' => 2]]],
      ];
   }

   public function testMalformedJsonIsRejected(): void {
      $request = new Request('POST', '/nextcloud/remote.php/dav/bulk-delete', ['Content-Type' => 'application/json'], '{');
      $request->setBaseUrl($this->server->getBaseUri());
      $this->expectException(BadRequest::class);
      $this->plugin->httpPost($request, new Response());
   }

   public function testContentTypeIsRequired(): void {
      $request = new Request('POST', '/nextcloud/remote.php/dav/bulk-delete');
      $request->setBaseUrl($this->server->getBaseUri());
      $this->expectException(UnsupportedMediaType::class);
      $this->plugin->httpPost($request, new Response());
   }

   public function testOversizedStreamIsRejectedWithoutDispatch(): void {
      $body = fopen('php://temp', 'w+');
      fwrite($body, str_repeat('x', BulkDeletePlugin::MAX_BODY_BYTES + 1));
      rewind($body);
      $request = new Request('POST', '/nextcloud/remote.php/dav/bulk-delete', ['Content-Type' => 'application/json'], $body);
      $request->setBaseUrl($this->server->getBaseUri());
      $response = new Response();
      try {
         self::assertFalse($this->plugin->httpPost($request, $response));
         self::assertSame(413, $response->getStatus());
         self::assertSame([], $this->deleted);
      } finally {
         fclose($body);
      }
   }

   public function testUnrelatedPostIsNotHandled(): void {
      $request = new Request('POST', '/nextcloud/remote.php/dav/bulk');
      $request->setBaseUrl($this->server->getBaseUri());
      self::assertTrue($this->plugin->httpPost($request, new Response()));
   }
}
