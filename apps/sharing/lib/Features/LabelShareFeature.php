<?php

declare(strict_types=1);

namespace OCA\Sharing\Features;

use OCA\Sharing\Model\AShareFeature;
use OCA\Sharing\RecipientTypes\GroupShareRecipientType;
use OCA\Sharing\RecipientTypes\UserShareRecipientType;
use OCA\Sharing\SourceTypes\NodeShareSourceType;

class LabelShareFeature extends AShareFeature {
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
		return array_keys($properties) === ['text'] && count($properties['text']) === 1 && $properties['text'][0] !== '';
	}
}
