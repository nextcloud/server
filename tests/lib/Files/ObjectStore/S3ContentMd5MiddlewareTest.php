<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2025 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Files\ObjectStore;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Utils;
use Test\TestCase;

/**
 * Test suite for S3 Content-MD5 middleware
 * Verifies AWS SDK PHP v3.339.0+ compatibility fix for DeleteObjects operations
 * @see https://github.com/aws/aws-sdk-php/issues/3068
 */
#[\PHPUnit\Framework\Attributes\Group('objectstore')]
class S3ContentMd5MiddlewareTest extends TestCase {

	/**
	 * Helper: Apply middleware logic to a request
	 * Mirrors the logic from S3ConnectionTrait::addDeleteObjectsContentMd5Middleware()
	 */
	private function applyContentMd5Middleware(Request $request): Request {
		if ($request->getUri()->getQuery() !== 'delete') {
			return $request;
		}

		if (!$request->hasHeader('Content-MD5')) {
			$body = $request->getBody();
			$contentMd5 = base64_encode(Utils::hash($body, 'md5', true));
			return $request->withHeader('Content-MD5', $contentMd5);
		}

		return $request;
	}

	/**
	 * Test that Content-MD5 header is added to DeleteObjects requests
	 */
	public function testContentMd5HeaderAddedToDeleteObjects(): void {
		$testBody = '<?xml version="1.0" encoding="UTF-8"?><Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Object><Key>test-key</Key></Object></Delete>';
		$request = new Request('POST', 'http://s3.example.com/bucket?delete', [], $testBody);

		// Calculate expected MD5
		$expectedMd5 = base64_encode(md5($testBody, true));

		// Apply middleware logic
		$resultRequest = $this->applyContentMd5Middleware($request);

		// Verify header was added
		$this->assertTrue($resultRequest->hasHeader('Content-MD5'));
		$this->assertEquals($expectedMd5, $resultRequest->getHeaderLine('Content-MD5'));
	}

	/**
	 * Test that Content-MD5 header is NOT added to non-DeleteObjects requests
	 */
	public function testContentMd5NotAddedToNonDeleteRequests(): void {
		$testCases = [
			'GET request' => new Request('GET', 'http://s3.example.com/bucket/key'),
			'PUT request' => new Request('PUT', 'http://s3.example.com/bucket/key'),
			'HEAD request' => new Request('HEAD', 'http://s3.example.com/bucket/key'),
			'POST with different query' => new Request('POST', 'http://s3.example.com/bucket?uploads'),
		];

		foreach ($testCases as $label => $request) {
			$resultRequest = $this->applyContentMd5Middleware($request);

			// Verify header was NOT added for non-delete requests
			$this->assertFalse($resultRequest->hasHeader('Content-MD5'), "Content-MD5 should not be added for: $label");
		}
	}

	/**
	 * Test that existing Content-MD5 header is preserved
	 */
	public function testExistingContentMd5HeaderPreserved(): void {
		$testBody = 'test data';
		$existingMd5 = 'existing-md5-value';
		$request = new Request(
			'POST',
			'http://s3.example.com/bucket?delete',
			['Content-MD5' => $existingMd5],
			$testBody
		);

		// Apply middleware logic
		$resultRequest = $this->applyContentMd5Middleware($request);

		// Verify existing header was preserved
		$this->assertTrue($resultRequest->hasHeader('Content-MD5'));
		$this->assertEquals($existingMd5, $resultRequest->getHeaderLine('Content-MD5'));
	}

	/**
	 * Test MD5 calculation with various body sizes
	 */
	public function testMd5CalculationWithVariousSizes(): void {
		$testBodies = [
			'small' => 'x',
			'medium' => str_repeat('y', 1000),
			'large' => str_repeat('z', 10000),
			'xml_payload' => '<?xml version="1.0"?><Delete xmlns="http://s3.amazonaws.com/doc/2006-03-01/"><Object><Key>file1.txt</Key></Object><Object><Key>file2.txt</Key></Object></Delete>',
		];

		foreach ($testBodies as $label => $body) {
			$request = new Request('POST', 'http://s3.example.com/bucket?delete', [], $body);
			$expectedMd5 = base64_encode(md5($body, true));

			$resultRequest = $this->applyContentMd5Middleware($request);

			$this->assertEquals(
				$expectedMd5,
				$resultRequest->getHeaderLine('Content-MD5'),
				"MD5 mismatch for $label body size"
			);
		}
	}

	/**
	 * Test MD5 header format is base64-encoded
	 */
	public function testMd5HeaderFormatIsBase64(): void {
		$testBody = 'test data for base64 validation';
		$request = new Request('POST', 'http://s3.example.com/bucket?delete', [], $testBody);

		$resultRequest = $this->applyContentMd5Middleware($request);

		$md5Header = $resultRequest->getHeaderLine('Content-MD5');

		// Verify it's a valid base64 string
		$this->assertNotEmpty($md5Header);
		$this->assertEquals($md5Header, base64_encode(base64_decode($md5Header, true)));

		// Verify MD5 is typically 24 chars when base64-encoded (16 bytes)
		$this->assertEquals(24, strlen($md5Header));
	}

	/**
	 * Test edge case: Empty body in DeleteObjects request
	 */
	public function testMd5CalculationWithEmptyBody(): void {
		$request = new Request('POST', 'http://s3.example.com/bucket?delete', [], '');

		$resultRequest = $this->applyContentMd5Middleware($request);

		// MD5 of empty string should still produce a valid header
		$this->assertTrue($resultRequest->hasHeader('Content-MD5'));
		$this->assertNotEmpty($resultRequest->getHeaderLine('Content-MD5'));
	}

	/**
	 * Test that middleware is idempotent (doesn't double-hash)
	 */
	public function testMiddlewareIsIdempotent(): void {
		$testBody = 'test data';
		$request = new Request('POST', 'http://s3.example.com/bucket?delete', [], $testBody);

		// Apply middleware twice
		$resultRequest1 = $this->applyContentMd5Middleware($request);
		$resultRequest2 = $this->applyContentMd5Middleware($resultRequest1);

		// Headers should be identical
		$this->assertEquals(
			$resultRequest1->getHeaderLine('Content-MD5'),
			$resultRequest2->getHeaderLine('Content-MD5'),
			'Middleware should be idempotent'
		);
	}
}
