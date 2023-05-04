<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\Files;

use OCA\Files\Service\DirectEditingService;
use OCP\Capabilities\ICapability;
use OCP\Capabilities\IInitialStateExcludedCapability;
use OCP\IURLGenerator;

class DirectEditingCapabilities implements ICapability, IInitialStateExcludedCapability {
	protected DirectEditingService $directEditingService;
	protected IURLGenerator $urlGenerator;

	public function __construct(DirectEditingService $directEditingService, IURLGenerator $urlGenerator) {
		$this->directEditingService = $directEditingService;
		$this->urlGenerator = $urlGenerator;
	}

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
