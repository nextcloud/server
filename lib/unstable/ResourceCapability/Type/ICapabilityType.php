<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\ResourceCapability\Type;

use NCU\ResourceCapability\Capability;
use NCU\ResourceCapability\Parameter\ICapabilityParameterType;
use NCU\ResourceRight\IRight;
use OCP\AppFramework\Attribute\Implementable;
use OCP\L10N\IFactory;

/**
 * One capability an app supports for a resource type.
 *
 * Two resource types may register the same identity with different
 * {@see ICapabilityType} instances, each restricting the parameter values it can
 * honour, and a subclass restyling or narrowing a capability stays the same capability.
 *
 * Use capabilities and name their identifiers affirmatively (`KEEP_VERSIONS`),
 * never as a denial: an unresolved capability must read as "not granted".
 *
 * The app owns what the capability means: which values are valid, which right
 * withholding it restricts, and how two answers about the same resource
 * combine.
 *
 * Extend {@see ACapabilityType} — or {@see AGrantCapabilityType},
 * {@see AFloorCapabilityType}, {@see ACeilingCapabilityType} for the ordinary
 * shapes - rather than implementing this directly. To reuse a capability with
 * different wording, subclass it and override {@see self::getDisplayName()};
 * {@see self::getIdentifier()} is inherited, so extending a base capability
 * can be used to re-declare a capability with the same meaning for different
 * apps.
 *
 * @experimental 35.0.0
 */
#[Implementable(since: '35.0.0')]
interface ICapabilityType {
	/**
	 * The capability's identity, e.g. `KEEP_VERSIONS`.
	 *
	 * Unique per resource type, not globally: lookups are keyed on
	 * `(resourceClass, type)`, so no app can invalidate another's vocabulary by
	 * picking a common name. It is persisted and sent to clients, so treat it as
	 * permanent - changing it orphans stored configurations and requires a
	 * migration.
	 *
	 * @experimental 35.0.0
	 */
	public function getIdentifier(): string;

	/**
	 * The capability's name, could be shown to admins/users.
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
	 * A key that can be used to group capabilities together.
	 *
	 * @experimental 35.0.0
	 */
	public function getGroup(): ?string;

	/**
	 * @return list<ICapabilityParameterType>
	 * @experimental 35.0.0
	 */
	public function getParameters(): array;

	/**
	 * Capabilities that must be granted alongside this one, e.g. editing
	 * requires reading. Names {@see self::getIdentifier()} values of the same resource
	 * type, checked at registration.
	 *
	 * @return list<string>
	 * @experimental 35.0.0
	 */
	public function getRequires(): array;

	/**
	 * A right withholding this capability restricts. Null when the
	 * capability carries one or more values rather than granting a right.
	 *
	 * @return ?class-string<IRight>
	 * @experimental 35.0.0
	 */
	public function getGrantedRight(): ?string;

	/**
	 * Whether a capability is acceptable as a whole.
	 *
	 * Returns true, or a translated error message to show the user.
	 *
	 * {@see ACapabilityType} implements this by checking every declared parameter.
	 *
	 * @experimental 35.0.0
	 */
	public function validate(IFactory $l10nFactory, Capability $capability): true|string;

	/**
	 * Combines multiple answers for the same resource and capability into one.
	 *
	 * Must be independent of the order answers arrive in, and cheap, since it
	 * runs once per resource in a batch.
	 *
	 * @param non-empty-list<Capability> $capabilities all of this type, for one resource
	 * @experimental 35.0.0
	 */
	public function resolve(array $capabilities): Capability;

	/**
	 * Formats the capability and its parameters, for a client to render an editor from.
	 *
	 * @return array<string, mixed>
	 * @experimental 35.0.0
	 */
	public function format(IFactory $l10nFactory): array;
}
