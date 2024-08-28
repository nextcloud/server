<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Core\Command\Preview;

use OCP\Files\Config\IUserMountCache;
use OCP\Files\File;
use OCP\Files\IRootFolder;
use OCP\Files\Node;
use OCP\Files\NotFoundException;
use OCP\IPreview;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class Generate extends Command {
	public function __construct(
		private IRootFolder $rootFolder,
		private IUserMountCache $userMountCache,
		private IPreview $previewManager,
	) {
		parent::__construct();
	}

	protected function configure() {
		$this
			->setName('preview:generate')
			->setDescription('generate a preview for a file')
			->addArgument('file', InputArgument::REQUIRED, 'path or fileid of the file to generate the preview for')
			->addOption('size', 's', InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED, 'size to generate the preview for in pixels, defaults to 64x64', ['64x64'])
			->addOption('crop', 'c', InputOption::VALUE_NONE, 'crop the previews instead of maintaining aspect ratio')
			->addOption('mode', 'm', InputOption::VALUE_REQUIRED, "mode for generating uncropped previews, 'cover' or 'fill'", IPreview::MODE_FILL);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int {
		$fileInput = $input->getArgument('file');
		$sizes = $input->getOption('size');
		$sizes = array_map(function (string $size) use ($output) {
			if (str_contains($size, 'x')) {
				$sizeParts = explode('x', $size, 2);
			} else {
				$sizeParts = [$size, $size];
			}
			if (!is_numeric($sizeParts[0]) || !is_numeric($sizeParts[1] ?? null)) {
				$output->writeln("<error>Invalid size $size</error>");
				return null;
			}

			return array_map('intval', $sizeParts);
		}, $sizes);
		if (in_array(null, $sizes)) {
			return 1;
		}

		$mode = $input->getOption('mode');
		if ($mode !== IPreview::MODE_FILL && $mode !== IPreview::MODE_COVER) {
			$output->writeln("<error>Invalid mode $mode</error>");
			return 1;
		}
		$crop = $input->getOption('crop');
		$file = $this->getFile($fileInput);
		if (!$file) {
			$output->writeln("<error>File $fileInput not found</error>");
			return 1;
		}
		if (!$file instanceof File) {
			$output->writeln("<error>Can't generate previews for folders</error>");
			return 1;
		}

		if (!$this->previewManager->isAvailable($file)) {
			$output->writeln('<error>No preview generator available for file of type' . $file->getMimetype() . '</error>');
			return 1;
		}

		$specifications = array_map(function (array $sizes) use ($crop, $mode) {
			return [
				'width' => $sizes[0],
				'height' => $sizes[1],
				'crop' => $crop,
				'mode' => $mode,
			];
		}, $sizes);

		$this->previewManager->generatePreviews($file, $specifications);
		if (count($specifications) > 1) {
			$output->writeln('generated <info>' . count($specifications) . '</info> previews');
		} else {
			$output->writeln('preview generated');
		}
		return 0;
	}

	private function getFile(string $fileInput): ?Node {
		if (is_numeric($fileInput)) {
			$mounts = $this->userMountCache->getMountsForFileId((int)$fileInput);
			if (!$mounts) {
				return null;
			}
			$mount = $mounts[0];
			$userFolder = $this->rootFolder->getUserFolder($mount->getUser()->getUID());
			return $userFolder->getFirstNodeById((int)$fileInput);
		} else {
			try {
				return $this->rootFolder->get($fileInput);
			} catch (NotFoundException $e) {
				return null;
			}
		}
	}
}
