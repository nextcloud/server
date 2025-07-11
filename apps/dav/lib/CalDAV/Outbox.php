<?php

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OCA\DAV\CalDAV;

use OCP\IConfig;
use Sabre\CalDAV\Plugin as CalDAVPlugin;

/**
 * Class Outbox
 *
 * @package OCA\DAV\CalDAV
 */
class Outbox extends \Sabre\CalDAV\Schedule\Outbox {

	/** @var null|bool */
	private $disableFreeBusy = null;

	/**
	 * Outbox constructor.
	 *
	 * @param IConfig $config
	 * @param string $principalUri
	 */
	public function __construct(
		private IConfig $config,
		string $principalUri,
	) {
		parent::__construct($principalUri);
	}

	/**
	 * Returns a list of ACE's for this node.
	 *
	 * Each ACE has the following properties:
	 *   * 'privilege', a string such as {DAV:}read or {DAV:}write. These are
	 *     currently the only supported privileges
	 *   * 'principal', a url to the principal who owns the node
	 *   * 'protected' (optional), indicating that this ACE is not allowed to
	 *      be updated.
	 *
	 * @return array
	 */
	public function getACL() {
		// getACL is called so frequently that we cache the config result
		if ($this->disableFreeBusy === null) {
			$this->disableFreeBusy = ($this->config->getAppValue('dav', 'disableFreeBusy', 'no') === 'yes');
		}

		$commonAcl = [
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-read',
				'protected' => true,
			],
			[
				'privilege' => '{DAV:}read',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			],
		];

		// schedule-send is an aggregate privilege for:
		// - schedule-send-invite
		// - schedule-send-reply
		// - schedule-send-freebusy
		//
		// If FreeBusy is disabled, we have to remove the latter privilege

		if ($this->disableFreeBusy) {
			return array_merge($commonAcl, [
				[
					'privilege' => '{' . CalDAVPlugin::NS_CALDAV . '}schedule-send-invite',
					'principal' => $this->getOwner(),
					'protected' => true,
				],
				[
					'privilege' => '{' . CalDAVPlugin::NS_CALDAV . '}schedule-send-invite',
					'principal' => $this->getOwner() . '/calendar-proxy-write',
					'protected' => true,
				],
				[
					'privilege' => '{' . CalDAVPlugin::NS_CALDAV . '}schedule-send-reply',
					'principal' => $this->getOwner(),
					'protected' => true,
				],
				[
					'privilege' => '{' . CalDAVPlugin::NS_CALDAV . '}schedule-send-reply',
					'principal' => $this->getOwner() . '/calendar-proxy-write',
					'protected' => true,
				],
			]);
		}

		return array_merge($commonAcl, [
			[
				'privilege' => '{' . CalDAVPlugin::NS_CALDAV . '}schedule-send',
				'principal' => $this->getOwner(),
				'protected' => true,
			],
			[
				'privilege' => '{' . CalDAVPlugin::NS_CALDAV . '}schedule-send',
				'principal' => $this->getOwner() . '/calendar-proxy-write',
				'protected' => true,
			],
		]);
	}
}
