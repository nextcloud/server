<?php
/**
 * @copyright 2017 Christoph Wurst <christoph@winzerhof-wurst.at>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
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
namespace OC\Contacts\ContactsMenu;

use OC\Contacts\ContactsMenu\Actions\LinkAction;
use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\ILinkAction;

class ActionFactory implements IActionFactory {
	/**
	 * {@inheritDoc}
	 */
	public function newLinkAction(string $icon, string $name, string $href, string $appId = ''): ILinkAction {
		$action = new LinkAction();
		$action->setName($name);
		$action->setIcon($icon);
		$action->setHref($href);
		$action->setAppId($appId);
		return $action;
	}

	/**
	 * {@inheritDoc}
	 */
	public function newEMailAction(string $icon, string $name, string $email, string $appId = ''): ILinkAction {
		return $this->newLinkAction($icon, $name, 'mailto:' . $email, $appId);
	}
}
