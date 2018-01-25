<?php
/**
 * @copyright Copyright (c) 2018 Julius Härtl <jus@bitgrid.net>
 *
 * @author Julius Härtl <jus@bitgrid.net>
 *
 * @license GNU AGPL version 3 or any later version
 *
 *  This program is free software: you can redistribute it and/or modify
 *  it under the terms of the GNU Affero General Public License as
 *  published by the Free Software Foundation, either version 3 of the
 *  License, or (at your option) any later version.
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU Affero General Public License for more details.
 *
 *  You should have received a copy of the GNU Affero General Public License
 *  along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\Files_Sharing\Template;

use OCP\AppFramework\Http\Template\SimpleMenuAction;
use OCP\Util;

class ExternalShareMenuAction extends SimpleMenuAction {

	private $owner;
	private $displayname;
	private $shareName;

	public function __construct($label, $icon, $owner, $displayname, $shareName) {
		parent::__construct('save', $label, $icon);
		$this->owner = $owner;
		$this->displayname = $displayname;
		$this->shareName = $shareName;
	}

	public function render(): string {
		return '<li>' .
			'<a id="save" data-protected="false" data-owner-display-name="' . Util::sanitizeHTML($this->displayname) . '" data-owner="' . Util::sanitizeHTML($this->owner) . '" data-name="' . Util::sanitizeHTML($this->shareName) . '">' .
			'<span class="icon ' . Util::sanitizeHTML($this->getIcon()) . '"></span>' .
			'<span id="save-button">' . Util::sanitizeHTML($this->getLabel()) . '</span>' .
			'<form class="save-form hidden" action="#">' .
			'<input type="text" id="remote_address" placeholder="user@yourNextcloud.org">' .
			'<button id="save-button-confirm" class="icon-confirm svg" disabled=""></button>' .
			'</form>' .
			'</a>' .
			'</li>';
	}
}