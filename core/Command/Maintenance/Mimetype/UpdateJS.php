<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC\Core\Command\Maintenance\Mimetype;

use OCP\Files\IMimeTypeDetector;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;

use Symfony\Component\Console\Output\OutputInterface;

class UpdateJS extends Command {
	public function __construct(
		protected IMimeTypeDetector $mimetypeDetector,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('maintenance:mimetype:update-js')
			->setDescription('Update mimetypelist.js');
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		// Fetch all the aliases
		$aliases = $this->mimetypeDetector->getAllAliases();

		// Output the JS
		$generatedMimetypeFile = new GenerateMimetypeFileBuilder();
		file_put_contents(\OC::$SERVERROOT . '/core/js/mimetypelist.js', $generatedMimetypeFile->generateFile($aliases));

		$output->writeln('<info>mimetypelist.js is updated');
		return 0;
	}
}
