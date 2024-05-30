<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2019 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CardDAV;

use Sabre\CardDAV\Card;
use Sabre\DAV\INode;
use Sabre\DAV\PropFind;
use Sabre\DAV\Server;
use Sabre\DAV\ServerPlugin;
use Sabre\VObject\Component\VCard;
use Sabre\VObject\Reader;

class HasPhotoPlugin extends ServerPlugin {

	/** @var Server */
	protected $server;

	/**
	 * Initializes the plugin and registers event handlers
	 *
	 * @param Server $server
	 * @return void
	 */
	public function initialize(Server $server) {
		$server->on('propFind', [$this, 'propFind']);
	}

	/**
	 * Adds all CardDAV-specific properties
	 *
	 * @param PropFind $propFind
	 * @param INode $node
	 * @return void
	 */
	public function propFind(PropFind $propFind, INode $node) {
		$ns = '{http://nextcloud.com/ns}';

		if ($node instanceof Card) {
			$propFind->handle($ns . 'has-photo', function () use ($node) {
				$vcard = Reader::read($node->get());
				return $vcard instanceof VCard
					&& $vcard->PHOTO
					// Either the PHOTO is a url (doesn't start with data:) or the mimetype has to start with image/
					&& (!str_starts_with($vcard->PHOTO->getValue(), 'data:')
						|| str_starts_with($vcard->PHOTO->getValue(), 'data:image/'))
				;
			});
		}
	}

	/**
	 * Returns a plugin name.
	 *
	 * Using this name other plugins will be able to access other plugins
	 * using \Sabre\DAV\Server::getPlugin
	 *
	 * @return string
	 */
	public function getPluginName() {
		return 'vcard-has-photo';
	}

	/**
	 * Returns a bunch of meta-data about the plugin.
	 *
	 * Providing this information is optional, and is mainly displayed by the
	 * Browser plugin.
	 *
	 * The description key in the returned array may contain html and will not
	 * be sanitized.
	 *
	 * @return array
	 */
	public function getPluginInfo() {
		return [
			'name' => $this->getPluginName(),
			'description' => 'Return a boolean stating if the vcard have a photo property set or not.'
		];
	}
}
