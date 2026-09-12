<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability\Parameter;

use NCU\ResourceCapability\Capability;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * One value that can be configured on a capability: what it is called, whether
 * it is required, and what makes it valid.
 *
 * Implement one of the abstract types - {@see AIntCapabilityParameterType},
 * {@see AStringCapabilityParameterType}, {@see AEnumCapabilityParameterType},
 * {@see ABoolCapabilityParameterType} - rather than this interface directly.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ICapabilityParameterType {
	/**
	 * The parameter's identity, e.g. `duration`.
	 *
	 * Unique within its capability: it is the key the value is stored and
	 * transmitted under. Persisted, so treat it as permanent - changing it
	 * orphans configured values and needs a migration.
	 *
	 * @experimental 35.0.0
	 */
	public function getName(): string;

	/**
	 * The parameter's label, can be shown in a UI to input/edit values.
	 *
	 * @experimental 35.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * An optional explanation shown alongside {@see self::getDisplayName()}.
	 *
	 * @experimental 35.0.0
	 */
	public function getHint(IFactory $l10nFactory): ?string;

	/**
	 * Whether the parameter is required or not, the answer may depend on values
	 * assigned to other parameters in the same capability.
	 *
	 * @experimental 35.0.0
	 */
	public function isRequired(Capability $capability): bool;

	/**
	 * Whether this value is acceptable, given the capability it belongs to.
	 *
	 * Returns true, or an error message shown to the user inputting the value.
	 *
	 * @experimental 35.0.0
	 */
	public function validateValue(IFactory $l10nFactory, Capability $capability, mixed $value): true|string;

	/**
	 * The parameter and its constraints, for a client to render an input and
	 * validate as the user types.
	 *
	 * @return array<string, mixed>
	 * @experimental 35.0.0
	 */
	public function format(IFactory $l10nFactory): array;
}
