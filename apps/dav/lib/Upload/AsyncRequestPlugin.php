<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\Upload;

use OCP\AppFramework\Http;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\NotFound;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;

/**
 * Finalize chunked uploads asynchronously.
 *
 * Assembling a chunked upload can take a long time. A client that sets the
 * "X-Nc-AsyncRequest: true" header gets the status of the final MOVE pushed
 * through notify_push instead of waiting for the response:
 *
 * 1. On the MKCOL creating the upload folder the client receives the token of
 *    an anonymous notify_push session in "X-Nc-AsyncRequestId" and subscribes
 *    to it while the chunks are uploaded.
 * 2. On the MOVE the client echoes the token in "X-Nc-AsyncRequestId". The
 *    request is answered with "202 Accepted" before the upload is assembled
 *    and the outcome is pushed to the session afterwards.
 *
 * Whenever the header or the push transport is missing the request is handled
 * synchronously as before.
 */
class AsyncRequestPlugin extends ServerPlugin {
	public const REQUEST_HEADER = 'X-Nc-AsyncRequest';
	public const ID_HEADER = 'X-Nc-AsyncRequestId';

	public const STATUS_FINISHED = 'finished';
	public const STATUS_FAILED = 'failed';

	private Server $server;
	private ?string $destination = null;

	public function __construct(
		private readonly AsyncRequestService $service,
	) {
	}

	#[\Override]
	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('afterMethod:MKCOL', [$this, 'afterMkcol']);
		// Runs after authentication and after the file drop plugin rewrote the destination
		$server->on('beforeMethod:MOVE', [$this, 'beforeMove'], 1000);
		$server->on('afterMove', [$this, 'afterMove']);
	}

	/**
	 * Hand out (or confirm) the session token when an upload folder is created.
	 */
	public function afterMkcol(RequestInterface $request, ResponseInterface $response): void {
		if (!$this->isRequested($request)) {
			return;
		}

		try {
			$node = $this->server->tree->getNodeForPath($request->getPath());
		} catch (NotFound) {
			return;
		}
		if (!$node instanceof UploadFolder || !$this->service->isAvailable()) {
			return;
		}

		// A client may keep using one session for several uploads
		$token = (string)$request->getHeader(self::ID_HEADER);
		if ($this->service->resolveSessionId($token) === null) {
			$token = $this->service->createSessionToken();
		}
		$response->setHeader(self::ID_HEADER, $token);
	}

	/**
	 * Answer the MOVE of a chunked upload early and push the outcome afterwards.
	 *
	 * @return bool false when the request was fully handled here
	 */
	public function beforeMove(RequestInterface $request, ResponseInterface $response): bool {
		if (!$this->isRequested($request)) {
			return true;
		}

		$token = (string)$request->getHeader(self::ID_HEADER);
		try {
			$source = $this->server->tree->getNodeForPath($request->getPath());
		} catch (NotFound) {
			return true;
		}
		if (!$source instanceof FutureFile || !$this->service->isAvailable()) {
			return true;
		}
		$sessionId = $this->service->resolveSessionId($token);
		if ($sessionId === null) {
			return true;
		}

		// Everything that can fail cheaply is still reported synchronously
		$this->server->getCopyAndMoveInfo($request);
		if (!$this->server->checkPreconditions($request, $response)) {
			$this->server->sapi->sendResponse($response);
			return false;
		}

		$response->setStatus(Http::STATUS_ACCEPTED);
		$response->setHeader(self::ID_HEADER, $token);
		$response->setHeader('Content-Length', '0');
		$this->server->sapi->sendResponse($response);
		$this->service->finishRequest();

		$status = ['upload' => basename(dirname($request->getPath()))];
		try {
			$this->server->emit('method:MOVE', [$request, $response]);
			$status['status'] = self::STATUS_FINISHED;
			$status['http_status'] = $response->getStatus();
			$status['path'] = $this->getRelativeDestination();
		} catch (\Throwable $e) {
			$this->server->emit('exception', [$e]);
			$status['status'] = self::STATUS_FAILED;
			$status['http_status'] = $e instanceof Exception ? $e->getHTTPCode() : Http::STATUS_INTERNAL_SERVER_ERROR;
			$status['message'] = $e->getMessage();
		}
		$this->service->send($sessionId, $status);

		return false;
	}

	public function afterMove(string $source, string $destination): void {
		$this->destination = $destination;
	}

	private function isRequested(RequestInterface $request): bool {
		return strtolower(trim((string)$request->getHeader(self::REQUEST_HEADER))) === 'true';
	}

	/**
	 * The destination relative to the root the client uploads to,
	 * which is the part after "files/<share token or user id>/".
	 */
	private function getRelativeDestination(): ?string {
		if ($this->destination === null) {
			return null;
		}
		$parts = explode('/', trim($this->destination, '/'), 3);
		return $parts[2] ?? '';
	}
}
