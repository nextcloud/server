<?php
/**
 * @author Adam Williamson <awilliam@redhat.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 * @author Thomas Müller <thomas.mueller@tmit.eu>
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

use Doctrine\DBAL\Platforms\MySqlPlatform;
use OC\Hooks\BasicEmitter;

class AssetCache extends BasicEmitter implements \OC\RepairStep {

	public function getName() {
		return 'Clear asset cache after upgrade';
	}

	public function run() {
		if (!\OC_Template::isAssetPipelineEnabled()) {
			$this->emit('\OC\Repair', 'info', array('Asset pipeline disabled -> nothing to do'));
			return;
		}
		$assetDir = \OC::$server->getConfig()->getSystemValue('assetdirectory', \OC::$SERVERROOT) . '/assets';
		\OC_Helper::rmdirr($assetDir, false);
		$this->emit('\OC\Repair', 'info', array('Asset cache cleared.'));
	}
}

