<?php

declare(strict_types=1);

/*
 * @copyright 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author 2020 Christoph Wurst <christoph@winzerhof-wurst.at>
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 */

namespace OCP\Search;

use function array_merge;

/**
 * @since 20.0.0
 */
class ObjectSearchResultEntry extends SearchResultEntry {

	/**
	 * @var string
	 * @since 20.0.0
	 */
	protected $objectType;

	/**
	 * @var string
	 * @since 20.0.0
	 */
	protected $objectId;

	/**
	 * @param string $thumbnailUrl a relative or absolute URL to the thumbnail or icon of the entry
	 * @param string $title a main title of the entry
	 * @param string $subline the secondary line of the entry
	 * @param string $resourceUrl the URL where the user can find the detail, like a deep link inside the app
	 * @param string $icon the icon class or url to the icon
	 * @param boolean $rounded is the thumbnail rounded
	 *
	 * @since 20.0.0
	 */
	public function __construct(string $thumbnailUrl,
								string $title,
								string $subline,
								string $resourceUrl,
								string $objectType,
								string $objectId,
								string $icon = '',
								bool $rounded = false) {
		parent::__construct(
			$thumbnailUrl,
			$title,
			$subline,
			$resourceUrl,
			$icon,
			$rounded
		);

		$this->objectType = $objectType;
		$this->objectId = $objectId;
	}

	/**
	 * @return mixed[]
	 *
	 * @since 20.0.0
	 */
	public function jsonSerialize(): array {
		return array_merge(
			parent::jsonSerialize(),
			[
				'objectType' => $this->objectType,
				'objectId' => $this->objectId,
			]
		);
	}
}
