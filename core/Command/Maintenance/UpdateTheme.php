<?php

/**
 * SPDX-FileCopyrightText: 2017 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Core\Command\Maintenance;

use OC\Core\Command\Maintenance\Mimetype\UpdateJS;
use OCP\Files\IMimeTypeDetector;
use OCP\ICacheFactory;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

class UpdateTheme extends UpdateJS {
	public function __construct(
		IMimeTypeDetector $mimetypeDetector,
		protected ICacheFactory $cacheFactory,
	) {
		parent::__construct($mimetypeDetector);
	}

	protected function configure() {
		$this
			->setName('maintenance:theme:update')
			->setDescription('Apply custom theme changes');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		// run mimetypelist.js update since themes might change mimetype icons
		parent::execute($input, $output);

		// cleanup image cache
		$c = $this->cacheFactory->createDistributed('imagePath');
		$c->clear('');
		$output->writeln('<info>Image cache cleared</info>');
		return 0;
	}
}
