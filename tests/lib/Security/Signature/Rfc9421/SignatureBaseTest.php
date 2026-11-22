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

	public function testSerializeBareItemEscapesQuotes(): void {
		$this->assertSame('"\\"hi\\""', SignatureBase::serializeBareItem('"hi"'));
		$this->assertSame('"\\\\"', SignatureBase::serializeBareItem('\\'));
	}

	public function testSerializeBareItemBoolean(): void {
		$this->assertSame('?1', SignatureBase::serializeBareItem(true));
		$this->assertSame('?0', SignatureBase::serializeBareItem(false));
	}
}
