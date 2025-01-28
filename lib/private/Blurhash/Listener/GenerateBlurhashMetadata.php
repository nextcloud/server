<?php

declare(strict_types=1);
/**
 * SPDX-FileCopyrightText: 2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */

namespace OC\Blurhash\Listener;

use GdImage;
use kornrunner\Blurhash\Blurhash;
use OC\Files\Node\File;
use OCP\EventDispatcher\Event;
use OCP\EventDispatcher\IEventDispatcher;
use OCP\EventDispatcher\IEventListener;
use OCP\Files\GenericFileException;
use OCP\Files\NotPermittedException;
use OCP\FilesMetadata\AMetadataEvent;
use OCP\FilesMetadata\Event\MetadataBackgroundEvent;
use OCP\FilesMetadata\Event\MetadataLiveEvent;
use OCP\Lock\LockedException;

/**
 * Generate a Blurhash string as metadata when image file is uploaded/edited.
 *
 * @template-implements IEventListener<AMetadataEvent>
 */
class GenerateBlurhashMetadata implements IEventListener {
	private const RESIZE_BOXSIZE = 30;

	private const COMPONENTS_X = 4;
	private const COMPONENTS_Y = 3;

	/**
	 * @throws NotPermittedException
	 * @throws GenericFileException
	 * @throws LockedException
	 */
	public function handle(Event $event): void {
		if (!($event instanceof MetadataLiveEvent)
			&& !($event instanceof MetadataBackgroundEvent)) {
			return;
		}

		$file = $event->getNode();
		if (!($file instanceof File)) {
			return;
		}

		$currentEtag = $file->getEtag();
		$metadata = $event->getMetadata();
		if ($metadata->getEtag('blurhash') === $currentEtag) {
			return;
		}

		// too heavy to run on the live thread, request a rerun as a background job
		if ($event instanceof MetadataLiveEvent) {
			$event->requestBackgroundJob();
			return;
		}

		if (!str_starts_with($file->getMimetype(), 'image/')) {
			return;
		}

		$image = $this->resizedImageFromFile($file);
		if (!$image) {
			return;
		}

		$metadata->setString('blurhash', $this->generateBlurHash($image))
			->setEtag('blurhash', $currentEtag);
	}

	/**
	 * @param File $file
	 *
	 * @return GdImage|false
	 * @throws GenericFileException
	 * @throws NotPermittedException
	 * @throws LockedException
	 */
	private function resizedImageFromFile(File $file): GdImage|false {
		$image = @imagecreatefromstring($file->getContent());
		if ($image === false) {
			return false;
		}

		$currX = imagesx($image);
		$currY = imagesy($image);

		if ($currX > $currY) {
			$newX = self::RESIZE_BOXSIZE;
			$newY = intval($currY * $newX / $currX);
		} else {
			$newY = self::RESIZE_BOXSIZE;
			$newX = intval($currX * $newY / $currY);
		}

		$newImage = imagescale($image, $newX, $newY);
		return ($newImage !== false) ? $newImage : $image;
	}

	/**
	 * @param GdImage $image
	 *
	 * @return string
	 */
	public function generateBlurHash(GdImage $image): string {
		$width = imagesx($image);
		$height = imagesy($image);

		$pixels = [];
		for ($y = 0; $y < $height; ++$y) {
			$row = [];
			for ($x = 0; $x < $width; ++$x) {
				$index = imagecolorat($image, $x, $y);
				$colors = imagecolorsforindex($image, $index);
				$row[] = [$colors['red'], $colors['green'], $colors['blue']];
			}

			$pixels[] = $row;
		}

		return Blurhash::encode($pixels, self::COMPONENTS_X, self::COMPONENTS_Y);
	}

	/**
	 * @param IEventDispatcher $eventDispatcher
	 *
	 * @return void
	 */
	public static function loadListeners(IEventDispatcher $eventDispatcher): void {
		$eventDispatcher->addServiceListener(MetadataLiveEvent::class, self::class);
		$eventDispatcher->addServiceListener(MetadataBackgroundEvent::class, self::class);
	}
}
