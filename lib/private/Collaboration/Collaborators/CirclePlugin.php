<?php
/**
 * @copyright Copyright (c) 2017 Arthur Schiwon <blizzz@arthur-schiwon.de>
 *
 * @author Arthur Schiwon <blizzz@arthur-schiwon.de>
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
 *
 */

namespace OC\Collaboration\Collaborators;


use OCA\Circles\Api\Sharees;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;

class CirclePlugin implements ISearchPlugin {
	public function search($search, $limit, $offset, ISearchResult $searchResult) {
		$result = ['wide' => [], 'exact' => []];

		if(\OC_App::isEnabled('circles')) {
			$circles = Sharees::search($search);
			if (array_key_exists('circles', $circles['exact'])) {
				$result['exact'] = $circles['exact']['circles'];
			}
			if (array_key_exists('circles', $circles)) {
				$result['wide'] = $circles['circles'];
			}

			$searchResult->addResultSet('circles', $result['wide'], $result['exact']);
		}

		return false;
	}
}
