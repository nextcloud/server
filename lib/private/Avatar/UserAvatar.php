<?php

declare(strict_types=1);

/**
 * SPDX-FileCopyrightText: 2018 Nextcloud GmbH and Nextcloud contributors
 * SPDX-License-Identifier: AGPL-3.0-or-later
 */
namespace OC\Avatar;

use OC\NotSquareException;
use OC\User\User;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IImage;
use OCP\IL10N;
use Psr\Log\LoggerInterface;

/**
 * This class represents a registered user's avatar.
 */
class UserAvatar extends Avatar {
	public function __construct(
		private ISimpleFolder $folder,
		private IL10N $l,
		private User $user,
		LoggerInterface $logger,
		private IConfig $config,
	) {
		parent::__construct(
			$logger,
			$user,
			$config,
		);
	}

	/**
	 * Check if an avatar exists for the user
	 */
	public function exists(): bool {
		return $this->folder->fileExists('avatar.jpg') || $this->folder->fileExists('avatar.png');
	}

	/**
	 * Sets the users avatar.
	 *
	 * @param IImage|resource|string $data An image object, imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws NotSquareException if the image is not square
	 */
	public function set($data): void {
		$img = $this->getAvatarImage($data);
		$data = $img->data();

		$this->validateAvatar($img);

		$this->remove(true);
		$type = $this->getAvatarImageType($img);
		$file = $this->folder->newFile('avatar.' . $type);
		$file->putContent($data);

		try {
			$generated = $this->folder->getFile('generated');
			$generated->delete();
		} catch (NotFoundException $e) {
			//
		}

		$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'false');
		$this->user->triggerChange('avatar', $file);
	}

	/**
	 * Returns an image from several sources.
	 *
	 * @param IImage|resource|string|\GdImage $data An image object, imagedata or path to the avatar
	 */
	private function getAvatarImage($data): IImage {
		if ($data instanceof IImage) {
			return $data;
		}

		$img = new \OCP\Image();
		if (
			(is_resource($data) && get_resource_type($data) === 'gd') ||
			(is_object($data) && get_class($data) === \GdImage::class)
		) {
			$img->setResource($data);
		} elseif (is_resource($data)) {
			$img->loadFromFileHandle($data);
		} else {
			try {
				// detect if it is a path or maybe the images as string
				$result = @realpath($data);
				if ($result === false || $result === null) {
					$img->loadFromData($data);
				} else {
					$img->loadFromFile($data);
				}
			} catch (\Error $e) {
				$img->loadFromData($data);
			}
		}

		return $img;
	}

	/**
	 * Returns the avatar image type.
	 */
	private function getAvatarImageType(IImage $avatar): string {
		$type = substr($avatar->mimeType(), -3);
		if ($type === 'peg') {
			$type = 'jpg';
		}
		return $type;
	}

	/**
	 * Validates an avatar image:
	 * - must be "png" or "jpg"
	 * - must be "valid"
	 * - must be in square format
	 *
	 * @param IImage $avatar The avatar to validate
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws NotSquareException if the image is not square
	 */
	private function validateAvatar(IImage $avatar): void {
		$type = $this->getAvatarImageType($avatar);

		if ($type !== 'jpg' && $type !== 'png') {
			throw new \Exception($this->l->t('Unknown filetype'));
		}

		if (!$avatar->valid()) {
			throw new \Exception($this->l->t('Invalid image'));
		}

		if (!($avatar->height() === $avatar->width())) {
			throw new NotSquareException($this->l->t('Avatar image is not square'));
		}
	}

	/**
	 * Removes the users avatar.
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function remove(bool $silent = false): void {
		$avatars = $this->folder->getDirectoryListing();

		$this->config->setUserValue($this->user->getUID(), 'avatar', 'version',
			(string)((int)$this->config->getUserValue($this->user->getUID(), 'avatar', 'version', '0') + 1));

		foreach ($avatars as $avatar) {
			$avatar->delete();
		}
		$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'true');
		if (!$silent) {
			$this->user->triggerChange('avatar', '');
		}
	}

	/**
	 * Get the extension of the avatar. If there is no avatar throw Exception
	 *
	 * @throws NotFoundException
	 */
	private function getExtension(bool $generated, bool $darkTheme): string {
		if ($darkTheme && $generated) {
			$name = 'avatar-dark.';
		} else {
			$name = 'avatar.';
		}

		if ($this->folder->fileExists($name . 'jpg')) {
			return 'jpg';
		}

		if ($this->folder->fileExists($name . 'png')) {
			return 'png';
		}

		throw new NotFoundException;
	}

	/**
	 * Returns the avatar for an user.
	 *
	 * If there is no avatar file yet, one is generated.
	 *
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function getFile(int $size, bool $darkTheme = false): ISimpleFile {
		$generated = $this->folder->fileExists('generated');

		try {
			$ext = $this->getExtension($generated, $darkTheme);
		} catch (NotFoundException $e) {
			if (!$data = $this->generateAvatarFromSvg(1024, $darkTheme)) {
				$data = $this->generateAvatar($this->user->getDisplayName(), 1024, $darkTheme);
			}
			$avatar = $this->folder->newFile($darkTheme ? 'avatar-dark.png' : 'avatar.png');
			$avatar->putContent($data);
			$ext = 'png';

			$this->folder->newFile('generated', '');
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'true');
			$generated = true;
		}

		if ($generated) {
			if ($size === -1) {
				$path = 'avatar' . ($darkTheme ? '-dark' : '') . '.' . $ext;
			} else {
				$path = 'avatar' . ($darkTheme ? '-dark' : '') . '.' . $size . '.' . $ext;
			}
		} else {
			if ($size === -1) {
				$path = 'avatar.' . $ext;
			} else {
				$path = 'avatar.' . $size . '.' . $ext;
			}
		}

		try {
			$file = $this->folder->getFile($path);
		} catch (NotFoundException $e) {
			if ($size <= 0) {
				throw new NotFoundException;
			}
			if ($generated) {
				if (!$data = $this->generateAvatarFromSvg($size, $darkTheme)) {
					$data = $this->generateAvatar($this->user->getDisplayName(), $size, $darkTheme);
				}
			} else {
				$avatar = new \OCP\Image();
				$file = $this->folder->getFile('avatar.' . $ext);
				$avatar->loadFromData($file->getContent());
				$avatar->resize($size);
				$data = $avatar->data();
			}

			try {
				$file = $this->folder->newFile($path);
				$file->putContent($data);
			} catch (NotPermittedException $e) {
				$this->logger->error('Failed to save avatar for ' . $this->user->getUID());
				throw new NotFoundException();
			}
		}

		if ($this->config->getUserValue($this->user->getUID(), 'avatar', 'generated', null) === null) {
			$generated = $generated ? 'true' : 'false';
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', $generated);
		}

		return $file;
	}

	/**
	 * Handles user changes.
	 *
	 * @param string $feature The changed feature
	 * @param mixed $oldValue The previous value
	 * @param mixed $newValue The new value
	 * @throws NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function userChanged(string $feature, $oldValue, $newValue): void {
		// If the avatar is not generated (so an uploaded image) we skip this
		if (!$this->folder->fileExists('generated')) {
			return;
		}

		$this->remove();
	}

	/**
	 * Check if the avatar of a user is a custom uploaded one
	 */
	public function isCustomAvatar(): bool {
		return $this->config->getUserValue($this->user->getUID(), 'avatar', 'generated', 'false') !== 'true';
	}
}
