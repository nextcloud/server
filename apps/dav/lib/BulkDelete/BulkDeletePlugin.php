<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\DAV\BulkDelete;

use OCA\DAV\Connector\Sabre\Directory;
use OCA\DAV\Connector\Sabre\File;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\Forbidden;
use Sabre\DAV\Exception\UnsupportedMediaType;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\HTTP\RequestInterface;
use Sabre\HTTP\ResponseInterface;
use Sabre\Xml\ParseException;

/**
 * Implements the non-standard BDELETE WebDAV method for bounded file batches.
 *
 * The request shape follows the Exchange BDELETE extension:
 *
 * <d:delete xmlns:d="DAV:">
 *   <d:target>
 *     <d:href>file1</d:href>
 *     <d:href>folder/file2</d:href>
 *   </d:target>
 * </d:delete>
 *
 * Targets are relative to the collection addressed by the BDELETE request.
 */
class BulkDeletePlugin extends ServerPlugin {
	public const MAX_FILES = 100;
	public const MAX_BODY_BYTES = 1048576;
	public const MAX_HREF_BYTES = 4096;

	private Server $server;

	public function __construct(
		private string $userId,
		private LoggerInterface $logger,
	) {
	}

	#[\Override]
	public function initialize(Server $server): void {
		$this->server = $server;
		$server->on('method:BDELETE', [$this, 'httpBulkDelete'], 10);
	}

	#[\Override]
	public function getPluginName(): string {
		return 'bulk-delete';
	}

	/**
	 * @param string $path
	 * @return string[]
	 */
	#[\Override]
	public function getHTTPMethods($path) {
		return $this->isUserFilesPath($path) ? ['BDELETE'] : [];
	}

	/**
	 * Delete each target through the standard DAV unbind lifecycle.
	 */
	public function httpBulkDelete(RequestInterface $request, ResponseInterface $response): bool {
		$containerPath = trim($request->getPath(), '/');
		if (!$this->isUserFilesPath($containerPath)) {
			return true;
		}

		$container = $this->server->tree->getNodeForPath($containerPath);
		if (!$container instanceof Directory) {
			throw new Forbidden('BDELETE target must be a collection');
		}

		$contentType = strtolower(trim(explode(';', $request->getHeader('Content-Type') ?? '')[0]));
		if (!in_array($contentType, ['application/xml', 'text/xml'], true)) {
			throw new UnsupportedMediaType('BDELETE requires an XML request body');
		}

		$body = $request->getBody();
		$body = is_resource($body) ? stream_get_contents($body, self::MAX_BODY_BYTES + 1) : $body;
		if (!is_string($body) || strlen($body) > self::MAX_BODY_BYTES) {
			$response->setStatus(413);
			$response->setHeader('Content-Length', '0');
			return false;
		}

		$targets = $this->parseTargets($body, $containerPath);
		$failures = [];
		$stopped = false;
		foreach ($targets as $target) {
			if ($stopped) {
				$failures[] = ['href' => $target['href'], 'status' => 424];
				continue;
			}

			$status = $this->deleteTarget($target['path']);
			if ($status !== 204) {
				$failures[] = ['href' => $target['href'], 'status' => $status];
				$stopped = true;
			}
		}

		$response->setHeader('Cache-Control', 'no-store');
		if ($failures === []) {
			$response->setStatus(204);
			$response->setHeader('Content-Length', '0');
			return false;
		}

		$response->setStatus(207);
		$response->setHeader('Content-Type', 'application/xml; charset=utf-8');
		$response->setBody($this->buildMultiStatus($failures));
		return false;
	}

	private function isUserFilesPath(string $path): bool {
		$path = trim($path, '/');
		$root = 'files/' . $this->userId;
		return $path === $root || str_starts_with($path, $root . '/');
	}

