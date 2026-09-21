<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BulkDelete;

use OCA\DAV\Connector\Sabre\File;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\PreconditionFailed;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\Request;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\Response;
use Sabre\HTTP\ResponseInterface;

/**
 * Authenticated, bounded transport batching, not a storage-level bulk delete.
 * Each item still passes through the registered DAV DELETE handlers.
 */
class BulkDeletePlugin extends ServerPlugin {
   public const MAX_FILES = 100;
   public const MAX_BODY_BYTES = 1048576;
   public const MAX_PATH_BYTES = 4096;

   private Server $server;
   private ?RequestInterface $activeRequest = null;
   /** @var array{path: string, fileId: int}|null */
   private ?array $activeFile = null;

   public function __construct(
      private string $userId,
      private LoggerInterface $logger,
   ) {
   }

   #[\Override]
   public function initialize(Server $server): void {
      $this->server = $server;
      $server->on('method:POST', [$this, 'httpPost'], 10);
      $server->on('beforeMethod:DELETE', [$this, 'validateTarget'], 200);
   }

   /**
    * Parse and validate the entire envelope before dispatching any mutation.
    * A failed item stops its batch; later entries are explicitly not attempted.
    */
   public function httpPost(RequestInterface $request, ResponseInterface $response): bool {
      if ($request->getPath() !== 'bulk-delete') {
         return true;
      }

      $contentType = strtolower(trim(explode(';', $request->getHeader('Content-Type') ?? '')[0]));
      if ($contentType !== 'application/json') {
         throw new UnsupportedMediaType('Bulk deletion requires UTF-8 JSON');
      }

      // Read at most the limit plus one byte, including for chunked requests.
      $body = $request->getBody();
      $body = is_resource($body) ? stream_get_contents($body, self::MAX_BODY_BYTES + 1) : $body;
      if (!is_string($body) || strlen($body) > self::MAX_BODY_BYTES) {
         $response->setStatus(413);
         $response->setHeader('Content-Type', 'application/json; charset=utf-8');
         $response->setHeader('Cache-Control', 'no-store');
         $response->setBody('{"error":"request_too_large"}');
         return false;
      }

      $files = $this->parseFiles($body);
      $results = [];
      $stopped = false;
      foreach ($files as $file) {
         if ($stopped) {
            $results[] = $file + ['status' => 424, 'attempted' => false];
            continue;
         }

         $status = $this->deleteFile($file, $request);
         $results[] = $file + ['status' => $status, 'attempted' => true];
         $stopped = $status !== 204;
      }

      // This is a JSON application response, not a WebDAV XML multistatus.
      $response->setStatus(200);
      $response->setHeader('Content-Type', 'application/json; charset=utf-8');
      $response->setHeader('Cache-Control', 'no-store');
      $response->setBody(json_encode([
         'results' => $results,
         'stopped' => $stopped,
      ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
      return false;
   }

   /** @return list<array{path: string, fileId: int}> */
   private function parseFiles(string $body): array {
      try {
         $data = json_decode($body, true, 16, JSON_THROW_ON_ERROR);
      } catch (\JsonException) {
         throw new BadRequest('Invalid UTF-8 JSON');
      }
      if (!is_array($data) || !isset($data['files']) || !is_array($data['files'])
         || !array_is_list($data['files']) || count($data['files']) < 1
         || count($data['files']) > self::MAX_FILES) {
         throw new BadRequest('Supply between 1 and 100 files');
      }

      $files = [];
      $paths = [];
      $ids = [];
      foreach ($data['files'] as $file) {
         if (!is_array($file) || !isset($file['path'], $file['fileId'])
            || !is_string($file['path']) || !is_int($file['fileId'])
            || $file['fileId'] < 1 || $file['fileId'] > 9007199254740991) {
            throw new BadRequest('Each file requires a path and a positive, safe integer fileId');
         }
         $path = $file['path'];
         if (!str_starts_with($path, '/') || strlen($path) > self::MAX_PATH_BYTES
            || preg_match('/[\x00-\x1f\x7f\\\\]/', $path)) {
            throw new BadRequest('Invalid user-relative path');
         }
         foreach (explode('/', substr($path, 1)) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
               throw new BadRequest('Paths must be canonical and must name a file');
            }
         }
         if (isset($paths[$path]) || isset($ids[$file['fileId']])) {
            throw new BadRequest('Duplicate paths or file IDs are not allowed');
         }
         $paths[$path] = true;
         $ids[$file['fileId']] = true;
         // Discard all client-supplied keys except the two defined by the API.
         $files[] = ['path' => $path, 'fileId' => $file['fileId']];
      }
      return $files;
   }

   /**
    * Guard only our internally constructed request, after normal DAV before
    * handlers. The URL is built in the authenticated user's files namespace.
    */
   public function validateTarget(RequestInterface $request): void {
      if ($request !== $this->activeRequest || $this->activeFile === null) {
         return;
      }
      $node = $this->server->tree->getNodeForPath($request->getPath());
      if (!$node instanceof File || $node->getInternalPath() === '') {
         throw new Forbidden('Only regular files are supported by bulk deletion');
      }
      if ($node->getInternalFileId() !== $this->activeFile['fileId']) {
         throw new PreconditionFailed('The file identity changed; refresh the file list');
      }
   }

   /** @param array{path: string, fileId: int} $file */
   private function deleteFile(array $file, RequestInterface $outerRequest): int {
      // Encode each segment exactly once. A literal "%2F" remains a filename,
      // while a slash in the JSON path is a directory separator.
      $path = 'files/' . rawurlencode($this->userId) . '/'
         . implode('/', array_map('rawurlencode', explode('/', substr($file['path'], 1))));
      $headers = [];
      foreach (['Authorization', 'Cookie', 'requesttoken', 'X-Requested-With', 'User-Agent'] as $name) {
         $value = $outerRequest->getHeader($name);
         if ($value !== null) {
            $headers[$name] = $value;
         }
      }
      // Do not propagate POST bodies or conditional headers to unrelated files.
      $request = new Request('DELETE', $this->server->getBaseUri() . $path, $headers);
      $request->setBaseUrl($this->server->getBaseUri());
      $response = new Response(500);
      $previousRequest = $this->server->httpRequest;
      $previousResponse = $this->server->httpResponse;
      $previousTransaction = $this->server->transactionType;
      $this->activeRequest = $request;
      $this->activeFile = $file;
      $this->server->httpRequest = $request;
      $this->server->httpResponse = $response;

      try {
         // No HTTP loopback calls, no direct S3 deletion, and no SQL mutations.
         $this->server->invokeMethod($request, $response, false);
         $status = $response->getStatus();
         $this->server->emit('afterResponse', [$request, $response]);
         // A handler that stops without explicitly confirming 204 is a failure.
         return $status === 204 ? 204 : ($status >= 400 && $status <= 599 ? $status : 500);
      } catch (\Throwable $exception) {
         try {
            $this->server->emit('exception', [$exception]);
         } catch (\Throwable $listenerException) {
            $this->logger->error('Bulk delete exception listener failed', ['exception' => $listenerException]);
         }
         if ($exception instanceof Exception) {
            $status = $exception->getHTTPCode();
            return $status >= 400 && $status <= 599 ? $status : 500;
         }
         $this->logger->error('Bulk deletion failed', ['exception' => $exception]);
         return 500;
      } finally {
         $this->server->httpRequest = $previousRequest;
         $this->server->httpResponse = $previousResponse;
         $this->server->transactionType = $previousTransaction;
         $this->activeRequest = null;
         $this->activeFile = null;
      }
   }
}
