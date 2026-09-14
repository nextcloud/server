<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Sharing\Property;

use DateInterval;
use DateTimeImmutable;
use NCU\Sharing\Property\APasswordSharePropertyType;
use NCU\Sharing\Property\ISharePropertyTypeFilter;
use NCU\Sharing\Share;
use NCU\Sharing\ShareAccessContext;
use OC\Core\AppInfo\Application;
use OC\Core\Sharing\Recipient\EmailShareRecipientType;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\IConfig;
use OCP\L10N\IFactory;
use OCP\Security\Events\GenerateSecurePasswordEvent;
use OCP\Security\IHasher;
use OCP\Security\ISecureRandom;
use OCP\Security\PasswordContext;
use OCP\Share\IManager;
use Random\Randomizer;

final class PasswordSharePropertyType extends APasswordSharePropertyType implements ISharePropertyTypeFilter {

	private readonly Randomizer $randomizer;

	private readonly DateTimeImmutable $now;

	public function __construct(
		private readonly IManager $legacyManager,
		private readonly IHasher $hasher,
		private readonly IEventDispatcher $eventDispatcher,
		private readonly IConfig $config,
	) {
		$this->randomizer = new Randomizer();
		$this->now = new DateTimeImmutable();
	}

	#[\Override]
	public function getDisplayName(IFactory $l10nFactory): string {
		return $l10nFactory->get(Application::APP_ID)->t('Password');
	}

	#[\Override]
	public function getHint(IFactory $l10nFactory, Share $share): ?string {
		if ($this->isRequired($share)) {
			return $l10nFactory->get(Application::APP_ID)->t('Your administrator has enforced a password protection.');
		}

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
	public function isRequired(Share $share): bool {
		// TODO: Enable group memberships check based on the owner.
		return $this->legacyManager->shareApiLinkEnforcePassword(false);
	}

	#[\Override]
	public function getDefaultValue(Share $share): ?string {
		if (!$this->isRequired($share)) {
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
			if (!$this->hasher->verify($argument, $property->value)) {
				// TODO: Check if the hash has to be updated and save it.
				return true;
			}

			if (!$this->config->getSystemValueBool('sharing.enable_mail_link_password_expiration')) {
				return false;
			}

			if ($accessContext->secret === null) {
				return false;
			}

			foreach ($share->recipients as $recipient) {
				if ($recipient->secret !== $accessContext->secret) {
					continue;
				}

				if ($recipient->class !== EmailShareRecipientType::class) {
					continue;
				}

				$expirationIntervalSeconds = $this->config->getSystemValueInt('sharing.mail_link_password_expiration_interval', 3600);
				$expirationDate = $share->lastUpdated->add(new DateInterval('PT' . $expirationIntervalSeconds . 'S'));
				return $this->now->diff($expirationDate)->invert === 1;
			}
		}

		return false;
	}
}
