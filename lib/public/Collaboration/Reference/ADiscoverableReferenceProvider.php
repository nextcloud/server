<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2022 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCP\Collaboration\Reference;

use JsonSerializable;

/**
 * @since 26.0.0
 */
abstract class ADiscoverableReferenceProvider implements IDiscoverableReferenceProvider, JsonSerializable {
	/**
	 * @since 26.0.0
	 */
	public function jsonSerialize(): array {
		$json = [
			'id' => $this->getId(),
			'title' => $this->getTitle(),
			'icon_url' => $this->getIconUrl(),
			'order' => $this->getOrder(),
		];
		if ($this instanceof ISearchableReferenceProvider) {
			$json['search_providers_ids'] = $this->getSupportedSearchProviderIds();
		}
		return $json;
	}
}
