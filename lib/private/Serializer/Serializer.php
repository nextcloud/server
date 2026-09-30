<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Serializer;

use OCP\Serializer\Format;
use OCP\Serializer\ISerializer;
use Symfony\Component\PropertyInfo\Extractor\PhpDocExtractor;
use Symfony\Component\PropertyInfo\Extractor\ReflectionExtractor;
use Symfony\Component\PropertyInfo\PropertyInfoExtractor;
use Symfony\Component\Serializer\Encoder\CsvEncoder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\Encoder\XmlEncoder;
use Symfony\Component\Serializer\Mapping\Factory\ClassMetadataFactory;
use Symfony\Component\Serializer\NameConverter\MetadataAwareNameConverter;
use Symfony\Component\Serializer\Normalizer\ArrayDenormalizer;
use Symfony\Component\Serializer\Normalizer\BackedEnumNormalizer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Serializer\Normalizer\ObjectNormalizer;
use Symfony\Component\Serializer\Serializer as SymfonySerializer;

class Serializer implements ISerializer {
	private SymfonySerializer $serializer;

	public function __construct() {
		$classMetadataFactory = new ClassMetadataFactory(new AttributeLoader());
		$nameConverter = new MetadataAwareNameConverter($classMetadataFactory);

		// Combines native (reflection) and "@var"-docblock type information, so that e.g. an
		// `array` property documented as `@var Foo[]` can be denormalized back into `Foo`
		// instances instead of plain arrays.
		$reflectionExtractor = new ReflectionExtractor();
		$phpDocExtractor = new PhpDocExtractor();
		$propertyTypeExtractor = new PropertyInfoExtractor(
			listExtractors: [$reflectionExtractor],
			typeExtractors: [$phpDocExtractor, $reflectionExtractor],
			descriptionExtractors: [$phpDocExtractor],
			accessExtractors: [$reflectionExtractor],
			initializableExtractors: [$reflectionExtractor],
		);

		$this->serializer = new SymfonySerializer(
			[
				new DateTimeNormalizer(),
				new BackedEnumNormalizer(),
				new ArrayDenormalizer(),
				new ObjectNormalizer(
					classMetadataFactory: $classMetadataFactory,
					nameConverter: $nameConverter,
					propertyTypeExtractor: $propertyTypeExtractor,
				),
			],
			[
				new JsonEncoder(),
				new XmlEncoder(),
				new CsvEncoder(),
			],
		);
	}

	#[\Override]
	public function serialize(mixed $data, Format $format = Format::JSON, array $context = []): string {
		return $this->serializer->serialize($data, $format->value, $context);
	}

	#[\Override]
	public function deserialize(string $data, string $type, Format $format = Format::JSON, array $context = []): mixed {
		return $this->serializer->deserialize($data, $type, $format->value, $context);
	}
}
