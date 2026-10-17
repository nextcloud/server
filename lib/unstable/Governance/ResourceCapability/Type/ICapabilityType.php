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
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
interface ICapabilityType {

	/**
	 * A stable identifier for this capability.
	 *
	 * The value returned here MUST be URL-safe and will identify the capability
	 * with {appId}.{resourceId}.{capabilityId}.
	 *
	 * @experimental 36.0.0
	 */
	public function getIdentifier(): string;

	/**
	 * TODO: we probably want to have a way for apps to update their code without breaking completely the existing configured data. An idea would be
	 *   to have somewhere a function to get the current stored data for a capability, pass it to the app and get the new updated version.
	 *   Problem: how to deal with values that need to be filled manually by the admin?
	 */
	public function getVersion(): int;

	/**
	 * What the capability represents in general.
	 *
	 * @return string|value-of<SemanticType>
	 */
	public function getSemanticType(): string;

	/**
	 * The capability's name, could be shown to admins/users.
	 *
	 * @experimental 36.0.0
	 */
	public function getDisplayName(IFactory $l10nFactory): string;

	/**
	 * An optional explanation shown alongside {@see self::getDisplayName()}.
	 *
	 * @experimental 36.0.0
	 */
	public function getHint(IFactory $l10nFactory): ?string;

	/**
	 * Capabilities that must be granted alongside this one, e.g. editing
	 * requires reading. Names capability classes of the same resource
	 * type, checked when the vocabulary is read.
	 *
	 * A subclass of the named class satisfies the requirement, since it keeps
	 * the identifier and the granted right. An unrelated class that only reuses
	 * the same identifier does not.
	 *
	 * @return list<class-string<ICapabilityType>>
	 * @experimental 36.0.0
	 */
	public function getRequires(): array;

	/**
	 * Whether a capability is acceptable as a whole.
	 *
	 * Returns true, or a translated error message to show the user.
	 *
	 * {@see ACapabilityType} implements this by checking every declared parameter.
	 *
	 * @experimental 36.0.0
	 */
	public function validate(IFactory $l10nFactory, Capability $capability): true|string;

	/**
	 * Formats the capability definition, for a client to render an editor from.
	 *
	 * @return array<string, mixed>
	 * @experimental 36.0.0
	 */
	public function format(IFactory $l10nFactory): array;
}
