<?php
declare(strict_types=1);

namespace OC\Avatar;

use OC\NotSquareException;
use OC\User\User;
use OC_Image;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\Files\SimpleFS\ISimpleFile;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\IConfig;
use OCP\IImage;
use OCP\IL10N;
use OCP\ILogger;

/**
 * This clas represents a registered user's avatar.
 *
 * @package OC\Avatar
 */
class UserAvatar extends Avatar {
	/** @var IConfig */
	private $config;

	/** @var ISimpleFolder */
	private $folder;

	/** @var IL10N */
	private $l;

	/** @var User */
	private $user;

	/**
	 * UserAvatar constructor.
	 *
	 * @param IConfig $config The configuration
	 * @param ISimpleFolder $folder The avatar files folder
	 * @param IL10N $l The localization helper
	 * @param User $user The user this class manages the avatar for
	 * @param ILogger $logger The logger
	 */
	public function __construct(
		ISimpleFolder $folder,
		IL10N $l,
		$user,
		ILogger $logger,
		IConfig $config) {
		parent::__construct($logger);
		$this->folder = $folder;
		$this->l = $l;
		$this->user = $user;
		$this->config = $config;
	}

	/**
	 * Check if an avatar exists for the user
	 *
	 * @return bool
	 */
	public function exists() {
		return $this->folder->fileExists('avatar.jpg') || $this->folder->fileExists('avatar.png');
	}

	/**
	 * Sets the users avatar.
	 *
	 * @param IImage|resource|string $data An image object, imagedata or path to set a new avatar
	 * @throws \Exception if the provided file is not a jpg or png image
	 * @throws \Exception if the provided image is not valid
	 * @throws NotSquareException if the image is not square
	 * @return void
	 */
	public function set($data) {

		if ($data instanceof IImage) {
			$img = $data;
			$data = $img->data();
		} else {
			$img = new OC_Image();
			if (is_resource($data) && get_resource_type($data) === "gd") {
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
		}
		$type = substr($img->mimeType(), -3);
		if ($type === 'peg') {
			$type = 'jpg';
		}
		if ($type !== 'jpg' && $type !== 'png') {
			throw new \Exception($this->l->t('Unknown filetype'));
		}

		if (!$img->valid()) {
			throw new \Exception($this->l->t('Invalid image'));
		}

		if (!($img->height() === $img->width())) {
			throw new NotSquareException($this->l->t('Avatar image is not square'));
		}

		$this->remove();
		$file = $this->folder->newFile('avatar.' . $type);
		$file->putContent($data);

		try {
			$generated = $this->folder->getFile('generated');
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'false');
			$generated->delete();
		} catch (NotFoundException $e) {
			//
		}
		$this->user->triggerChange('avatar', $file);
	}

	/**
	 * Removes the users avatar.
	 * @return void
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function remove() {
		$avatars = $this->folder->getDirectoryListing();

		$this->config->setUserValue($this->user->getUID(), 'avatar', 'version',
			(int) $this->config->getUserValue($this->user->getUID(), 'avatar', 'version', 0) + 1);

		foreach ($avatars as $avatar) {
			$avatar->delete();
		}
		$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'true');
		$this->user->triggerChange('avatar', '');
	}

	/**
	 * Get the extension of the avatar. If there is no avatar throw Exception
	 *
	 * @return string
	 * @throws NotFoundException
	 */
	private function getExtension() {
		if ($this->folder->fileExists('avatar.jpg')) {
			return 'jpg';
		} elseif ($this->folder->fileExists('avatar.png')) {
			return 'png';
		}
		throw new NotFoundException;
	}

	/**
	 * Returns the avatar for an user.
	 *
	 * If there is no avatar file yet, one is generated.
	 *
	 * @param int $size
	 * @return ISimpleFile
	 * @throws NotFoundException
	 * @throws \OCP\Files\NotPermittedException
	 * @throws \OCP\PreConditionNotMetException
	 */
	public function getFile($size) {
		try {
			$ext = $this->getExtension();
		} catch (NotFoundException $e) {
			if (!$data = $this->generateAvatarFromSvg(1024)) {
				$data = $this->generateAvatar($this->getDisplayName(), 1024);
			}
			$avatar = $this->folder->newFile('avatar.png');
			$avatar->putContent($data);
			$ext = 'png';

			$this->folder->newFile('generated');
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', 'true');
		}

		if ($size === -1) {
			$path = 'avatar.' . $ext;
		} else {
			$path = 'avatar.' . $size . '.' . $ext;
		}

		try {
			$file = $this->folder->getFile($path);
		} catch (NotFoundException $e) {
			if ($size <= 0) {
				throw new NotFoundException;
			}

			if ($this->folder->fileExists('generated')) {
				if (!$data = $this->generateAvatarFromSvg($size)) {
					$data = $this->generateAvatar($this->getDisplayName(), $size);
				}

			} else {
				$avatar = new OC_Image();
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
			$generated = $this->folder->fileExists('generated') ? 'true' : 'false';
			$this->config->setUserValue($this->user->getUID(), 'avatar', 'generated', $generated);
		}

		return $file;
	}

	/**
	 * Returns the user display name.
	 *
	 * @return string
	 */
	public function getDisplayName(): string {
		return $this->user->getDisplayName();
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
	public function userChanged($feature, $oldValue, $newValue) {
		// We only change the avatar on display name changes
		if ($feature !== 'displayName') {
			return;
		}

		// If the avatar is not generated (so an uploaded image) we skip this
		if (!$this->folder->fileExists('generated')) {
			return;
		}

		$this->remove();
	}
}
