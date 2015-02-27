<?php
/**
 * Copyright (c) 2014 Thomas Müller <deepdiver@owncloud.com>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
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

