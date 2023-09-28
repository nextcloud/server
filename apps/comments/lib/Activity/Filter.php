<?php
/**
 * @copyright Copyright (c) 2016 Joas Schilling <coding@schilljs.com>
 *
 * @author Joas Schilling <coding@schilljs.com>
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
namespace OCA\Comments\Activity;

use OCP\Activity\IFilter;
use OCP\IL10N;
use OCP\IURLGenerator;

class Filter implements IFilter {
	public function __construct(
		protected IL10N $l,
		protected IURLGenerator $url,
	) {
	}

	public function getIdentifier(): string {
		return 'comments';
	}

	public function getName(): string {
		return $this->l->t('Comments');
	}

	public function getPriority(): int {
		return 40;
	}

	public function getIcon(): string {
		return $this->url->getAbsoluteURL($this->url->imagePath('core', 'actions/comment.svg'));
	}

	/**
	 * @param string[] $types
	 * @return string[] An array of allowed apps from which activities should be displayed
	 */
	public function filterTypes(array $types): array {
		return $types;
	}

	/**
	 * @return string[] An array of allowed apps from which activities should be displayed
	 */
	public function allowedApps(): array {
		return ['comments'];
	}
}
