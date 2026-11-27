<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Security\Signature\Rfc9421;

use OC\Security\Signature\Rfc9421\SignatureBase;
use OCP\Security\Signature\Exceptions\SignatureException;
use Test\TestCase;

class SignatureBaseTest extends TestCase {
	public function testBuildBasicComponents(): void {
		$base = SignatureBase::build(
			method: 'POST',
			uri: 'https://example.org/foo?bar=baz',
			headers: [
				'content-digest' => 'sha-256=:abcd:',
				'date' => 'Mon, 04 May 2026 12:00:00 GMT',
			],
			components: ['@method', '@target-uri', 'content-digest', 'date'],
			signatureParamsLine: '("@method" "@target-uri" "content-digest" "date");created=1;keyid="k"',
		);

		$expected = '"@method": POST' . "\n"
			. '"@target-uri": https://example.org/foo?bar=baz' . "\n"
			. '"content-digest": sha-256=:abcd:' . "\n"
			. '"date": Mon, 04 May 2026 12:00:00 GMT' . "\n"
			. '"@signature-params": ("@method" "@target-uri" "content-digest" "date");created=1;keyid="k"';
		$this->assertSame($expected, $base);
	}

	public function testAuthorityStripsDefaultPort(): void {
		$base = SignatureBase::build('GET', 'https://EXAMPLE.org:443/x', [], ['@authority'], '()');
		$this->assertStringContainsString('"@authority": example.org' . "\n", $base);
	}

	public function testAuthorityKeepsCustomPort(): void {
		$base = SignatureBase::build('GET', 'https://example.org:8443/x', [], ['@authority'], '()');
		$this->assertStringContainsString('"@authority": example.org:8443' . "\n", $base);
	}

	public function testQueryComponent(): void {
		$base = SignatureBase::build('GET', 'https://example.org/x?a=1', [], ['@query'], '()');
		$this->assertStringContainsString('"@query": ?a=1' . "\n", $base);
	}

	public function testMissingFieldThrows(): void {
		$this->expectException(SignatureException::class);
		SignatureBase::build('GET', 'https://example.org/', [], ['x-missing'], '()');
	}

	public function testFieldValueIsTrimmed(): void {
		$base = SignatureBase::build(
			'GET',
			'https://example.org/',
			['date' => '  Mon, 04 May 2026 12:00:00 GMT  '],
			['date'],
			'()'
		);
		$this->assertStringContainsString('"date": Mon, 04 May 2026 12:00:00 GMT' . "\n", $base);
	}

	public function testSerializeSignatureParams(): void {
		$line = SignatureBase::serializeSignatureParams(
			['@method', '@target-uri'],
			['created' => 100, 'keyid' => 'kid', 'expires' => 200],
		);
		$this->assertSame('("@method" "@target-uri");created=100;keyid="kid";expires=200', $line);
	}

	public function testBuildResponseWithRequestComponents(): void {
		$components = [
			'@status',
			'content-type',
			'content-digest',
			'@method;req',
			'@target-uri;req',
			'signature-input;key="ocm";req',
			'signature;key="ocm";req',
		];
		$params = SignatureBase::serializeSignatureParams(
			$components,
			['created' => 100, 'keyid' => 'kid', 'tag' => 'ocm'],
		);

		$base = SignatureBase::buildResponse(
			status: 200,
			responseHeaders: [
				'content-type' => 'application/json',
				'content-digest' => 'sha-256=:response:',
			],
			requestMethod: 'GET',
			requestUri: 'https://example.org/ocm/mls-key-packages?userId=alice',
			requestHeaders: [
				'signature-input' => 'proxy=("@method");created=1, ocm=("@method");created=1;nonce="n";tag="ocm"',
				'signature' => 'proxy=:AAAA:, ocm=:BBBB:',
			],
			components: $components,
			signatureParamsLine: $params,
		);

		$expected = '"@status": 200' . "\n"
			. '"content-type": application/json' . "\n"
			. '"content-digest": sha-256=:response:' . "\n"
			. '"@method";req: GET' . "\n"
			. '"@target-uri";req: https://example.org/ocm/mls-key-packages?userId=alice' . "\n"
			. '"signature-input";key="ocm";req: ocm=("@method");created=1;nonce="n";tag="ocm"' . "\n"
			. '"signature";key="ocm";req: ocm=:BBBB:' . "\n"
			. '"@signature-params": ("@status" "content-type" "content-digest" "@method";req "@target-uri";req "signature-input";key="ocm";req "signature";key="ocm";req);created=100;keyid="kid";tag="ocm"';
		$this->assertSame($expected, $base);
	}

	public function testKeyedComponentWithMissingDictionaryEntryThrows(): void {
		$this->expectException(SignatureException::class);
		SignatureBase::buildResponse(
			status: 200,
			responseHeaders: ['content-type' => 'application/json'],
			requestMethod: 'GET',
			requestUri: 'https://example.org/ocm/x',
			requestHeaders: ['signature-input' => 'proxy=("@method");created=1'],
			components: ['signature-input;key="ocm";req'],
			signatureParamsLine: '()',
		);
	}

	public function testMalformedComponentIdentifierThrowsSignatureException(): void {
		// a component name containing ';' (e.g. "a;b" from a remote
		// Signature-Input) must surface as SignatureException, not as an
		// uncaught InvalidArgumentException
		foreach (['a;b', '', 'content-type;sf', '"quoted"', 'x;key='] as $component) {
			try {
				SignatureBase::serializeSignatureParams([$component], ['created' => 1]);
				$this->fail('expected SignatureException for ' . $component);
			} catch (SignatureException) {
				$this->addToAssertionCount(1);
			}
			try {
				SignatureBase::build('GET', 'https://example.org/', [], [$component], '()');
				$this->fail('expected SignatureException for ' . $component);
			} catch (SignatureException) {
				$this->addToAssertionCount(1);
			}
		}
	}

	public function testBuildRejectsReqParameterInRequestSignature(): void {
		$this->expectException(SignatureException::class);
		SignatureBase::build('GET', 'https://example.org/', [], ['@method;req'], '()');
	}

	public function testParseComponentIdentifier(): void {
		$this->assertSame(['@method', false, null], SignatureBase::parseComponentIdentifier('@method'));
		$this->assertSame(['signature-input', true, 'ocm'], SignatureBase::parseComponentIdentifier('signature-input;key="ocm";req'));
		$this->assertSame(['signature-input', true, null], SignatureBase::parseComponentIdentifier('signature-input;req'));
		$this->assertSame('"signature-input";key="ocm";req', SignatureBase::serializeComponentIdentifier('signature-input', true, 'ocm'));
	}

	public function testSerializeBareItemEscapesQuotes(): void {
		$this->assertSame('"\\"hi\\""', SignatureBase::serializeBareItem('"hi"'));
		$this->assertSame('"\\\\"', SignatureBase::serializeBareItem('\\'));
	}

	public function testSerializeBareItemBoolean(): void {
		$this->assertSame('?1', SignatureBase::serializeBareItem(true));
		$this->assertSame('?0', SignatureBase::serializeBareItem(false));
	}
}
