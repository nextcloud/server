<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Controller;

use OCA\Files\Db\ResumableUploadMapper;
use OCA\Files\Response\AProblemResponse;
use OCP\AppFramework\Http;
use OCP\AppFramework\Http\Response;
use OCP\IRequest;
use OCP\IURLGenerator;
use OCP\Server;
use Test\TestCase;

/**
 * @group DB
 */
class ResumableUploadControllerTest extends TestCase {
	/**
	 * @psalm-param Callable(ResumableUploadController):Response $method
	 * @param array<string, string> $requestHeaders
	 * @param array<string, string>|array<string, int>|array<string, bool> $responseHeaders
	 */
	private function performRequest(
		array $requestHeaders,
		string $requestBody,
		int $responseStatusCode,
		array $responseHeaders,
		string $responseBody,
		callable $method,
	): Response {
		$request = $this->createMock(IRequest::class);
		$request
			->method('getHeader')
			->willReturnCallback(fn (string $name): string => $requestHeaders[$name] ?? '');

		$inputHandle = tmpfile();
		$this->assertNotFalse($inputHandle);
		if ($requestBody !== '') {
			$this->assertEquals(strlen($requestBody), fwrite($inputHandle, $requestBody));
			$this->assertEquals(0, fseek($inputHandle, 0));
		}

		$controller = new ResumableUploadController(
			'files',
			$request,
			'user',
			Server::get(IURLGenerator::class),
			Server::get(ResumableUploadMapper::class),
			$inputHandle,
		);

		/** @var Response $response */
		$response = $method($controller);
		$headers = $response->getHeaders();
		unset(
			$headers['X-Request-Id'],
			$headers['Content-Security-Policy'],
			$headers['Feature-Policy'],
			$headers['X-Robots-Tag'],
		);
		if ($headers['Cache-Control'] === 'no-cache, no-store, must-revalidate') {
			// Only remove if default so we don't have to check it every time
			unset($headers['Cache-Control']);
		}

		if (($responseHeaders[ResumableUploadController::HTTP_HEADER_LOCATION] ?? null) === true) {
			$this->assertMatchesRegularExpression('#http://localhost/index\.php/apps/files/upload/[a-z0-9.]+$#', $headers[ResumableUploadController::HTTP_HEADER_LOCATION]);
		} else {
			$this->assertEquals(null, $headers[ResumableUploadController::HTTP_HEADER_LOCATION] ?? null);
		}

		unset(
			$responseHeaders[ResumableUploadController::HTTP_HEADER_LOCATION],
			$headers[ResumableUploadController::HTTP_HEADER_LOCATION],
		);

		$this->assertEquals($responseStatusCode, $response->getStatus());
		$this->assertEquals($responseHeaders, $headers);
		$this->assertEquals($responseBody, $response->render());

		return $response;
	}

	private function getTokenFromResponse(Response $response): string {
		$parts = explode('/', (string)$response->getHeaders()[ResumableUploadController::HTTP_HEADER_LOCATION]);
		return end($parts);
	}

	public function testCreateComplete(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '3',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 3,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testCreateEmptyComplete(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '0',
			],
			'',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 0,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 0,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 0,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testCreateEmptyIncomplete(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '0',
			],
			'',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 0,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 0,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 0,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testCreateCompleteUnknownUploadLength(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 3,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testCreateWrongUploadLengthTooBig(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '4',
			],
			'abc',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '3',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
	}

	public function testCreateWrongUploadLengthTooSmall(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '2',
			],
			'abc',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 0,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
	}

