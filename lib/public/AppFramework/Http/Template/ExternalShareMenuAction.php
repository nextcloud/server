<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCP\AppFramework\Http\Template;

use OCP\AppFramework\Http\Template\SimpleMenuAction;
use OCP\Util;
/**
 * Class LinkMenuAction
 *
 * @package OCP\AppFramework\Http\Template
 * @since 14.0.0
 */
class ExternalShareMenuAction extends SimpleMenuAction {

	/** @var string */
	private $owner;

	/** @var string */
	private $displayname;

	/** @var string */
	private $shareName;

	/**
	 * ExternalShareMenuAction constructor.
	 *
	 * @param string $label
	 * @param string $icon
	 * @param string $owner
	 * @param string $displayname
	 * @param string $shareName
	 * @since 14.0.0
	 */
	public function __construct(string $label, string $icon, string $owner, string $displayname, string $shareName) {
		parent::__construct('save', $label, $icon);
		$this->owner = $owner;
		$this->displayname = $displayname;
		$this->shareName = $shareName;
	}

	/**
	 * @since 14.0.0
	 */
	public function render(): string {
		return '<li>' .
			'<a id="save-external-share" data-protected="false" data-owner-display-name="' . Util::sanitizeHTML($this->displayname) . '" data-owner="' . Util::sanitizeHTML($this->owner) . '" data-name="' . Util::sanitizeHTML($this->shareName) . '">' .
			'<span class="icon ' . Util::sanitizeHTML($this->getIcon()) . '"></span>' .
			'<label for="remote_address">' . Util::sanitizeHTML($this->getLabel()) . '</label>' .
			'<form class="save-form hidden" action="#">' .
			'<input type="text" id="remote_address" placeholder="user@yourNextcloud.org">' .
			'<input type="submit" value=" " id="save-button-confirm" class="icon-confirm" disabled="disabled"></button>' .
			'</form>' .
			'</a>' .
			'</li>';
	}
}
