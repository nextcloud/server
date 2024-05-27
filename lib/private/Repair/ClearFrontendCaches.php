<?php
/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OC\Template\JSCombiner;
use OCP\ICacheFactory;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;

class ClearFrontendCaches implements IRepairStep {
	/** @var ICacheFactory */
	protected $cacheFactory;

	/** @var JSCombiner */
	protected $jsCombiner;

	public function __construct(ICacheFactory $cacheFactory,
		JSCombiner $JSCombiner) {
		$this->cacheFactory = $cacheFactory;
		$this->jsCombiner = $JSCombiner;
	}

	public function getName() {
		return 'Clear frontend caches';
	}

	public function run(IOutput $output) {
		try {
			$c = $this->cacheFactory->createDistributed('imagePath');
			$c->clear();
			$output->info('Image cache cleared');

			$this->jsCombiner->resetCache();
			$output->info('JS cache cleared');
		} catch (\Exception $e) {
			$output->warning('Unable to clear the frontend cache');
		}
	}
}
