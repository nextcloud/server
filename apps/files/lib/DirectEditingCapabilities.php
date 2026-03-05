<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\Files;

use OCA\Files\Service\DirectEditingService;
use OCP\Capabilities\ICapability;
use OCP\Capabilities\IInitialStateExcludedCapability;
use OCP\IURLGenerator;

class DirectEditingCapabilities implements ICapability, IInitialStateExcludedCapability {
	public function __construct(
		protected DirectEditingService $directEditingService,
		protected IURLGenerator $urlGenerator,
	) {
	}

	/**
	 * @return array{files: array{directEditing: array{url: string, etag: string, supportsFileId: bool}}}
	 */
	public function getCapabilities() {
		return [
			'files' => [
				'directEditing' => [
					'url' => $this->urlGenerator->linkToOCSRouteAbsolute('files.DirectEditing.info'),
					'etag' => $this->directEditingService->getDirectEditingETag(),
					'supportsFileId' => true,
				]
			],
		];
	}
}
