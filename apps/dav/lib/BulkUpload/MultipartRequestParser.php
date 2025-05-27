<?php
/**
 * SPDX-FileCopyrightText: 2021 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-only
 */

namespace OCA\DAV\BulkUpload;

use OCP\AppFramework\Http;
use Psr\Log\LoggerInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\LengthRequired;
use Sabre\HTTP\RequestInterface;

class MultipartRequestParser {

	/** @var resource */
	private $stream;

	/** @var string */
	private $boundary = '';

	/** @var string */
	private $lastBoundary = '';

	/**
	 * @throws BadRequest
	 */
	public function __construct(
		RequestInterface $request,
		protected LoggerInterface $logger,
	) {
		$stream = $request->getBody();
		$contentType = $request->getHeader('Content-Type');

		if (!is_resource($stream)) {
			throw new BadRequest('Body should be of type resource');
		}

		if ($contentType === null) {
			throw new BadRequest('Content-Type can not be null');
		}

		$this->stream = $stream;

		$boundary = $this->parseBoundaryFromHeaders($contentType);
		$this->boundary = '--' . $boundary . "\r\n";
		$this->lastBoundary = '--' . $boundary . "--\r\n";
	}

	/**
	 * Parse the boundary from the Content-Type header.
	 * Example: Content-Type: "multipart/related; boundary=boundary_bf38b9b4b10a303a28ed075624db3978"
	 *
	 * @throws BadRequest
	 */
	private function parseBoundaryFromHeaders(string $contentType): string {
		try {
			if (!str_contains($contentType, ';')) {
				throw new \InvalidArgumentException('No semicolon in header');
			}
			[$mimeType, $boundary] = explode(';', $contentType);
			if (!str_contains($boundary, '=')) {
				throw new \InvalidArgumentException('No equal in boundary header');
			}
			[$boundaryKey, $boundaryValue] = explode('=', $boundary);
		} catch (\Exception $e) {
			throw new BadRequest('Error while parsing boundary in Content-Type header.', Http::STATUS_BAD_REQUEST, $e);
		}

		$boundaryValue = trim($boundaryValue);

		// Remove potential quotes around boundary value.
		if (str_starts_with($boundaryValue, '"') && str_ends_with($boundaryValue, '"')) {
			$boundaryValue = substr($boundaryValue, 1, -1);
		}

		if (trim($mimeType) !== 'multipart/related') {
			throw new BadRequest('Content-Type must be multipart/related');
		}

		if (trim($boundaryKey) !== 'boundary') {
			throw new BadRequest('Boundary is invalid');
		}

		return $boundaryValue;
	}

	/**
	 * Check whether the stream's cursor is sitting right before the provided string.
	 *
	 * @throws Exception
	 */
	private function isAt(string $expectedContent): bool {
		$expectedContentLength = strlen($expectedContent);

		$content = fread($this->stream, $expectedContentLength);
		if ($content === false) {
			throw new Exception('An error occurred while checking content');
		}

		$seekBackResult = fseek($this->stream, -$expectedContentLength, SEEK_CUR);
		if ($seekBackResult === -1) {
			throw new Exception('Unknown error while seeking content', Http::STATUS_INTERNAL_SERVER_ERROR);
		}

		return $expectedContent === $content;
	}


	/**
	 * Check whether the stream's cursor is sitting right before the boundary.
	 */
	private function isAtBoundary(): bool {
		return $this->isAt($this->boundary);
	}

	/**
	 * Check whether the stream's cursor is sitting right before the last boundary.
	 */
	public function isAtLastBoundary(): bool {
		return $this->isAt($this->lastBoundary);
	}

	/**
	 * Parse and return the next part of the multipart headers.
	 *
	 * Example:
	 * --boundary_azertyuiop
	 * Header1: value
	 * Header2: value
	 *
	 * Content of
	 * the part
	 *
	 */
	public function parseNextPart(): array {
		$this->readBoundary();

		$headers = $this->readPartHeaders();

		$length = (int)$headers['content-length'];

		$this->validateHash($length, $headers['x-file-md5'] ?? '', $headers['oc-checksum'] ?? '');
		$content = $this->readPartContent($length);

		return [$headers, $content];
	}

	/**
	 * Read the boundary and check its content.
	 *
	 * @throws BadRequest
	 */
	private function readBoundary(): string {
		if (!$this->isAtBoundary()) {
			throw new BadRequest('Boundary not found where it should be.');
		}

		return fread($this->stream, strlen($this->boundary));
	}

	/**
	 * Return the headers of a part of the multipart body.
	 *
	 * @throws Exception
	 * @throws BadRequest
	 * @throws LengthRequired
	 */
	private function readPartHeaders(): array {
		$headers = [];

		while (($line = fgets($this->stream)) !== "\r\n") {
			if ($line === false) {
				throw new Exception('An error occurred while reading headers of a part');
			}

			if (!str_contains($line, ':')) {
				$this->logger->error('Header missing ":" on bulk request: ' . json_encode($line));
				throw new Exception('An error occurred while reading headers of a part', Http::STATUS_BAD_REQUEST);
			}

			try {
				[$key, $value] = explode(':', $line, 2);
				$headers[strtolower(trim($key))] = trim($value);
			} catch (\Exception $e) {
				throw new BadRequest('An error occurred while parsing headers of a part', Http::STATUS_BAD_REQUEST, $e);
			}
		}

		if (!isset($headers['content-length'])) {
			throw new LengthRequired('The Content-Length header must not be null.');
		}

		// TODO: Drop $md5 condition when the latest desktop client that uses it is no longer supported.
		if (!isset($headers['x-file-md5']) && !isset($headers['oc-checksum'])) {
			throw new BadRequest('The hash headers must not be null.');
		}

		return $headers;
	}

	/**
	 * Return the content of a part of the multipart body.
	 *
	 * @throws Exception
	 * @throws BadRequest
	 */
	private function readPartContent(int $length): string {
		if ($length === 0) {
			$content = '';
		} else {
			$content = stream_get_line($this->stream, $length);
		}

		if ($content === false) {
			throw new Exception("Fail to read part's content.");
		}

		if ($length !== 0 && feof($this->stream)) {
			throw new Exception('Unexpected EOF while reading stream.');
		}

		// Read '\r\n'.
		stream_get_contents($this->stream, 2);

		return $content;
	}

	/**
	 * Compute the MD5 or checksum hash of the next x bytes.
	 * TODO: Drop $md5 argument when the latest desktop client that uses it is no longer supported.
	 */
	private function validateHash(int $length, string $fileMd5Header, string $checksumHeader): void {
		if ($checksumHeader !== '') {
			[$algorithm, $hash] = explode(':', $checksumHeader, 2);
		} elseif ($fileMd5Header !== '') {
			$algorithm = 'md5';
			$hash = $fileMd5Header;
		} else {
			throw new BadRequest('No hash provided.');
		}

		$context = hash_init($algorithm);
		hash_update_stream($context, $this->stream, $length);
		fseek($this->stream, -$length, SEEK_CUR);
		$computedHash = hash_final($context);
		if ($hash !== $computedHash) {
			throw new BadRequest("Computed $algorithm hash is incorrect ($computedHash).");
		}
	}
}
