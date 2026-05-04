<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCP\Sharing\Property;

use OCP\AppFramework\Attribute\Implementable;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\HintException;
use OCP\L10N\IFactory;
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\Security\IHasher;
use OCP\Security\PasswordContext;
use OCP\Server;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyPassword from Share
 * @since 35.0.0
 */
#[Implementable(since: '35.0.0')]
abstract class APasswordSharePropertyType implements ISharePropertyType, ISharePropertyTypeModifyValue {
	/**
	 * @since 35.0.0
	 */
	public const string PLACEHOLDER = 'PROVOKJNLDSV';

	private ?IEventDispatcher $eventDispatcher = null;

	private ?IHasher $hasher = null;

	private function getEventDispatcher(): IEventDispatcher {
		return $this->eventDispatcher ??= Server::get(IEventDispatcher::class);
	}

	private function getHasher(): IHasher {
		return $this->hasher ??= Server::get(IHasher::class);
	}

	/**
	 * @since 35.0.0
	 */
	#[\Override]
	public function validateValue(IFactory $l10nFactory, string $value): true|string {
		if ($value === self::PLACEHOLDER) {
			return true;
		}

		try {
			$this->getEventDispatcher()->dispatchTyped(new ValidatePasswordPolicyEvent($value, PasswordContext::SHARING));
			return true;
		} catch (HintException $hintException) {
			return $hintException->getHint();
		}
	}

	/**
	 * @since 35.0.0
	 */
	#[\Override]
	public function modifyValueOnSave(?string $oldValue, ?string $newValue): ?string {
		if ($newValue === null) {
			return null;
		}

		if ($newValue === self::PLACEHOLDER) {
			return $oldValue;
		}

		return $this->getHasher()->hash($newValue);
	}

	/**
	 * @since 35.0.0
	 */
	#[\Override]
	public function modifyValueOnLoad(?string $value): ?string {
		if ($value === null) {
			return null;
		}

		return self::PLACEHOLDER;
	}

	/**
	 * @param SharingProperty $property
	 * @return SharingPropertyPassword
	 * @since 35.0.0
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'password';
		return $property;
	}
}
