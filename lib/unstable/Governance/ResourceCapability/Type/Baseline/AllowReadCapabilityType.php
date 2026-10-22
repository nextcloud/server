<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type\Baseline;

use NCU\Governance\ResourceCapability\CanonicalPermission;
use NCU\Governance\ResourceCapability\Type\AGrantCapabilityType;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use Override;

/**
 * Grants reading permissions.
 *
 * Final while the API is experimental, so nothing inherits from a class that may
 * still change.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
final class AllowReadCapabilityType extends AGrantCapabilityType {
	/**
	 * @experimental 36.0.0
	 */
	final public const string TYPE = 'ALLOW_READ';

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
		return $l10nFactory->get(Application::APP_ID)->t('Read');
	}

	#[Override]
	public function getVersion(): int {
		return 1;
	}

	final public function getMeaning(): string {
		return CanonicalPermission::Read->value;
	}
}
