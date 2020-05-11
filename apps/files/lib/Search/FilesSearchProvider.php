<?php

declare(strict_types=1);

/**
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

namespace OCA\Files\Search;

use OCP\IL10N;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;

class FilesSearchProvider implements IProvider {

	/** @var IL10N */
	private $l10n;

	public function __construct(IL10N $l10n) {
		$this->l10n = $l10n;
	}

	public function getId(): string {
		return 'files';
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		return SearchResult::complete(
			$this->l10n->t('Files'),
			[
				new FilesSearchResultEntry(
					"path/to/icon.png",
					"cute cats.jpg",
					"/Cats",
					"/f/21156"
				),
				new FilesSearchResultEntry(
					"path/to/icon.png",
					"cat 1.jpg",
					"/Cats",
					"/f/21192"
				),
				new FilesSearchResultEntry(
					"path/to/icon.png",
					"cat 2.jpg",
					"/Cats",
					"/f/25942"
				),
			]
		);
	}
}
