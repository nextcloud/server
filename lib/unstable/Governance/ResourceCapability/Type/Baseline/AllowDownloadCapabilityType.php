<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type\Baseline;

use NCU\Governance\ResourceCapability\CanonicalAction;
use NCU\Governance\ResourceCapability\Type\AGrantCapabilityType;
use NCU\Governance\ResourceCapability\Type\ICapabilityType;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use Override;

/**
 * Grants downloading a copy of a resource.
 *
 * Final while the API is experimental, so nothing inherits from a class that may
 * still change.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class AllowDownloadCapabilityType extends AGrantCapabilityType {
	/**
	 * @experimental 36.0.0
	 */
	final public const string TYPE = 'ALLOW_DOWNLOAD';

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function getIdentifier(): string {
		return self::TYPE;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Download');
	}

	/**
	 * @return list<class-string<ICapabilityType>>
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getRequires(): array {
		return [
			AllowReadCapabilityType::class,
		];
	}

	#[Override]
	public function getVersion(): int {
		return 1;
	}

	final public function getMeaning(): string {
		return CanonicalAction::Download->value;
	}
}
