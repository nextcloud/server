<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace Test\Serializer;

use OC\Serializer\AttributeLoader;
use OC\Serializer\Serializer;
use OCP\Serializer\Format;
use OCP\Serializer\ISerializer;
use OCP\Server;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Test\TestCase;

class SerializerTest extends TestCase {
	private ISerializer $serializer;

	#[\Override]
	protected function setUp(): void {
		parent::setUp();
		$this->serializer = new Serializer();
	}

	public function testServiceIsRegistered(): void {
		$this->assertInstanceOf(Serializer::class, Server::get(ISerializer::class));
	}

	public function testSerializeOnlyIncludesRequestedGroup(): void {
		$dto = new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London', secret: 's3cr3t');

		$json = $this->serializer->serialize($dto, Format::JSON, ['groups' => ['basic']]);

		$this->assertJsonStringEqualsJsonString(
			'{"full_name":"Jane Doe","meta":{"city":"London"},"active":true}',
			$json,
		);
	}

	public function testSerializeAlwaysExcludesIgnoredProperty(): void {
		$dto = new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London', secret: 's3cr3t');

		$json = $this->serializer->serialize($dto);

		$this->assertStringNotContainsString('s3cr3t', $json);
		$this->assertStringNotContainsString('secret', $json);
	}

	public function testSerializeAlwaysExcludesIgnoredVirtualAttribute(): void {
		$dto = new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London', secret: 's3cr3t');

		$json = $this->serializer->serialize($dto);

		$this->assertStringNotContainsString('nope', $json);
		$this->assertStringNotContainsString('secretCode', $json);
	}

	public function testSerializeIncludesVirtualAttributeFromAccessorMethod(): void {
		$dto = new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London', secret: 's3cr3t');

		$json = $this->serializer->serialize($dto, Format::JSON, ['groups' => ['detailed']]);
		$data = json_decode($json, true);

		$this->assertSame(42, $data['user_score']);
	}

	public function testPropertyMetadataWinsOverAccessorWithSameDerivedName(): void {
		$classMetadata = (new ClassMetadataFactory(new AttributeLoader()))->getMetadataFor(SerializerCollisionTestDto::class);

		$attributeMetadata = $classMetadata->getAttributesMetadata()['name'];

		$this->assertSame('property_name', $attributeMetadata->getSerializedName());
	}

	public function testSerializeToXml(): void {
		$dto = new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London', secret: 's3cr3t');

		$xml = $this->serializer->serialize($dto, Format::XML, ['groups' => ['basic']]);

		$this->assertStringContainsString('<full_name>Jane Doe</full_name>', $xml);
		$this->assertStringNotContainsString('221B Baker Street', $xml);
	}

	public function testSerializeToCsv(): void {
		$dtos = [
			new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London'),
			new SerializerTestDto(name: 'John Doe', address: '10 Downing Street', city: 'London'),
		];

		$csv = $this->serializer->serialize($dtos, Format::CSV, ['groups' => ['basic']]);

		$this->assertSame(
			"full_name,meta.city,active\n"
			. "\"Jane Doe\",London,1\n"
			. "\"John Doe\",London,1\n",
			$csv,
		);
	}

	public function testDeserializeRoundTripsRenamedAndNestedProperties(): void {
		$dto = new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London');
		$json = $this->serializer->serialize($dto);

		/** @var SerializerTestDto $deserialized */
		$deserialized = $this->serializer->deserialize($json, SerializerTestDto::class, Format::JSON);

		$this->assertSame('Jane Doe', $deserialized->name);
		$this->assertSame('221B Baker Street', $deserialized->address);
		$this->assertSame('London', $deserialized->city);
	}

	public function testSerializeArrayOfObjects(): void {
		$dtos = [
			new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London'),
			new SerializerTestDto(name: 'John Doe', address: '10 Downing Street', city: 'London'),
		];

		$json = $this->serializer->serialize($dtos, Format::JSON, ['groups' => ['basic']]);

		$this->assertJsonStringEqualsJsonString(
			'[{"full_name":"Jane Doe","meta":{"city":"London"},"active":true},'
			. '{"full_name":"John Doe","meta":{"city":"London"},"active":true}]',
			$json,
		);
	}

