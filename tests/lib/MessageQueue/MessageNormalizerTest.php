<?php

declare(strict_types=1);

// SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
// SPDX-License-Identifier: AGPL-3.0-or-later

namespace Test\MessageQueue;

use OC\MessageQueue\MessageMetadataReader;
use OC\MessageQueue\MessageNormalizer;
use OCP\MessageQueue\Exception\InvalidMessageException;
use PHPUnit\Framework\Attributes\DataProvider;
use Test\MessageQueue\Fixtures\NestedMessage;
use Test\MessageQueue\Fixtures\Status;
use Test\MessageQueue\Fixtures\TestMessage;
use Test\TestCase;

final class MessageNormalizerTest extends TestCase {
	private MessageNormalizer $normalizer;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->normalizer = new MessageNormalizer(new MessageMetadataReader());
	}

	public function testRoundTrip(): void {
		$message = new TestMessage(
			id: 42,
			text: 'hello',
			status: Status::Disabled,
			date: new \DateTimeImmutable('2026-10-04 12:34:56.123456+02:00'),
			nested: new NestedMessage('child'),
			list: ['a' => [1, 2], 'b' => null],
			ratio: 2.5,
		);

		$data = $this->normalizer->normalize($message);
		$this->assertSame([
			'id' => 42,
			'text' => 'hello',
			'status' => 'disabled',
			'date' => '2026-10-04T12:34:56.123456+02:00',
			'nested' => ['name' => 'child'],
			'list' => ['a' => [1, 2], 'b' => null],
			'ratio' => 2.5,
		], $data);

		$json = json_decode(json_encode($data, JSON_THROW_ON_ERROR), true, flags: JSON_THROW_ON_ERROR);
		$this->assertIsArray($json);
		$this->assertEquals($message, $this->normalizer->denormalize(TestMessage::class, $json));
	}

	public function testDenormalizeUsesDefaultsForMissingValues(): void {
		$message = $this->normalizer->denormalize(TestMessage::class, ['id' => 1, 'ratio' => 3]);
		$this->assertEquals(new TestMessage(id: 1, ratio: 3.0), $message);
	}

	public function testNormalizeRejectsObjectsInArrays(): void {
		$this->expectException(InvalidMessageException::class);
		$this->normalizer->normalize(new TestMessage(id: 1, list: [Status::Active]));
	}

	/**
	 * @return array<string, array{array<string, mixed>}>
	 */
	public static function invalidDataProvider(): array {
		return [
			'missing required value' => [['text' => 'a']],
			'wrong scalar type' => [['id' => '1']],
			'unexpected null' => [['id' => null]],
			'invalid enum value' => [['id' => 1, 'status' => 'unknown']],
			'invalid date' => [['id' => 1, 'date' => 'yesterday']],
			'invalid nested message' => [['id' => 1, 'nested' => 'child']],
		];
	}

	#[DataProvider('invalidDataProvider')]
	public function testDenormalizeRejectsInvalidData(array $data): void {
		$this->expectException(InvalidMessageException::class);
		$this->normalizer->denormalize(TestMessage::class, $data);
	}
}
