<?php
/**
 * @copyright 2022 Thomas Citharel <nextcloud@tcit.fr>
 *
 * @author Thomas Citharel <nextcloud@tcit.fr>
 *
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
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */
namespace OC\Authentication\Token;

use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\TimedJob;
use OCP\IConfig;

class TokenCleanupJob extends TimedJob {
	
	/**
	 *  @var IConfig 
	 * */
	protected $config;
	private IProvider $provider;

	public function __construct(ITimeFactory $time, IProvider $provider, IConfig $config) {
		parent::__construct($time);
		$this->provider = $provider;
		// Run once a day at off-peak time
		$this->setInterval(24 * 60 * 60);
		$this->setTimeSensitivity(self::TIME_INSENSITIVE);
		$this->config = $config;
	}

	protected function run($argument) {
		if ($this->config->getSystemValueBool('auth.authtoken.v1.disabled')) {
			return;
		}
		
		$this->provider->invalidateOldTokens();
	}
}
