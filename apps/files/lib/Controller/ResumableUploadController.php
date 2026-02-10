<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OCA\Files\Db\ResumableUpload;
use OCA\Files\Db\ResumableUploadMapper;
use OCA\Files\Response\CompleteUploadResponse;
use OCA\Files\Response\MismatchingOffsetResponse;
use OCP\AppFramework\Controller;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Attribute\FrontpageRoute;
use OCP\AppFramework\Http\Attribute\NoAdminRequired;
use OCP\AppFramework\Http\Attribute\NoCSRFRequired;
use OCP\AppFramework\Http\Attribute\OpenAPI;
use OCP\AppFramework\Http\Response;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\IRootFolder;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Server;

/**
 * Implementation of https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05
 * All functionality described by the draft RFC is excluded from OpenAPI, only the custom endpoint to finish the upload is included.
 */
class ResumableUploadController extends Controller {
	// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-4.2-2
	public const UPLOAD_DRAFT_INTEROP_VERSION = '6';

	public const MEDIA_TYPE_PARTIAL_UPLOAD = 'application/partial-upload';

	public const HTTP_HEADER_LOCATION = 'Location';

	public const HTTP_HEADER_CONTENT_LENGTH = 'Content-Length';

	public const HTTP_HEADER_CONTENT_TYPE = 'Content-Type';

	public const HTTP_HEADER_CACHE_CONTROL = 'Cache-Control';

	public const HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION = 'Upload-Draft-Interop-Version';

	public const HTTP_HEADER_UPLOAD_COMPLETE = 'Upload-Complete';

	public const HTTP_HEADER_UPLOAD_OFFSET = 'Upload-Offset';

	public const HTTP_HEADER_UPLOAD_LENGTH = 'Upload-Length';

	private const BASE_HEADERS = [
		self::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => self::UPLOAD_DRAFT_INTEROP_VERSION,
	];

	// Some constraints are only for append, not create
	private bool $isCreation = false;

	public function __construct(
		string $appName,
		IRequest $request,
		private readonly ?string $userId,
		private readonly IURLGenerator $urlGenerator,
		private readonly ResumableUploadMapper $mapper,
		/**
		 * Only meant for testing, there is no way to mock it otherwise
		 * @var ?resource $inputHandle
		 */
		private readonly mixed $inputHandle = null,
	) {
		parent::__construct($appName, $request);
	}

	private function isSupported(): bool {
		return $this->request->getHeader(self::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION) === self::UPLOAD_DRAFT_INTEROP_VERSION;
	}

	private function getUploadComplete(): ?bool {
		return match ($this->request->getHeader(self::HTTP_HEADER_UPLOAD_COMPLETE)) {
			'1' => true,
			'0' => false,
			default => null,
		};
	}

	private function getUploadOffset(): ?int {
		$value = $this->request->getHeader(self::HTTP_HEADER_UPLOAD_OFFSET);
		if ($value !== '') {
			return (int)$value;
		}

		return null;
	}

	private function getUploadLength(): ?int {
		$value = $this->request->getHeader(self::HTTP_HEADER_UPLOAD_LENGTH);
		if ($value !== '') {
			return (int)$value;
		}

		return null;
	}

	private function getContentLength(): ?int {
		$value = $this->request->getHeader(self::HTTP_HEADER_CONTENT_LENGTH);
		if ($value !== '') {
			return (int)$value;
		}

		return null;
	}

