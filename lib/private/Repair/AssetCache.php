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

use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class AssetCache implements IRepairStep {

	public function getName() {
		return 'Clear asset cache after upgrade';
	}

	public function run(IOutput $output) {
		if (!\OC_Template::isAssetPipelineEnabled()) {
			$output->info('Asset pipeline disabled -> nothing to do');
			return;
		}
		$assetDir = \OC::$server->getConfig()->getSystemValue('assetdirectory', \OC::$SERVERROOT) . '/assets';
		\OC_Helper::rmdirr($assetDir, false);
		$output->info('Asset cache cleared.');
	}
}

