<?php

declare(strict_types=1);
/**
 * @copyright Copyright (c) 2022 Julien Veyssier <eneiluj@posteo.net>
 *
 * @author Julien Veyssier <eneiluj@posteo.net>
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