	/**
	 * @return list<array{href: string, path: string}>
	 */
	private function parseTargets(string $body, string $containerPath): array {
		if (stripos($body, '<!DOCTYPE') !== false) {
			throw new BadRequest('Invalid BDELETE XML body');
		}

		try {
			$elements = $this->server->xml->expect('{DAV:}delete', $body);
		} catch (ParseException) {
			throw new BadRequest('Invalid BDELETE XML body');
		}

		if ($elements === null) {
			$elements = [];
		}
		if (!is_array($elements)) {
			throw new BadRequest('BDELETE body must contain DAV: target elements');
		}

		$hrefs = [];
		foreach ($elements as $target) {
			if (!is_array($target) || ($target['name'] ?? null) !== '{DAV:}target') {
				throw new BadRequest('BDELETE body may only contain DAV: target elements');
			}

			$targetValue = $target['value'] ?? null;
			if (!is_array($targetValue) || $targetValue === []) {
				throw new BadRequest('DAV: target must contain at least one DAV: href');
			}

			foreach ($targetValue as $hrefElement) {
				if (!is_array($hrefElement) || ($hrefElement['name'] ?? null) !== '{DAV:}href'
					|| !is_string($hrefElement['value'] ?? null)) {
					throw new BadRequest('DAV: target may only contain DAV: href elements');
				}

				$href = trim($hrefElement['value']);
				if ($href === '') {
					throw new BadRequest('DAV: href must not be empty');
				}
				$hrefs[] = $href;
			}
		}

		if (count($hrefs) < 1 || count($hrefs) > self::MAX_FILES) {
			throw new BadRequest('BDELETE requires between 1 and 100 target hrefs');
		}

		$targets = [];
		$paths = [];
		foreach ($hrefs as $href) {
			$path = $this->resolveHref($containerPath, $href);
			if (isset($paths[$path])) {
				throw new BadRequest('Duplicate BDELETE targets are not allowed');
			}
			$paths[$path] = true;
			$targets[] = ['href' => $href, 'path' => $path];
		}
		return $targets;
	}

	private function resolveHref(string $containerPath, string $href): string {
		if (strlen($href) > self::MAX_HREF_BYTES
			|| str_starts_with($href, '/')
			|| str_contains($href, '?')
			|| str_contains($href, '#')
			|| preg_match('/[\x00-\x1f\x7f\\\\]/', $href)
		) {
			throw new BadRequest('BDELETE href must be a relative resource path');
		}

		$decoded = [];
		foreach (explode('/', $href) as $segment) {
			if ($segment === '' || preg_match('/%(?![0-9A-Fa-f]{2})/', $segment)) {
				throw new BadRequest('Invalid BDELETE href');
			}
			$segment = rawurldecode($segment);
			if ($segment === '' || $segment === '.' || $segment === '..'
				|| str_contains($segment, '/') || str_contains($segment, '\\')
				|| preg_match('/[\x00-\x1f\x7f]/', $segment)
				|| !mb_check_encoding($segment, 'UTF-8')
			) {
				throw new BadRequest('Invalid BDELETE href');
			}
			$decoded[] = $segment;
		}

		$path = $containerPath . '/' . implode('/', $decoded);
		if (strlen($path) > self::MAX_HREF_BYTES) {
			throw new BadRequest('BDELETE target path is too long');
		}
		return $path;
	}

	private function deleteTarget(string $path): int {
		try {
			$node = $this->server->tree->getNodeForPath($path);
			if (!$node instanceof File || $node->getInternalPath() === '') {
				return 403;
			}

			if (!$this->server->emit('beforeUnbind', [$path])) {
				return 403;
			}
			$this->server->tree->delete($path);
			$this->server->emit('afterUnbind', [$path]);
			return 204;
		} catch (Exception $exception) {
			$status = $exception->getHTTPCode();
			return $status >= 400 && $status <= 599 ? $status : 500;
		} catch (\Throwable $exception) {
			$this->logger->error('BDELETE target failed', [
				'path' => $path,
				'exception' => $exception,
			]);
			return 500;
		}
	}

	/**
	 * @param list<array{href: string, status: int}> $failures
	 */
	private function buildMultiStatus(array $failures): string {
		$writer = $this->server->xml->getWriter();
		$writer->openMemory();
		$writer->setIndent(true);
		$writer->startDocument();
		$writer->startElement('{DAV:}multistatus');

		foreach ($failures as $failure) {
			$writer->startElement('{DAV:}response');
			$writer->writeElement('{DAV:}href', $failure['href']);
			$writer->writeElement('{DAV:}status', $this->statusLine($failure['status']));
			$writer->endElement();
		}

		$writer->endElement();
		return $writer->outputMemory();
	}

	private function statusLine(int $status): string {
		$reason = match ($status) {
			400 => 'Bad Request',
			401 => 'Unauthorized',
			403 => 'Forbidden',
			404 => 'Not Found',
			405 => 'Method Not Allowed',
			409 => 'Conflict',
			412 => 'Precondition Failed',
			413 => 'Content Too Large',
			415 => 'Unsupported Media Type',
			423 => 'Locked',
			424 => 'Failed Dependency',
			429 => 'Too Many Requests',
			500 => 'Internal Server Error',
			503 => 'Service Unavailable',
			507 => 'Insufficient Storage',
			default => 'Error',
		};
		return 'HTTP/1.1 ' . $status . ' ' . $reason;
	}
}
