<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type;

use NCU\Governance\ResourceCapability\Capability;
use NCU\Governance\ResourceCapability\SemanticType;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;
use Override;

/**
 * A capability that carries no value: granting it is the whole statement.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class AGrantCapabilityType extends ACapabilityType implements IGrantCapabilityType {
	#[Override]
	final public function getSemanticType(): string {
		return SemanticType::Grant->value;
	}

	#[Override]
	public function format(IFactory $l10nFactory): array {
		return [
			...parent::format($l10nFactory),
			'meaning' => $this->getMeaning(),
		];
	}

	/**
	 * @return list<class-string<AGrantCapabilityType>>
	 */
	#[Override]
	public function getRequires(): array {
		return parent::getRequires();
	}

	public function validate(IFactory $l10nFactory, Capability $capability): true|string {
		return true;
	}
}
