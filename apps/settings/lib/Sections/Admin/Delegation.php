<?php
/**
 * @copyright Copyright (c) 2021 Nextcloud GmbH
 *
 * @author Carl Schwan <carl@carlschwan.eu>
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

namespace OCA\Settings\Sections\Admin;

use OCP\IL10N;
use OCP\IURLGenerator;
use OCP\Settings\IIconSection;

class Delegation implements IIconSection {
	/** @var IL10N */
	private $l;
	/** @var IURLGenerator */
	private $url;

	/**
	 * @param IURLGenerator $url
	 * @param IL10N $l
	 */
	public function __construct(IURLGenerator $url, IL10N $l) {
		$this->url = $url;
		$this->l = $l;
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getID() {
		return 'admindelegation';
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getName() {
		return $this->l->t('Administration privileges');
	}

	/**
	 * {@inheritdoc}
	 * @return int
	 */
	public function getPriority() {
		return 54;
	}

	/**
	 * {@inheritdoc}
	 * @return string
	 */
	public function getIcon() {
		return $this->url->imagePath('core', 'actions/user-admin.svg');
	}
}
