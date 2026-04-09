<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use OC\Core\Command\Base;
use OCP\Files\IMimeTypeDetector;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use OCP\Util;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Info extends Base {
	public function __construct(
		private ObjectUtil $objectUtils,
		private IMimeTypeDetector $mimeTypeDetector,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:object:info')
			->setDescription('Get the metadata of an object')
			->addArgument('object', InputArgument::REQUIRED, 'Object to get')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket to get the object from, only required in cases where it can't be determined from the config");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$object = $input->getArgument('object');
		$objectStore = $this->objectUtils->getObjectStore($input->getOption('bucket'), $output);
		if (!$objectStore) {
			return self::FAILURE;
		}

		if (!$objectStore instanceof IObjectStoreMetaData) {
			$output->writeln('<error>Configured object store does currently not support retrieve metadata</error>');
			return self::FAILURE;
		}

		if (!$objectStore->objectExists($object)) {
			$output->writeln("<error>Object $object does not exist</error>");
			return self::FAILURE;
		}

		try {
			$meta = $objectStore->getObjectMetaData($object);
		} catch (\Exception $e) {
			$msg = $e->getMessage();
			$output->writeln("<error>Failed to read $object from object store: $msg</error>");
			return self::FAILURE;
		}

		if ($input->getOption('output') === 'plain' && isset($meta['size'])) {
			$meta['size'] = Util::humanFileSize($meta['size']);
		}
		if (isset($meta['mtime'])) {
			$meta['mtime'] = $meta['mtime']->format(\DateTimeImmutable::ATOM);
		}
		if (!isset($meta['mimetype'])) {
			$handle = $objectStore->readObject($object);
			$head = fread($handle, 8192);
			fclose($handle);
			$meta['mimetype'] = $this->mimeTypeDetector->detectString($head);
		}

		$this->writeArrayInOutputFormat($input, $output, $meta);

		return self::SUCCESS;
	}

}
