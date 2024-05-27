<?php
/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
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
