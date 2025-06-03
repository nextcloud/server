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
use OCP\IPreview;
use OCP\Lock\LockedException;

/**
 * Generate a Blurhash string as metadata when image file is uploaded/edited.
 *
 * @template-implements IEventListener<AMetadataEvent>
 */
class GenerateBlurhashMetadata implements IEventListener {
	private const COMPONENTS_X = 4;
	private const COMPONENTS_Y = 3;

	public function __construct(
		private IPreview $preview,
	) {
	}

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

		// Preview are disabled, so we skip generating the blurhash.
		if (!$this->preview->isAvailable($file)) {
			return;
		}

		$preview = $this->preview->getPreview($file, 64, 64, cacheResult: false);
		$image = @imagecreatefromstring($preview->getContent());

		if (!$image) {
			return;
		}

		$metadata->setString('blurhash', $this->generateBlurHash($image))
			->setEtag('blurhash', $currentEtag);
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
