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

namespace OCA\Comments\Search;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\IUser;
use OCP\Search\IProvider;
use OCP\Search\ISearchQuery;
use OCP\Search\SearchResult;
use function array_map;
use function pathinfo;

class Provider implements IProvider {

	/** @var IL10N */
	private $l10n;

	/** @var IURLGenerator */
	private $urlGenerator;

	/** @var LegacyProvider */
	private $legacyProvider;

	public function __construct(IL10N $l10n,
								IURLGenerator $urlGenerator,
								LegacyProvider $legacyProvider) {
		$this->l10n = $l10n;
		$this->urlGenerator = $urlGenerator;
		$this->legacyProvider = $legacyProvider;
	}

	public function getId(): string {
		return 'comments';
	}

	public function search(IUser $user, ISearchQuery $query): SearchResult {
		return SearchResult::complete(
			$this->l10n->t('Comments'),
			array_map(function (Result $result) {
				$path = $result->path;
				$pathInfo = pathinfo($path);
				return new CommentsSearchResultEntry(
					$this->urlGenerator->linkToRoute('core.Preview.getPreviewByFileId', ['x' => 32, 'y' => 32, 'fileId' => $result->id]),
					$result->name,
					$path,
					$this->urlGenerator->linkToRoute(
						'files.view.index',
						[
							'dir' => $pathInfo['dirname'],
							'scrollto' => $pathInfo['basename'],
						]
					)
				);
			}, $this->legacyProvider->search($query->getTerm()))
		);
	}
}
