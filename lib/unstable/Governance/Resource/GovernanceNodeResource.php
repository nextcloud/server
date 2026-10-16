<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace NCU\Governance\Resource;

use NCU\Governance\LabelType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowCreateCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowDeleteCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowDownloadCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowReadCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowShareCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowUpdateCapabilityType;
use OC\Files\Node\Node;
use OCA\Governance\Service\Resource\FilesResourceAdapter;
use OCP\Server;

/**
 * Represents the type for {@see Node} as provided by the files app.
 *
 * @experimental 36.0.0
 */
final readonly class GovernanceNodeResource implements IGovernanceResource, IResourceWithSystemTagName {
	public function getIdentifier(): string {
		return 'files';
	}

	#[\Override]
	public function getAdapter(): IGovernanceResourceAdapter {
		return Server::get(FilesResourceAdapter::class);
	}

	#[\Override]
	public function getSupportedCapabilities(): array {
		return [
			LabelType::SENSITIVITY->value => [
				AllowReadCapabilityType::class,
				AllowDownloadCapabilityType::class,
				AllowCreateCapabilityType::class,
				AllowUpdateCapabilityType::class,
				AllowDeleteCapabilityType::class,
				AllowShareCapabilityType::class,
			],
			LabelType::RETENTION->value => [],
			LabelType::HOLD->value => [],
		];
	}

	#[\Override]
	public function getSystemTagObjectType(): string {
		return 'files';
	}
}