	public function testCreateMismatchingUploadLengthAndContentLength(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '3',
				ResumableUploadController::HTTP_HEADER_CONTENT_LENGTH => '4',
			],
			'abc',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
	}

	public function testMissingUploadComplete(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'abc',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
	}

	public function testCreateUnsupported(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => '-1',
			],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$this->performRequest(
			[],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
	}

	public function testAppend(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '9',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
		$token = $this->getTokenFromResponse($response);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 9,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '3',
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'def',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 6,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 6,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 9,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '6',
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'ghi',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 9,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 9,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 9,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testAppendWrongUploadLength(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '10',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
		$token = $this->getTokenFromResponse($response);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 10,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '3',
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'def',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 6,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 6,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 10,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '6',
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'ghi',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 9,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 9,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 10,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testAppendMissingUploadOffset(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '9',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
		$token = $this->getTokenFromResponse($response);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 9,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'def',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 9,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testAppendWrongUploadOffset(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '9',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);
		$token = $this->getTokenFromResponse($response);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 9,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '4',
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'def',
			Http::STATUS_CONFLICT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => AProblemResponse::MEDIA_TYPE_PROBLEM_JSON,
			],
			'{"type":"https:\/\/iana.org\/assignments\/http-problem-types#mismatching-upload-offset","title":"offset from request does not match offset of resource","expected-offset":3,"provided-offset":4}',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 9,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}


	public function testAppendWrongContentType(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '6',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => 'text/plain',
			],
			'def',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
	}

	public function testAppendMissingContentType(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '6',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'def',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
	}

	public function testAppendAlreadyCompleted(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '3',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'def',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => AProblemResponse::MEDIA_TYPE_PROBLEM_JSON,
			],
			'{"type":"https:\/\/iana.org\/assignments\/http-problem-types#completed-upload","title":"upload is already completed"}',
			fn (ResumableUploadController $controller): Response => $controller->appendResource($token),
		);
	}

	public function testAppendUnsupported(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => '-1',
			],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource(''),
		);

		$this->performRequest(
			[],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource(''),
		);
	}

	public function testAppendNonExistent(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_CONTENT_TYPE => ResumableUploadController::MEDIA_TYPE_PARTIAL_UPLOAD,
			],
			'abc',
			Http::STATUS_NOT_FOUND,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->appendResource('404'),
		);
	}

	public function testCheckRejectUploadOffset(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '1',
			],
			'',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource(''),
		);
	}

	public function testCheckRejectUploadComplete(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
			],
			'',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource(''),
		);
	}

	public function testCheckRejectUploadLength(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '1',
			],
			'',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource(''),
		);
	}

	public function testCheckUnsupported(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => '-1',
			],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource(''),
		);

		$this->performRequest(
			[],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource(''),
		);
	}

	public function testCheckNonExistent(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NOT_FOUND,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource('404'),
		);
	}

	public function testDeleteComplete(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '3',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 3,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->deleteResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NOT_FOUND,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testDeleteIncomplete(): void {
		$response = $this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => '6',
			],
			'abc',
			Http::STATUS_CREATED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_LOCATION => true,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->createResource(),
		);

		$token = $this->getTokenFromResponse($response);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '0',
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => 3,
				ResumableUploadController::HTTP_HEADER_UPLOAD_LENGTH => 6,
				ResumableUploadController::HTTP_HEADER_CACHE_CONTROL => 'no-store',
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NO_CONTENT,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->deleteResource($token),
		);

		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NOT_FOUND,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->checkResource($token),
		);
	}

	public function testDeleteRejectUploadOffset(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_OFFSET => '1',
			],
			'',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->deleteResource(''),
		);
	}

	public function testDeleteRejectUploadComplete(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
				ResumableUploadController::HTTP_HEADER_UPLOAD_COMPLETE => '1',
			],
			'',
			Http::STATUS_BAD_REQUEST,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->deleteResource(''),
		);
	}

	public function testDeleteUnsupported(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => '-1',
			],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->deleteResource(''),
		);

		$this->performRequest(
			[],
			'',
			Http::STATUS_NOT_IMPLEMENTED,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->deleteResource(''),
		);
	}

	public function testDeleteNonExistent(): void {
		$this->performRequest(
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			Http::STATUS_NOT_FOUND,
			[
				ResumableUploadController::HTTP_HEADER_UPLOAD_DRAFT_INTEROP_VERSION => ResumableUploadController::UPLOAD_DRAFT_INTEROP_VERSION,
			],
			'',
			fn (ResumableUploadController $controller): Response => $controller->deleteResource('404'),
		);
	}
}
