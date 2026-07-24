<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use OC\Core\AppInfo\Application;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\L10N\IFactory;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use OCP\Share\IManager;
use OCP\Sharing\Property\APasswordSharePropertyType;
use OCP\Sharing\Property\ISharePropertyTypeFilter;
use OCP\Sharing\Share;
use OCP\Sharing\ShareAccessContext;
use Random\Randomizer;

final class PasswordSharePropertyType extends APasswordSharePropertyType implements ISharePropertyTypeFilter {

	private readonly Randomizer $randomizer;

	public function __construct(
		private readonly IManager $legacyManager,
		private readonly IHasher $hasher,
		private readonly IEventDispatcher $eventDispatcher,
	) {
		$this->randomizer = new Randomizer();
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Password');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	#[\Override]
	public function getPriority(): int {
		return 60;
	}

	#[\Override]
	public function isAdvanced(): bool {
		return true;
	}

	#[\Override]
	public function isRequired(): bool {
		// TODO: Enable group memberships check based on the owner.
		return $this->legacyManager->shareApiLinkEnforcePassword(false);
	}

	#[\Override]
	public function getDefaultValue(): ?string {
		if (!$this->isRequired()) {
			return null;
		}

		$event = new GenerateSecurePasswordEvent(PasswordContext::SHARING);
		$this->eventDispatcher->dispatchTyped($event);
		return $event->getPassword() ?? $this->randomizer->getBytesFromString(ISecureRandom::CHAR_ALPHANUMERIC, 20);
	}

	#[\Override]
	public function isFiltered(ShareAccessContext $accessContext, Share $share): bool {
		$argument = $accessContext->arguments[self::class] ?? null;
		if (!is_string($argument)) {
			return true;
		}

		if (($property = $share->properties[self::class] ?? null) !== null && $property->value !== null) {
			// TODO: Check if the hash has to be updated and save it.
			return !$this->hasher->verify($argument, $property->value);
		}

		return false;
	}
}
