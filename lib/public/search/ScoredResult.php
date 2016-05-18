<?php
/**
 * @author Georg Ehrke <georg@ownCloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OCP\Search;

/**
 * The generic result of a search
 * @since 9.1.0
 */
class ScoredResult extends Result {

	/**
	 * A score for the result, ranks between 0 and 1
	 * @var float
	 * @since 9.1.0
	 */
	public $score;

	/**
	 * Highlight to what part of the result matches the search
	 * @var string
	 * @since 9.1.0
	 */
	public $highlight;

	/**
	 * Create a new scored search result
	 * @param string $id unique identifier from application: '[app_name]/[item_identifier_in_app]'
	 * @param string $name displayed text of result
	 * @param string $link URL to the result within its app
	 * @param float $score score the result
	 * @param string $highlight for little preview of result
	 * @since 9.1.0
	 */
	public function __construct($id = null, $name = null, $link = null, $score = null, $highlight = null) {
		parent::__construct($id, $name, $link);
		$this->score = $score;
		$this->highlight = $highlight;
	}
}
