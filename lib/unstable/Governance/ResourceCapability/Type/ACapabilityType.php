<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type;

use NCU\Governance\ResourceCapability\Capability;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;
use Override;

/**
 * Abstract with some defaults.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class ACapabilityType implements ICapabilityType {

	#[Override]
	abstract public function getSemanticType(): string;

	#[Override]
	abstract public function getIdentifier(): string;

	#[Override]
	abstract public function getVersion(): int;

	/**
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getHint(IFactory $l10nFactory): ?string {
		return null;
	}

	/**
	 * @return list<class-string<ICapabilityType>>
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function getRequires(): array {
		return [];
	}

	/**
	 * {@inheritdoc}
	 *
	 * @experimental 36.0.0
	 */
	#[\Override]
	abstract public function validate(IFactory $l10nFactory, Capability $capability): true|string;

	/**
	 * @return array<string, mixed>
	 * @experimental 36.0.0
	 */
	#[\Override]
	public function format(IFactory $l10nFactory): array {
		return [
			'semanticType' => $this->getSemanticType(),
			'version' => $this->getVersion(),
			'identifier' => $this->getIdentifier(),
			'displayName' => $this->getDisplayName($l10nFactory),
			'hint' => $this->getHint($l10nFactory),
			'requires' => $this->getRequires(),
		];
	}
}
