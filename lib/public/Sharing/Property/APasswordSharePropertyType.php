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
use OCP\Security\Events\ValidatePasswordPolicyEvent;
use OCP\Security\IHasher;
use OCP\Security\PasswordContext;
use OCP\Server;
use OCP\Sharing\Share;

/**
 * @psalm-import-type SharingProperty from Share
 * @psalm-import-type SharingPropertyPassword from Share
 * @since 34.0.0
 */
#[Implementable(since: '34.0.0')]
abstract readonly class APasswordSharePropertyType implements ISharePropertyType, ISharePropertyTypeModifyValue {
	// TODO: Better frontend ergonomics
	// This is an easter egg!
	public const PLACEHOLDER = 'PROVOKJNLDSV';

	#[\Override]
	public function validateValue(string $value): true|string {
		if ($value === self::PLACEHOLDER) {
			return true;
		}

		try {
			Server::get(IEventDispatcher::class)->dispatchTyped(new ValidatePasswordPolicyEvent($value, PasswordContext::SHARING));
			return true;
		} catch (HintException $hintException) {
			return $hintException->getHint();
		}
	}

	#[\Override]
	public function modifyValueOnSave(?string $oldValue, ?string $newValue): ?string {
		if ($newValue === null) {
			return null;
		}

		if ($newValue === self::PLACEHOLDER) {
			return $oldValue;
		}

		return Server::get(IHasher::class)->hash($newValue);
	}

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
	 */
	#[\Override]
	public function format(array $property): array {
		$property['type'] = 'password';
		return $property;
	}
}
