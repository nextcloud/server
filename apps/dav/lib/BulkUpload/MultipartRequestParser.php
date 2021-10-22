<?php
/**
 * @copyright Copyright (c) 2021, Louis Chemineau <louis@chmn.me>
 *
 * @author Louis Chemineau <louis@chmn.me>
 *
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCA\DAV\BulkUpload;

use Sabre\HTTP\RequestInterface;
use Sabre\DAV\Exception;
use Sabre\DAV\Exception\BadRequest;
use Sabre\DAV\Exception\LengthRequired;
use OCP\AppFramework\Http;

class MultipartRequestParser {

	/** @var resource */
	private $stream;

	/** @var string */
	private $boundary = "";

	/** @var string */
	private $lastBoundary = "";

	/**
	 * @throws BadRequest
	 */
	public function __construct(RequestInterface $request) {
		$stream = $request->getBody();
		$contentType = $request->getHeader('Content-Type');

		if (!is_resource($stream)) {
			throw new BadRequest('Body should be of type resource');
		}

		if ($contentType === null) {
			throw new BadRequest("Content-Type can not be null");
		}

		$this->stream = $stream;

		$boundary = $this->parseBoundaryFromHeaders($contentType);
		$this->boundary = '--'.$boundary."\r\n";
		$this->lastBoundary = '--'.$boundary."--\r\n";
	}

	/**
	 * Parse the boundary from the Content-Type header.
	 * Example: Content-Type: "multipart/related; boundary=boundary_bf38b9b4b10a303a28ed075624db3978"
	 *
	 * @throws BadRequest
	 */
	private function parseBoundaryFromHeaders(string $contentType): string {
		try {
			[$mimeType, $boundary] = explode(';', $contentType);
			[$boundaryKey, $boundaryValue] = explode('=', $boundary);
		} catch (\Exception $e) {
			throw new BadRequest("Error while parsing boundary in Content-Type header.", Http::STATUS_BAD_REQUEST, $e);
		}

		$boundaryValue = trim($boundaryValue);

		// Remove potential quotes around boundary value.
		if (substr($boundaryValue, 0, 1) == '"' && substr($boundaryValue, -1) == '"') {
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
			throw new Exception("Unknown error while seeking content", Http::STATUS_INTERNAL_SERVER_ERROR);
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

		$content = $this->readPartContent($headers["content-length"], $headers["x-file-md5"]);

		return [$headers, $content];
	}

	/**
	 * Read the boundary and check its content.
	 *
	 * @throws BadRequest
	 */
	private function readBoundary(): string {
		if (!$this->isAtBoundary()) {
			throw new BadRequest("Boundary not found where it should be.");
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

			try {
				[$key, $value] = explode(':', $line, 2);
				$headers[strtolower(trim($key))] = trim($value);
			} catch (\Exception $e) {
				throw new BadRequest('An error occurred while parsing headers of a part', Http::STATUS_BAD_REQUEST, $e);
			}
		}

		if (!isset($headers["content-length"])) {
			throw new LengthRequired("The Content-Length header must not be null.");
		}

		if (!isset($headers["x-file-md5"])) {
			throw new BadRequest("The X-File-MD5 header must not be null.");
		}

		return $headers;
	}

	/**
	 * Return the content of a part of the multipart body.
	 *
	 * @throws Exception
	 * @throws BadRequest
	 */
	private function readPartContent(int $length, string $md5): string {
		$computedMd5 = $this->computeMd5Hash($length);

		if ($md5 !== $computedMd5) {
			throw new BadRequest("Computed md5 hash is incorrect.");
		}

		$content = stream_get_line($this->stream, $length);

		if ($content === false) {
			throw new Exception("Fail to read part's content.");
		}

		if (feof($this->stream)) {
			throw new Exception("Unexpected EOF while reading stream.");
		}

		// Read '\r\n'.
		stream_get_contents($this->stream, 2);

		return $content;
	}

	/**
	 * Compute the MD5 hash of the next x bytes.
	 */
	private function computeMd5Hash(int $length): string {
		$context = hash_init('md5');
		hash_update_stream($context, $this->stream, $length);
		fseek($this->stream, -$length, SEEK_CUR);
		return hash_final($context);
	}
}
