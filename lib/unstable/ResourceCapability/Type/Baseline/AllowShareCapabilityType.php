<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability\Type\Baseline;

use NCU\ResourceCapability\Type\AGrantCapabilityType;
use NCU\ResourceRight\Rights\ShareRight;
use OC\Core\AppInfo\Application;
use OCP\AppFramework\Attribute\Consumable;
use OCP\L10N\IFactory;

/**
 * Grants passing access on to someone else.
 *
 * Final while the API is experimental, so nothing inherits from a class that may
 * still change.
 *
 * @experimental 35.0.0
 */
#[Consumable(since: '35.0.0')]
final class AllowShareCapabilityType extends AGrantCapabilityType {
	/**
	 * @experimental 35.0.0
	 */
	public const string TYPE = 'ALLOW_SHARE';

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	final public function getIdentifier(): string {
		return self::TYPE;
	}

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Share');
	}

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getGroup(): ?string {
		return 'rights';
	}

	/**
	 * @experimental 35.0.0
	 */
	#[\Override]
	public function getGrantedRight(): ?string {
		return ShareRight::class;
	}
}
