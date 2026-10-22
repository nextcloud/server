<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type\Baseline;

use NCU\Governance\ResourceCapability\CanonicalAction;
use NCU\Governance\ResourceCapability\Type\AGrantCapabilityType;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;
use Override;

/**
 * Grants passing access on to someone else.
 *
 * @experimental 36.0.0
 */
#[Consumable(since: '36.0.0')]
class AllowShareCapabilityType extends AGrantCapabilityType {
	/**
	 * @experimental 36.0.0
	 */
	final public const string TYPE = 'allow_share';

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
		return $this->l10nFactory->get('core')->t('Share');
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
		return CanonicalAction::Share->value;
	}
}
