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

use OCP\AppFramework\Http\TemplateResponse;

class PublicTemplateResponse extends TemplateResponse {

	private $headerTitle = '';
	private $headerDetails = '';
	private $headerActions = [];

	public function __construct(string $appName, string $templateName, array $params = array()) {
		parent::__construct($appName, $templateName, $params, 'public');
		\OC_Util::addScript('core', 'public/publicpage');
	}

	public function setHeaderTitle(string $title) {
		$this->headerTitle = $title;
	}

	public function getHeaderTitle(): string {
		return $this->headerTitle;
	}

	public function setHeaderDetails(string $details) {
		$this->headerDetails = $details;
	}

	public function getHeaderDetails(): string {
		return $this->headerDetails;
	}

	public function setHeaderActions(array $actions) {
		foreach ($actions as $action) {
			if ($actions instanceof IMenuAction) {
				throw new \InvalidArgumentException('Actions must be of type IMenuAction');
			}
			$this->headerActions[] = $action;
		}
	}

	public function addAction(IMenuAction $action) {
		$this->headerActions[] = $action;
	}

	public function getPrimaryAction(): IMenuAction {
		$lowest = null;
		foreach ($this->headerActions as $action) {
			if($lowest === null || $action->getPriority() < $lowest->getPriority()) {
				$lowest = $action;
			}
		}
		return $lowest;
	}

	public function getActionCount(): int {
		return count($this->headerActions);
	}

	/**
	 * @return IMenuAction[]
	 */
	public function getOtherActions(): array {
		$list = [];
		$primary = $this->getPrimaryAction();
		foreach ($this->headerActions as $action) {
			if($primary !== $action) {
				$list[] = $action;
			}
		}
		return $list;
	}


	public function render() {
		$params = array_merge($this->getParams(), [
			'template' => $this,
		]);
		$this->setParams($params);
		return  parent::render();
	}

}