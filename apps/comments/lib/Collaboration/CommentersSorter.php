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

namespace OCA\Comments\Collaboration;


use OCP\Collaboration\AutoComplete\ISorter;
use OCP\Comments\ICommentsManager;

class CommentersSorter implements ISorter {

	/** @var ICommentsManager */
	private $commentsManager;

	public function __construct(ICommentsManager $commentsManager) {
		$this->commentsManager = $commentsManager;
	}

	public function getId() {
		return 'commenters';
	}

	/**
	 * Sorts people who commented on the given item atop (descelating) of the
	 * others
	 *
	 * @param array $sortArray
	 * @param array $context
	 */
	public function sort(array &$sortArray, array $context) {
		$commenters = $this->retrieveCommentsInformation($context['itemType'], $context['itemId']);
		if(count($commenters) === 0) {
			return;
		}

		foreach ($sortArray as $type => &$byType) {
			if(!isset($commenters[$type])) {
				continue;
			}

			usort($byType, function ($a, $b) use ($commenters, $type) {
				$r = $this->compare($a, $b, $commenters[$type]);
				return $r;
			});

			$s = '';
		}
	}

	/**
	 * @param $type
	 * @param $id
	 * @return array
	 */
	protected function retrieveCommentsInformation($type, $id) {
		$comments = $this->commentsManager->getForObject($type, $id, 1);
		if(count($comments) === 0) {
			return [];
		}

		return $this->commentsManager->getActorsInTree($comments[0]->getTopmostParentId());
	}

	protected function compare(array $a, array $b, array $commenters) {
		$a = $a['value']['shareWith'];
		$b = $b['value']['shareWith'];

		$valueA = isset($commenters[$a]) ? $commenters[$a] : 0;
		$valueB = isset($commenters[$b]) ? $commenters[$b] : 0;

		return $valueB - $valueA;
	}
}
