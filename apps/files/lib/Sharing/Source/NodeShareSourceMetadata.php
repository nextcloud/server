<?php

/*
 * SPDX-FileCopyrightText: 2026 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

declare(strict_types=1);

namespace OCA\Files\Sharing\Source;

use NCU\Sharing\Icon\ShareIconSVG;
use NCU\Sharing\Icon\ShareIconURL;
use NCU\Sharing\Source\IShareSourceMetadata;
use OCP\Files\Node;
use OCP\IURLGenerator;

final readonly class NodeShareSourceMetadata implements IShareSourceMetadata {
	/**
	 * @experimental 35.0.0
	 */
	public function __construct(
		private IURLGenerator $urlGenerator,
		private Node $node,
	) {
	}

	#[\Override]
	public function getDisplayName(): string {
		$name = $this->node->getName();
		if ($name === '') {
			throw new \RuntimeException('Root node can\'t be shared');
		}
		return $name;
	}

	#[\Override]
	public function getIcon(): null|ShareIconSVG|ShareIconURL {
		$url = $this->urlGenerator->linkToRouteAbsolute('core.Preview.getPreviewByFileId', ['fileId' => $this->node->getId(), 'x' => 64, 'y' => 64]);

		return new ShareIconURL($url, $url);
	}
}
