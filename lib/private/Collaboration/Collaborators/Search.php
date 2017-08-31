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

use OCP\Collaboration\Collaborators\ISearch;
use OCP\Collaboration\Collaborators\ISearchPlugin;
use OCP\Collaboration\Collaborators\ISearchResult;
use OCP\IContainer;
use OCP\Share;

class Search implements ISearch {
	/** @var IContainer */
	private $c;

	public function __construct(IContainer $c) {
		$this->c = $c;
	}

	public function search($search, array $shareTypes, $lookup, $limit, $offset) {
		$hasMoreResults = false;

		$pluginList = [
			Share::SHARE_TYPE_USER => UserPlugin::class,
			Share::SHARE_TYPE_GROUP => GroupPlugin::class,
			Share::SHARE_TYPE_CIRCLE => CirclePlugin::class,
			Share::SHARE_TYPE_EMAIL => MailPlugin::class,
			Share::SHARE_TYPE_REMOTE => RemotePlugin::class,
		];

		/** @var ISearchResult $searchResult */
		$searchResult = $this->c->resolve(SearchResult::class);

		foreach ($shareTypes as $type) {
			if(!isset($pluginList[$type])) {
				continue;
			}
			/** @var ISearchPlugin $searchPlugin */
			$searchPlugin = $this->c->resolve($pluginList[$type]);
			$hasMoreResults |= $searchPlugin->search($search, $limit, $offset, $searchResult);
		}

		// Get from lookup server, not a separate share type
		if ($lookup) {
			$searchPlugin = $this->c->resolve(LookupPlugin::class);
			$hasMoreResults |= $searchPlugin->search($search, $limit, $offset, $searchResult);
		}

		// sanitizing, could go into the plugins as well

		// if we have a exact match, either for the federated cloud id or for the
		// email address we only return the exact match. It is highly unlikely
		// that the exact same email address and federated cloud id exists
		if($searchResult->hasExactIdMatch('emails') && !$searchResult->hasExactIdMatch('remotes')) {
			$searchResult->unsetResult('remotes');
		} elseif (!$searchResult->hasExactIdMatch('emails') && $searchResult->hasExactIdMatch('remotes')) {
			$searchResult->unsetResult('emails');
		}

		return [$searchResult->asArray(), $hasMoreResults];
	}
}