	private function getContentType(): ?string {
		$value = $this->request->getHeader(self::HTTP_HEADER_CONTENT_TYPE);
		if ($value !== '') {
			return $value;
		}

		return null;
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/upload', postfix: 'post')]
	#[FrontpageRoute(verb: 'PUT', url: '/upload', postfix: 'put')]
	#[FrontpageRoute(verb: 'PATCH', url: '/upload', postfix: 'patch')]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	public function createResource(): Response {
		if ($this->userId === null) {
			return new Response(Http::STATUS_UNAUTHORIZED, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		if (!$this->isSupported()) {
			return new Response(Http::STATUS_NOT_IMPLEMENTED, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-4-3
		$isUploadComplete = $this->getUploadComplete();
		if ($isUploadComplete === null) {
			return new Response(Http::STATUS_BAD_REQUEST, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-4-9
		$contentLength = $this->getContentLength();
		$uploadLength = $this->getUploadLength();
		if ($isUploadComplete && $contentLength !== null && $uploadLength !== null && $contentLength !== $uploadLength) {
			return new Response(Http::STATUS_BAD_REQUEST, self::BASE_HEADERS);
		}

		$token = uniqid('', true);
		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-4-10.1.1
		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-4-10.2.1
		$size = $uploadLength ?? ($isUploadComplete ? $contentLength : null);

		$upload = new ResumableUpload();
		$upload->setUserId($this->userId);
		$upload->setToken($token);
		// TODO: Generate a proper path
		$upload->setPath('/tmp/upload-' . $token);
		$upload->setSize($size);

		$this->mapper->insert($upload);

		$this->isCreation = true;
		return $this->appendResource($token);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'PATCH', url: '/upload/{token}')]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	public function appendResource(string $token): Response {
		if ($this->userId === null) {
			return new Response(Http::STATUS_UNAUTHORIZED, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		if (!$this->isSupported()) {
			return new Response(Http::STATUS_NOT_IMPLEMENTED, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-2
		if (!$this->isCreation && $this->getContentType() !== self::MEDIA_TYPE_PARTIAL_UPLOAD) {
			return new Response(Http::STATUS_BAD_REQUEST, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-5
		$upload = $this->mapper->findByToken($this->userId, $token);
		if (!$upload instanceof ResumableUpload) {
			return new Response(Http::STATUS_NOT_FOUND, self::BASE_HEADERS);
		}

		$tmpFileHandle = fopen($upload->getPath(), 'ab');
		if ($tmpFileHandle === false) {
			return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		$tmpFileStat = fstat($tmpFileHandle);
		if ($tmpFileStat === false) {
			return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		/** @var non-negative-int $tmpFileSize */
		$tmpFileSize = $tmpFileStat['size'];

		$headers = self::BASE_HEADERS;
		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-10
		$headers[self::HTTP_HEADER_UPLOAD_OFFSET] = $tmpFileSize;

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-11
		if ($upload->getComplete() === true) {
			return new CompleteUploadResponse($headers);
		}

		if (!$this->isCreation) {
			$uploadOffset = $this->getUploadOffset();

			// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-2
			if ($uploadOffset === null || $uploadOffset < 0) {
				return new Response(Http::STATUS_BAD_REQUEST, $headers);
			}

			// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-7
			if ($uploadOffset !== $tmpFileSize) {
				return new MismatchingOffsetResponse($tmpFileSize, $uploadOffset, $headers);
			}
		}

		$bodyHandle = $this->inputHandle ?? fopen('php://input', 'rb');
		if ($bodyHandle === false) {
			return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, $headers); // @codeCoverageIgnore
		}

		if ($upload->getSize() !== null) {
			$offset = 0;
			while (true) {
				$copied = stream_copy_to_stream($bodyHandle, $tmpFileHandle, 1024 * 1024 * 16, $offset);
				if ($copied === false) {
					return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, $headers); // @codeCoverageIgnore
				}

				if ($copied === 0) {
					// No more data, we can also skip checks since the size hasn't changed since the last checks
					break;
				}

				$offset += $copied;

				// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-15
				if ($upload->getSize() < $tmpFileSize + $copied) {
					return new Response(Http::STATUS_BAD_REQUEST, $headers);
				}
			}
		} else {
			$copied = stream_copy_to_stream($bodyHandle, $tmpFileHandle);
			if ($copied === false) {
				return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, $headers); // @codeCoverageIgnore
			}
		}

		fclose($bodyHandle);

		$tmpFileStat = fstat($tmpFileHandle);
		if ($tmpFileStat === false) {
			return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, $headers); // @codeCoverageIgnore
		}

		/** @var non-negative-int $tmpFileSize */
		$tmpFileSize = $tmpFileStat['size'];

		fclose($tmpFileHandle);

		$headers[self::HTTP_HEADER_UPLOAD_OFFSET] = $tmpFileSize;

		$isUploadComplete = $this->getUploadComplete();
		if ($isUploadComplete) {
			$upload->setComplete(true);

			// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-14
			if ($upload->getSize() === null) {
				$upload->setSize($tmpFileSize);
			}

			// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-14
			if ($tmpFileSize !== $upload->getSize()) {
				return new Response(Http::STATUS_BAD_REQUEST, $headers);
			}

			$this->mapper->update($upload);
		}

		if ($this->isCreation) {
			// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-4-4
			$headers[self::HTTP_HEADER_LOCATION] = $this->urlGenerator->linkToRouteAbsolute('files.ResumableUpload.appendResource', ['token' => $token]);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-12
		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-6-13
		$headers[self::HTTP_HEADER_UPLOAD_COMPLETE] = $upload->getComplete() ? '1' : '0';
		return new Response(Http::STATUS_CREATED, $headers);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	// The webserver will convert the HEAD request into a GET request, so we have to handle it this way
	#[FrontpageRoute(verb: 'GET', url: '/upload/{token}')]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	public function checkResource(string $token): Response {
		if ($this->userId === null) {
			return new Response(Http::STATUS_UNAUTHORIZED, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		if (!$this->isSupported()) {
			return new Response(Http::STATUS_NOT_IMPLEMENTED, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-5-2
		if ($this->getUploadOffset() !== null || $this->getUploadComplete() !== null || $this->getUploadLength() !== null) {
			return new Response(Http::STATUS_BAD_REQUEST, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-5-9
		$upload = $this->mapper->findByToken($this->userId, $token);
		if (!$upload instanceof ResumableUpload) {
			return new Response(Http::STATUS_NOT_FOUND, self::BASE_HEADERS);
		}

		$tmpFileHandle = fopen($upload->getPath(), 'rb');
		if ($tmpFileHandle === false) {
			return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		$tmpFileStat = fstat($tmpFileHandle);
		if ($tmpFileStat === false) {
			return new Response(Http::STATUS_INTERNAL_SERVER_ERROR, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		/** @var non-negative-int $tmpFileSize */
		$tmpFileSize = $tmpFileStat['size'];

		fclose($tmpFileHandle);

		$headers = self::BASE_HEADERS;

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-5-8
		$headers[self::HTTP_HEADER_CACHE_CONTROL] = 'no-store';

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-5-3
		$headers[self::HTTP_HEADER_UPLOAD_COMPLETE] = $upload->getComplete() ? '1' : '0';
		$headers[self::HTTP_HEADER_UPLOAD_OFFSET] = $tmpFileSize;
		$headers[self::HTTP_HEADER_UPLOAD_LENGTH] = $upload->getSize();
		return new Response(Http::STATUS_NO_CONTENT, $headers);
	}

	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'DELETE', url: '/upload/{token}')]
	#[OpenAPI(scope: OpenAPI::SCOPE_IGNORE)]
	public function deleteResource(string $token): Response {
		if ($this->userId === null) {
			return new Response(Http::STATUS_UNAUTHORIZED, self::BASE_HEADERS); // @codeCoverageIgnore
		}

		if (!$this->isSupported()) {
			return new Response(Http::STATUS_NOT_IMPLEMENTED, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-7-3
		if ($this->getUploadOffset() !== null || $this->getUploadComplete() !== null) {
			return new Response(Http::STATUS_BAD_REQUEST, self::BASE_HEADERS);
		}

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-7-6
		$upload = $this->mapper->findByToken($this->userId, $token);
		if (!$upload instanceof ResumableUpload) {
			return new Response(Http::STATUS_NOT_FOUND, self::BASE_HEADERS);
		}

		$path = $upload->getPath();
		if (file_exists($path)) {
			unlink($path);
		}

		$this->mapper->delete($upload);

		// https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#section-7-4
		return new Response(Http::STATUS_NO_CONTENT, self::BASE_HEADERS);
	}

	/**
	 * Finish the upload.
	 *
	 * @param string $token The token of the upload
	 * @param string $path The final path where the file will be moved to
	 * @param int $createdTimestamp The unix timestamp of when the file was created
	 * @param int $lastModifiedTimestamp The unix timestamp of when the file was last modified
	 * @param bool $overwrite Whether an existing file should be overwritten
	 * @return Response<Http::STATUS_NO_CONTENT|Http::STATUS_BAD_REQUEST|Http::STATUS_UNAUTHORIZED|Http::STATUS_NOT_FOUND|Http::STATUS_CONFLICT|Http::STATUS_INTERNAL_SERVER_ERROR, array{}>
	 *
	 * 204: Upload finished successfully
	 * 400: Upload not complete
	 * 401: User is unauthorized
	 * 404: Upload not found
	 * 409: File already exists
	 */
	#[NoAdminRequired]
	#[NoCSRFRequired]
	#[FrontpageRoute(verb: 'POST', url: '/upload/{token}/finish')]
	#[OpenAPI(scope: OpenAPI::SCOPE_DEFAULT)]
	public function finishUpload(
		string $token,
		string $path,
		int $createdTimestamp,
		int $lastModifiedTimestamp,
		bool $overwrite = false,
	): Response {
		if ($this->userId === null) {
			return new Response(Http::STATUS_UNAUTHORIZED); // @codeCoverageIgnore
		}

		$upload = $this->mapper->findByToken($this->userId, $token);
		if (!$upload instanceof ResumableUpload) {
			return new Response(Http::STATUS_NOT_FOUND);
		}

		if (!$upload->getComplete()) {
			return new Response(Http::STATUS_BAD_REQUEST);
		}

		$userFolder = Server::get(IRootFolder::class)->getUserFolder($this->userId);

		if ($userFolder->nodeExists($path)) {
			if (!$overwrite) {
				return new Response(Http::STATUS_CONFLICT);
			}

			$userFolder->get($path)->delete();
		}

		$tmpFileHandle = fopen($upload->getPath(), 'rb');
		$outFile = $userFolder->newFile($path);

		$outFile->putContent($tmpFileHandle);

		$userFolder->getStorage()->getCache()->put($outFile->getInternalPath(), [
			'creation_time' => $createdTimestamp,
			'upload_time' => time(),
			'mtime' => $lastModifiedTimestamp,
			// TODO: https://datatracker.ietf.org/doc/html/draft-ietf-httpbis-resumable-upload-05#name-upload-metadata
			'mimetype' => Server::get(IMimeTypeDetector::class)->detectPath($path),
		]);

		unlink($upload->getPath());
		$this->mapper->delete($upload);

		return new Response(Http::STATUS_NO_CONTENT);
	}
}
