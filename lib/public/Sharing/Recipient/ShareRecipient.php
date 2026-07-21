<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Recipient;

use OCP\AppFramework\Attribute\Consumable;
use OCP\IURLGenerator;
use OCP\IUserManager;
use OCP\L10N\IFactory;
use OCP\Sharing\ISharingRegistry;
use OCP\Sharing\Share;
use OCP\Sharing\ShareUser;
use RuntimeException;

/**
 * @psalm-import-type SharingRecipient from Share
 * @since 35.0.0
 */
#[Consumable(since: '35.0.0')]
final readonly class ShareRecipient {
	/**
	 * @since 35.0.0
	 */
	public function __construct(
		/** @var class-string<IShareRecipientType> $class */
		public string $class,
		/** @var non-empty-string $value */
		public string $value,
		/** @var ?non-empty-string $instance */
		public ?string $instance,
		/** @var ?non-empty-string $secret */
		public ?string $secret = null,
		public ?ShareUser $initiator = null,
	) {
		if ($instance !== null && !preg_match('/^https?:\/\/.+/', $instance)) {
			throw new RuntimeException('The instance is not a valid absolute URL: ' . $instance);
		}
	}

	/**
	 * @return SharingRecipient
	 * @since 35.0.0
	 */
	public function format(ISharingRegistry $registry, IFactory $l10nFactory, IURLGenerator $urlGenerator, IUserManager $userManager, bool $isUnique): array {
		if (($recipientType = ($registry->getRecipientTypes()[$this->class] ?? null)) === null) {
			throw new RuntimeException('The recipient type is not registered: ' . $this->class);
		}

		if ($this->instance !== null) {
			// TODO: Support federation
			throw new RuntimeException('Currently only local recipients are supported.');
		}

		$displayName = $recipientType->getRecipientDisplayName($this->value) ?? $this->value;
		if (!$isUnique) {
			$displayName .= ' (' . $recipientType->getDisplayName($l10nFactory) . ': ' . $this->value . ')';
		}

		$secret = [
			'updatable' => $recipientType instanceof IShareRecipientTypePublicSecret && $recipientType->isSecretUpdatable($this->value),
		];
		if ($this->secret !== null && $recipientType instanceof IShareRecipientTypePublicSecret && $recipientType->isSecretPublic($this->value)) {
			$secret['value'] = $this->secret;

			// TODO: We need a dedicated page that shows all sources linked to a share
			$secret['url'] = $urlGenerator->linkToRouteAbsolute(
				'files_sharing.sharecontroller.showShare',
				[
					'token' => $this->secret,
				],
			);
		}

		return [
			'class' => $this->class,
			'value' => $this->value,
			'instance' => $this->instance,
			'display_name' => $displayName,
			'icon' => $recipientType->getRecipientIcon($this->value)?->format(),
			'secret' => $secret,
			'initiator' => $this->initiator?->format($userManager),
		];
	}

	/**
	 * @param list<self> $recipients
	 * @return list<SharingRecipient>
	 * @since 35.0.0
	 */
	public static function formatMultiple(ISharingRegistry $registry, IFactory $l10nFactory, IURLGenerator $urlGenerator, IUserManager $userManager, array $recipients): array {
		$recipientTypes = $registry->getRecipientTypes();

		$recipientDisplayNames = [];
		foreach ($recipients as $recipient) {
			$displayName = $recipientTypes[$recipient->class]?->getRecipientDisplayName($recipient->value) ?? $recipient->value;
			$recipientDisplayNames[$displayName] ??= 0;
			++$recipientDisplayNames[$displayName];
		}

		return array_map(static fn (ShareRecipient $recipient): array => $recipient->format($registry, $l10nFactory, $urlGenerator, $userManager, $recipientDisplayNames[$recipientTypes[$recipient->class]?->getRecipientDisplayName($recipient->value) ?? $recipient->value] === 1), $recipients);
	}
}
