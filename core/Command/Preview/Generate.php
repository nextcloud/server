<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Preview;

use OC\Core\Command\Info\FileUtils;
use OCP\Files\Config\IUserMountCache;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\IPreview;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Generate a preview for a file from the command-line.
 *
 * Useful in automations and for troubleshooting preview generation issues.
 *
 * @since 27.0.0
 */
class Generate extends Command {
	public function __construct(
		private IRootFolder $rootFolder,
		private IUserMountCache $userMountCache,
		private IPreview $previewManager,
		private FileUtils $fileUtils,
	) {
		parent::__construct();
	}

	protected function configure(): void {
		$this
			->setName('preview:generate')
			->setDescription('Generates a preview for a file')
			->setHelp('Generates a preview for an individual file for automation or troubleshooting purposes.')
			->addArgument(
				'file',
				InputArgument::REQUIRED,
				'file id or Nextcloud path'
			)
			->addOption(
				'size',
				's',
				InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
				'preview size(s) to generate in pixels, defaults to 64x64',
				['64x64']
			)
			->addOption(
				'crop',
				'c',
				InputOption::VALUE_NONE,
				'crop the previews instead of maintaining aspect ratio'
			)
			->addOption(
				'mode',
				'm',
				InputOption::VALUE_REQUIRED,
				'mode for generating uncropped previews, \'cover\' or \'fill\'',
				IPreview::MODE_FILL
			)
		;
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		// parse and check `file` argument value (can be a file id or path to a specific file relative to the data directory
		$fileInput = $input->getArgument('file');
		$node = $this->fileUtils->getNode($fileInput);
		if (!$node) {
			$output->writeln("<error>File ($fileInput) does not exist</error>");
			return self::FAILURE;
		}
		if (!$node instanceof File) {
			$output->writeln("<error>specified file ($fileInput) is not a file (did you specify a folder by accident?)</error>");
			return self::INVALID;
		}
		// No point in continuing if there isn't a configured preview provider for the file
		if (!$this->previewManager->isAvailable($node)) {
			$output->writeln('<error>File of type ' . $node->getMimetype() . ' does not have a preview generator configured.</error>');
			return self::FAILURE;
		}

		// parse and check `size` option value(s) ("64x64" if not specified)
		$sizeInput = $input->getOption('size');
		// parse size option value(s)
		//	(e.g. ["128"] or ["128x128"] or even ["64x64", "128x128"])
		$sizes = array_map(function (string $size) use ($output): array|null {
			if (str_contains($size, 'x')) {
				$sizeParts = explode('x', $size, 2);
			} else {
				$sizeParts = [$size, $size];
			}
			if (!is_numeric($sizeParts[0]) || !is_numeric($sizeParts[1] ?? null)) {
				// output error here to be able to inform which one size entry caused it
				$output->writeln("<error>Size ($size) is invalid</error>");
				return null;
			}
			return array_map('intval', $sizeParts);
		}, $sizeInput);
		if (in_array(null, $sizes)) {
			// error output already provided so no need for it here
			return self::FAILURE;
		}

		// parse the `crop` option value
		$crop = $input->getOption('crop');

		// parse and check the `mode` option value
		$mode = $input->getOption('mode');
		if ($mode !== IPreview::MODE_FILL && $mode !== IPreview::MODE_COVER) {
			$output->writeln("<error>Mode ($mode) is invalid</error>");
			return self::INVALID;
		}

		// generate the specifification(s) of the preview size(s) generate
		$specifications = array_map(function (array $sizes) use ($crop, $mode) {
			return [
				'width' => $sizes[0],
				'height' => $sizes[1],
				'crop' => $crop,
				'mode' => $mode,
			];
		}, $sizes);

		// generate the actual requested previews
		$this->previewManager->generatePreviews($node, $specifications);

		// inform the user what we did if we were successful
		$output->writeln('Generated <info>' . count($specifications) . '</info> preview(s)');

		return self::SUCCESS;
	}
}
