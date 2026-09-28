<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Repair;

use OCA\Theming\ImageManager;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IConfig;
use OCP\Migration\IOutput;
use OCP\Migration\IRepairStep;
use OCP\Server;

class RepairLogoDimension implements IRepairStep {
	public function __construct(
		protected IConfig $config,
	) {
	}

	public function getName(): string {
		return 'Cache logo dimension to fix size in emails on Outlook';
	}

	public function run(IOutput $output): void {
		$logoDimensions = $this->config->getAppValue('theming', 'logoDimensions');
		if (preg_match('/^\d+x\d+$/', $logoDimensions)) {
			$output->info('Logo dimensions are already known');
			return;
		}

		try {
			/** @var ImageManager $imageManager */
			$imageManager = Server::get(ImageManager::class);
		} catch (\Throwable) {
			$output->info('Theming is disabled');
			return;
		}

		if (!$imageManager->hasImage('logo')) {
			$output->info('Theming is not used to provide a logo');
			return;
		}

		try {
			try {
				$simpleFile = $imageManager->getImage('logo', false);
				$image = @imagecreatefromstring($simpleFile->getContent());
			} catch (NotFoundException|NotPermittedException) {
				$simpleFile = $imageManager->getImage('logo');
				$image = false;
			}
		} catch (NotFoundException|NotPermittedException) {
			$output->info('Theming is not used to provide a logo');
			return;
		}

		$dimensions = '';
		if ($image !== false) {
			$dimensions = imagesx($image) . 'x' . imagesy($image);
		} elseif (str_starts_with($simpleFile->getMimeType(), 'image/svg')) {
			$matched = preg_match('/viewbox=["\']\d* \d* (\d*\.?\d*) (\d*\.?\d*)["\']/i', $simpleFile->getContent(), $matches);
			if ($matched) {
				$dimensions = $matches[1] . 'x' . $matches[2];
			}
		}

		if (!$dimensions) {
			$output->warning('Failed to read dimensions from logo');
			$this->config->deleteAppValue('theming', 'logoDimensions');
			return;
		}

		$dimensions = imagesx($image) . 'x' . imagesy($image);
		$this->config->setAppValue('theming', 'logoDimensions', $dimensions);
		$output->info('Updated logo dimensions: ' . $dimensions);
	}
}
