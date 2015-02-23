<?php
/**
 * @author Joas Schilling <nickvergessen@gmx.de>
 *
 * @copyright Copyright (c) 2015, ownCloud, Inc.
 * @license AGPL-3.0
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC;


use OCP\IConfig;
use OCP\IDateTimeZone;
use OCP\ISession;

class DateTimeZone implements IDateTimeZone {
	/** @var IConfig */
	protected $config;

	/** @var ISession */
	protected $session;

	/**
	 * Constructor
	 *
	 * @param IConfig $config
	 * @param ISession $session
	 */
	public function __construct(IConfig $config, ISession $session) {
		$this->config = $config;
		$this->session = $session;
	}

	/**
	 * Get the timezone of the current user, based on his session information and config data
	 *
	 * @return \DateTimeZone
	 */
	public function getTimeZone() {
		$timeZone = $this->config->getUserValue($this->session->get('user_id'), 'core', 'timezone', null);
		if ($timeZone === null) {
			if ($this->session->exists('timezone')) {
				$offsetHours = $this->session->get('timezone');
				// Note: the timeZone name is the inverse to the offset,
				// so a positive offset means negative timeZone
				// and the other way around.
				if ($offsetHours > 0) {
					return new \DateTimeZone('Etc/GMT-' . $offsetHours);
				} else {
					return new \DateTimeZone('Etc/GMT+' . abs($offsetHours));
				}
			} else {
				return new \DateTimeZone('UTC');
			}
		}
		return new \DateTimeZone($timeZone);
	}
}
