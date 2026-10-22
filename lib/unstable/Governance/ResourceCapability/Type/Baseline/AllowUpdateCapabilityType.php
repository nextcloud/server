<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type\Baseline;

use NCU\Governance\ResourceCapability\CanonicalPermission;
use NCU\Governance\ResourceCapability\Type\ACapabilityType;
use NCU\Governance\ResourceCapability\Type\AGrantCapabilityType;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use Override;

/**
 * Grants updating permissions for existing content.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
class AllowUpdateCapabilityType extends AGrantCapabilityType {
	/**
	 * @experimental 36.0.0
	 */
	final public const string TYPE = 'allow_update';

	/**
	 * @experimental 36.0.0
	 */
	public function __construct(
		private readonly IFactory $l10nFactory,
	) {
	}

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
	public function getDisplayName(): string {
		return $this->l10nFactory->get('core')->t('Edit');
	}

	/**
	 * @return list<non-empty-lowercase-string> {@see ACapabilityType::getIdentifier()}
	 * @experimental 36.0.0
	 */
	#[\Override]
	final public function getRequires(): array {
		return [
			AllowReadCapabilityType::TYPE,
		];
	}

	/**
	 * @experimental 36.0.0
	 */
	#[Override]
	public function getVersion(): int {
		return 1;
	}

	/**
	 * @experimental 36.0.0
	 */
	#[Override]
	final public function getMeaning(): string {
		return CanonicalPermission::Update->value;
	}
}
