<?php

declare(strict_types=1);

namespace OCA\Sharing\Features;

use DateTimeImmutable;
use Exception;
use OCA\Sharing\Model\AShareFeature;
use OCA\Sharing\Model\IShareFeatureFilter;
use OCA\Sharing\RecipientTypes\GroupShareRecipientType;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCA\Sharing\SourceTypes\NodeShareSourceType;

class ExpirationShareFeature extends AShareFeature implements IShareFeatureFilter {
	public function getCompatibles(): array {
		$compatibles = [];
		foreach ([UserShareRecipientType::class, GroupShareRecipientType::class] as $recipientType) {
			$compatibles[] = [
				'source_type' => NodeShareSourceType::class,
				'recipient_type' => $recipientType,
			];
		}

		return $compatibles;
	}

	public function validateProperties(array $properties): bool {
		if (array_keys($properties) !== ['date'] || count($properties['date']) !== 1) {
			return false;
		}

		try {
			$expirationDate = new DateTimeImmutable($properties['date'][0]);
		} catch (Exception) {
			return false;
		}

		return $expirationDate->diff(new DateTimeImmutable())->invert === 1;
	}

	public function isFiltered(array $properties): bool {
		if (!isset($properties['date'])) {
			return false;
		}

		try {
			$expirationDate = new DateTimeImmutable($properties['date'][0]);
		} catch (Exception) {
			return true;
		}

		return $expirationDate->diff(new DateTimeImmutable())->invert === 0;
	}
}
