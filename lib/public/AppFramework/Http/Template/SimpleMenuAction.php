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

namespace OCP\AppFramework\Http\Template;

use OCP\AppFramework\Http\Template\IMenuAction;
use Twig_Environment;

class SimpleMenuAction implements IMenuAction {

	private $id;
	private $label;
	private $icon;
	private $link;
	private $priority = 100;
	private $detail;

	public function __construct(string $id, string $label, string $icon, string $link = '', int $priority = 100, string $detail = '') {
		$this->id = $id;
		$this->label = $label;
		$this->icon = $icon;
		$this->link = $link;
		$this->priority = $priority;
		$this->detail = $detail;
	}

	public function setId(string $id) {
		$this->id = $id;
	}

	public function setLabel(string $label) {
		$this->label = $label;
	}

	public function setDetail(string $detail) {
		$this->detail = $detail;
	}

	public function setIcon(string $icon) {
		$this->icon = $icon;
	}

	public function setLink(string $link) {
		$this->link = $link;
	}

	public function setPriority(int $priority) {
		$this->priority = $priority;
	}

	public function getId(): string {
		return $this->id;
	}

	public function getLabel(): string {
		return $this->label;
	}

	public function getIcon(): string {
		return $this->icon;
	}

	public function getLink(): string {
		return $this->link;
	}

	public function getPriority(): int {
		return $this->priority;
	}

	public function render(): string {
		$detailContent = ($this->detail !== '') ? '&nbsp;<span class="download-size">(' . $this->detail . ')</span>' : '';
		return sprintf('<li><a href="%s"><span class="icon %s"></span>%s %s</a></li>', $this->link, $this->icon, $this->label, $detailContent);
	}

}