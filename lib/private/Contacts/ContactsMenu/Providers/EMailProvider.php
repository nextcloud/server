<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Contacts\ContactsMenu\Providers;

use OCP\Contacts\ContactsMenu\IActionFactory;
use OCP\Contacts\ContactsMenu\IEntry;
use OCP\Contacts\ContactsMenu\IProvider;
use OCP\IURLGenerator;

class EMailProvider implements IProvider {
	public function __construct(
		private IActionFactory $actionFactory,
		private IURLGenerator $urlGenerator,
	) {
	}

	public function process(IEntry $entry): void {
		$iconUrl = $this->urlGenerator->getAbsoluteURL($this->urlGenerator->imagePath('core', 'actions/mail.svg'));
		foreach ($entry->getEMailAddresses() as $address) {
			if (empty($address)) {
				// Skip
				continue;
			}
			$action = $this->actionFactory->newEMailAction($iconUrl, $address, $address, 'email');
			$entry->addAction($action);
		}
	}
}
