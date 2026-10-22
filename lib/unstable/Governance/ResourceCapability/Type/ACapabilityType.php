<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace NCU\Governance\ResourceCapability\Type;

use NCU\Governance\ResourceCapability\CanonicalAction;
use NCU\Governance\ResourceCapability\CanonicalPermission;
use NCU\Governance\ResourceCapability\Capability;
use NCU\Governance\ResourceCapability\SemanticType;
use OCP\AppFramework\Attribute\Implementable;

/**
 * One capability an app supports for a resource type.
 *
 * The identifier is unique within a resource type only. Use the semantic type
 * to group capabilities, and the meaning to express that capabilities of
 * different resource types are the same.
 *
 * Use capabilities and name their identifiers affirmatively (`keep_versions`),
 * never as a denial.
 *
 * A more specific abstract type for grants is available at {@see AGrantCapabilityType}.
 *
 * @experimental 36.0.0
 */
#[Implementable(since: '36.0.0')]
abstract class ACapabilityType {

	/**
	 * A stable identifier for this capability, unique within a resource type,
	 * e.g. `allow_read`. Persisted, so it must never change; use
	 * {@see self::getVersion()} to evolve its meaning.
	 *
	 * @return non-empty-lowercase-string matching `[a-z0-9_]+`
	 * @experimental 36.0.0
	 */
	abstract public function getIdentifier(): string;

	/**
	 * Version for the capability, to be able to distinguish and migrate older
	 * stored configurations.
	 *
	 * @return non-negative-int
	 * @experimental 36.0.0
	 */
	abstract public function getVersion(): int;

	/**
	 * What the capability represents in general, will be used for grouping.
	 *
	 * @return non-empty-string|value-of<SemanticType>
	 * @experimental 36.0.0
	 */
	abstract public function getSemanticType(): string;

	/**
	 * Value that can be used to express that a capability is the same as another
	 * for a different resource.
	 *
	 * For example: {@see CanonicalPermission::Read} for a file and table
	 * resource means that someone can get read access to both.
	 *
	 * Use a canonical value when the capability is equivalent to one. For a
	 * meaning specific to your app, use the `{appId}.{name}` format, e.g.
	 * `deck.archive`; other apps may return the same value to express the same
	 * meaning. If a meaning is generic enough to be shared by many apps, open a
	 * pull request to add it as a canonical value instead.
	 *
	 * Capabilities returning the same value may be treated as equivalent by the
	 * frontend UI in some cases (e.g. allow reading for all resources), so beware
	 * of conflicts: only reuse a value whose meaning matches exactly.
	 *
	 * @return string|value-of<CanonicalPermission>|value-of<CanonicalAction>
	 * @experimental 36.0.0
	 */
	abstract public function getMeaning(): string;

	/**
	 * The capability's name, could be shown to admins/users.
	 *
	 * @experimental 36.0.0
	 */
	abstract public function getDisplayName(): string;

	/**
	 * An optional explanation shown alongside {@see self::getDisplayName()}.
	 *
	 * @experimental 36.0.0
	 */
	public function getHint(): ?string {
		return null;
	}

	/**
	 * Capabilities that must be granted alongside this one, e.g. editing
	 * requires reading. Names capability identifiers on the same resource
	 * type, checked when the vocabulary is read.
	 *
	 * @return list<non-empty-lowercase-string> required capabilities {@see ACapabilityType::getIdentifier()}
	 * @experimental 36.0.0
	 */
	public function getRequires(): array {
		return [];
	}

	/**
	 * Whether a capability is acceptable as a whole.
	 *
	 * Returns true, or a translated error message to show the user.
	 *
	 * @experimental 36.0.0
	 */
	abstract public function validate(Capability $capability): true|string;

	/**
	 * Formats the capability definition, for a client to render an editor from.
	 *
	 * @return array{
	 *     meaning: string,
	 *     semanticType: non-empty-string,
	 *     version: int,
	 *     identifier: non-empty-lowercase-string,
	 *     displayName: string,
	 *     hint: ?string,
	 *     requires: list<non-empty-lowercase-string>,
	 *	  ...
	 *	 }
	 * @psalm-return array
	 * @experimental 36.0.0
	 */
	final public function format(): array {
		return [
			...$this->getAdditionalFormatValues(),
			'meaning' => $this->getMeaning(),
			'semanticType' => $this->getSemanticType(),
			'version' => $this->getVersion(),
			'identifier' => $this->getIdentifier(),
			'displayName' => $this->getDisplayName(),
			'hint' => $this->getHint(),
			'requires' => $this->getRequires(),
		];
	}

	/**
	 * Additional fields to render as part of the format.
	 *
	 * @return array<string, mixed> must be serializable to Json
	 * @experimental 36.0.0
	 */
	protected function getAdditionalFormatValues(): array {
		return [];
	}
}
