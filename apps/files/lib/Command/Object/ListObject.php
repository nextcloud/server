<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OCA\Files\Command\Object;

use OC\Core\Command\Base;
use OCP\Files\ObjectStore\IObjectStoreMetaData;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class ListObject extends Base {
	private const CHUNK_SIZE = 100;

	public function __construct(
		private readonly ObjectUtil $objectUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		parent::configure();
		$this
			->setName('files:object:list')
			->setDescription('List all objects in the object store')
			->addOption('bucket', 'b', InputOption::VALUE_REQUIRED, "Bucket to list the objects from, only required in cases where it can't be determined from the config");
	}

	public function execute(InputInterface $input, OutputInterface $output): int {
		$objectStore = $this->objectUtils->getObjectStore($input->getOption('bucket'), $output);
		if (!$objectStore) {
			return self::FAILURE;
		}

		if (!$objectStore instanceof IObjectStoreMetaData) {
			$output->writeln('<error>Configured object store does currently not support listing objects</error>');
			return self::FAILURE;
		}
		$outputType = $input->getOption('output');
		$humanOutput = $outputType === self::OUTPUT_FORMAT_PLAIN;

		if (!$humanOutput) {
			$output->writeln('[');
		}
		$objects = $objectStore->listObjects();
		$first = true;

		foreach ($this->chunkIterator($objects, self::CHUNK_SIZE) as $chunk) {
			if ($outputType === self::OUTPUT_FORMAT_PLAIN) {
				$this->outputChunk($input, $output, $chunk);
			} else {
				foreach ($chunk as $object) {
					if (!$first) {
						$output->writeln(',');
					}
					$row = $this->formatObject($object, $humanOutput);
					if ($outputType === self::OUTPUT_FORMAT_JSON_PRETTY) {
						$output->write(json_encode($row, JSON_PRETTY_PRINT));
					} else {
						$output->write(json_encode($row));
					}
					$first = false;
				}
			}
		}

		if (!$humanOutput) {
			$output->writeln("\n]");
		}

		return self::SUCCESS;
	}

	private function formatObject(array $object, bool $humanOutput): array {
		$row = array_merge([
			'urn' => $object['urn'],
		], ($object['metadata'] ?? []));

		if ($humanOutput && isset($row['size'])) {
			$row['size'] = \OC_Helper::humanFileSize($row['size']);
		}
		if (isset($row['mtime'])) {
			$row['mtime'] = $row['mtime']->format(\DateTimeImmutable::ATOM);
		}
		return $row;
	}

	private function outputChunk(InputInterface $input, OutputInterface $output, iterable $chunk): void {
		$result = [];
		$humanOutput = $input->getOption('output') === "plain";

		foreach ($chunk as $object) {
			$result[] = $this->formatObject($object, $humanOutput);
		}
		$this->writeTableInOutputFormat($input, $output, $result);
	}

	function chunkIterator(\Iterator $iterator, int $count): \Iterator {
		$chunk = [];

		for($i = 0; $iterator->valid(); $i++){
			$chunk[] = $iterator->current();
			$iterator->next();
			if(count($chunk) == $count){
				yield $chunk;
				$chunk = [];
			}
		}

		if(count($chunk)){
			yield $chunk;
		}
	}
}
