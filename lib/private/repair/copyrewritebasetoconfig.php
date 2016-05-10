<?php
/**
 * @author Lukas Reschke <lukas@owncloud.com>
 *
 * @copyright Copyright (c) 2016, ownCloud, Inc.
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

namespace OC\Repair;

use OC\Hooks\BasicEmitter;
use OC\RepairStep;
use OCP\IConfig;

/**
 * Class CopyRewriteBaseToConfig
 *
 * @package OC\Repair
 */
class CopyRewriteBaseToConfig extends BasicEmitter implements RepairStep {
	/** @var IConfig */
	private $config;

	/**
	 * @param IConfig $config
	 */
	public function __construct(IConfig $config) {
		$this->config = $config;
	}

	/**
	 * {@inheritdoc}
	 */
	public function getName() {
		return 'Copy the rewrite base to the config file';
	}

	/**
	 * {@inheritdoc}
	 */
	public function run() {
		$ocVersionFromBeforeUpdate = $this->config->getSystemValue('version', '0.0.0');

		$versionsToApplyFrom = [
			'9.0.0.19',
			'9.0.1.3',
			'9.0.2.2',
		];

		if(in_array($ocVersionFromBeforeUpdate, $versionsToApplyFrom, true)) {
			// For CLI read the value from overwrite.cli.url
			if(\OC::$CLI) {
				$webRoot = $this->config->getSystemValue('overwrite.cli.url', '');
				if($webRoot === '') {
					return;
				}
				$webRoot = parse_url($webRoot, PHP_URL_PATH);
				$webRoot = rtrim($webRoot, '/');
			} else {
				$webRoot = !empty(\OC::$WEBROOT) ? \OC::$WEBROOT : '/';
			}

			// ownCloud may be configured to live at the root folder without a
			// trailing slash being specified. In this case manually set the
			// rewrite base to `/`
			$rewriteBase = $webRoot;
			if($webRoot === '') {
				$rewriteBase = '/';
			}

			$this->config->setSystemValue('htaccess.RewriteBase', $rewriteBase);
			\OC\Setup::updateHtaccess();
		}

	}
}
