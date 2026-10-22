<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Governance;

use NCU\Governance\Actor\Actor;
use NCU\Governance\LabelType;
use NCU\Governance\Resource\IGovernanceResourceProvider;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowCreateCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowDeleteCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowDownloadCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowReadCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowShareCapabilityType;
use NCU\Governance\ResourceCapability\Type\Baseline\AllowUpdateCapabilityType;
use OCA\Files\AppInfo\Application;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\IL10N;
use OCP\L10N\IFactory;
use Override;

/**
 * Provider for {@see Node} type into Governance.
 */
final readonly class GovernanceNodeResourceProvider implements IGovernanceResourceProvider {

	public function __construct(
		private IL10N $l,
		private IFactory $l10nFactory,
		private IRootFolder $rootFolder,
		private IUserMountCache $userMountCache,
	) {
	}

	#[Override]
	public function getIdentifier(): string {
		return Application::APP_ID . '.node';
	}

	#[Override]
	public function getName(): string {
		return $this->l->t('File');
	}

	#[\Override]
	public function getSupportedCapabilities(): array {
		return [
			LabelType::Sensitivity->value => [
				new AllowReadCapabilityType($this->l10nFactory),
				new AllowDownloadCapabilityType($this->l10nFactory),
				new AllowCreateCapabilityType($this->l10nFactory),
				new AllowUpdateCapabilityType($this->l10nFactory),
				new AllowDeleteCapabilityType($this->l10nFactory),
				new AllowShareCapabilityType($this->l10nFactory),
			],
			LabelType::Retention->value => [],
			LabelType::Hold->value => [],
		];
	}

	#[Override]
	public function getSupportedLabelTypes(): array {
		return [
			LabelType::Sensitivity,
			LabelType::Retention,
			LabelType::Hold,
		];
	}

	#[Override]
	public function getResources(Actor $actor, array $ids): array {
		$loader = new NodeLoader($this->rootFolder, $this->userMountCache, $actor, $ids);

		$resources = [];
		foreach ($ids as $id) {
			$resources[$id] = new GovernanceNodeResource($loader, $id);
		}

		return $resources;
	}
}
