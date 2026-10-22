<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Governance;

use NCU\Governance\LabelType;
use NCU\Governance\Resource\Exception\ResourceUnavailableException;
use NCU\Governance\Resource\IGovernanceResource;
use OCP\Files\File;
use OCP\Files\Node;
use Override;

final class GovernanceNodeResource implements IGovernanceResource {

	private ?Node $node = null;

	private bool $loaded = false;

	public function __construct(
		private readonly NodeLoader $loader,
		private readonly string $id,
	) {
	}

	#[Override]
	public function exists(): bool {
		$this->load();

		return $this->node !== null;
	}

	#[\Override]
	public function isReadable(): bool {
		$this->load();

		return $this->node?->isReadable() ?? false;
	}

	#[\Override]
	public function isUpdatable(): bool {
		$this->load();

		return $this->node?->isUpdateable() ?? false;
	}

	#[\Override]
	public function supportsLabelType(LabelType $labelType): bool {
		$this->load();
		$isFile = $this->node instanceof File;

		return $labelType !== LabelType::Sensitivity || $isFile;
	}

	/**
	 * @throws ResourceUnavailableException
	 */
	private function load(): void {
		if ($this->loaded) {
			return;
		}

		$this->node = $this->loader->get($this->id);
		$this->loaded = true;
	}
}