	public function testDeserializeArrayOfObjects(): void {
		$dtos = [
			new SerializerTestDto(name: 'Jane Doe', address: '221B Baker Street', city: 'London'),
			new SerializerTestDto(name: 'John Doe', address: '10 Downing Street', city: 'Paris'),
		];
		$json = $this->serializer->serialize($dtos);

		/** @var SerializerTestDto[] $deserialized */
		$deserialized = $this->serializer->deserialize($json, SerializerTestDto::class . '[]', Format::JSON);

		$this->assertCount(2, $deserialized);
		$this->assertContainsOnlyInstancesOf(SerializerTestDto::class, $deserialized);
		$this->assertSame('Jane Doe', $deserialized[0]->name);
		$this->assertSame('Paris', $deserialized[1]->city);
	}

	public function testDeserializeArrayOfObjectsFailsIfOneEntryIsInvalid(): void {
		// ArrayDenormalizer denormalizes each entry through the same object denormalizer and
		// does not catch its errors, so one invalid entry (missing the required "$address") is
		// enough to fail the whole array, even though the first entry would have been valid.
		$json = '[{"full_name":"Jane Doe","address":"221B Baker Street","meta":{"city":"London"}},{"meta":{"city":"Paris"}}]';

		$this->expectException(\RuntimeException::class);
		$this->expectExceptionMessage('$address');
		$this->serializer->deserialize($json, SerializerTestDto::class . '[]', Format::JSON);
	}

	public function testSerializeAndDeserializeScalarArrayProperty(): void {
		$dto = new SerializerArrayTestDto(['first', 'second', 'third']);

		$json = $this->serializer->serialize($dto, Format::JSON, ['groups' => ['basic']]);
		$this->assertJsonStringEqualsJsonString('{"tags":["first","second","third"]}', $json);

		/** @var SerializerArrayTestDto $deserialized */
		$deserialized = $this->serializer->deserialize($json, SerializerArrayTestDto::class, Format::JSON);
		$this->assertSame(['first', 'second', 'third'], $deserialized->tags);
	}

	public function testSerializeAndDeserializeEmptyArrayProperty(): void {
		$dto = new SerializerArrayTestDto([]);

		$json = $this->serializer->serialize($dto, Format::JSON, ['groups' => ['basic']]);
		$this->assertJsonStringEqualsJsonString('{"tags":[]}', $json);

		/** @var SerializerArrayTestDto $deserialized */
		$deserialized = $this->serializer->deserialize($json, SerializerArrayTestDto::class, Format::JSON);
		$this->assertSame([], $deserialized->tags);
	}

	public function testSerializeNestedObjectArrayProperty(): void {
		$dto = new SerializerNestedArrayTestDto([
			new SerializerNestedItemTestDto('x'),
			new SerializerNestedItemTestDto('y'),
		]);

		$json = $this->serializer->serialize($dto);

		$this->assertJsonStringEqualsJsonString('{"items":[{"label":"x"},{"label":"y"}]}', $json);
	}

	public function testDeserializeNestedObjectArrayPropertyHydratesItems(): void {
		// The "items" property is only typed as "array" natively; symfony/property-info's
		// PhpDocExtractor reads the "@var SerializerNestedItemTestDto[]" docblock to learn the
		// array's value type, which is what lets the denormalizer hydrate real instances here
		// instead of leaving each entry as a plain associative array.
		$json = '{"items":[{"label":"x"},{"label":"y"}]}';

		/** @var SerializerNestedArrayTestDto $deserialized */
		$deserialized = $this->serializer->deserialize($json, SerializerNestedArrayTestDto::class, Format::JSON);

		$this->assertContainsOnlyInstancesOf(SerializerNestedItemTestDto::class, $deserialized->items);
		$this->assertSame('x', $deserialized->items[0]->label);
		$this->assertSame('y', $deserialized->items[1]->label);
	}
}
