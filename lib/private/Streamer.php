<?php

/**
 * SPDX-FileCopyrightText: 2016-2024 Nextcloud GmbH and Nextcloud contributors
 * SPDX-FileCopyrightText: 2016 ownCloud, Inc.
 * SPDX-License-Identifier: AGPL-3.0-only
 */
namespace OC;

use OC\Files\Filesystem;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\InvalidPathException;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use OCP\Files\NotPermittedException;
use OCP\IRequest;
use ownCloud\TarStreamer\TarStreamer;
use Psr\Log\LoggerInterface;
use ZipStreamer\ZipStreamer;

class Streamer {
	// array of regexp. Matching user agents will get tar instead of zip
	private const UA_PREFERS_TAR = [ '/macintosh|mac os x/i' ];

	// streamer instance
	private $streamerInstance;

	public static function isUserAgentPreferTar(IRequest $request): bool {
		return $request->isUserAgent(self::UA_PREFERS_TAR);
	}

	/**
	 * Streamer constructor.
	 *
	 * @param bool|IRequest $preferTar If true a tar stream is used.
	 *                                 For legacy reasons also a IRequest can be passed to detect this preference by user agent,
	 *                                 please migrate to `Streamer::isUserAgentPreferTar()` instead.
	 * @param int|float $size The size of the files in bytes
	 * @param int $numberOfFiles The number of files (and directories) that will
	 *                           be included in the streamed file
	 */
	public function __construct(IRequest|bool $preferTar, int|float $size, int $numberOfFiles) {
		if ($preferTar instanceof IRequest) {
			$preferTar = self::isUserAgentPreferTar($preferTar);
		}

		/**
		 * zip32 constraints for a basic (without compression, volumes nor
		 * encryption) zip file according to the Zip specification:
		 * - No file size is larger than 4 bytes (file size < 4294967296); see
		 *   4.4.9 uncompressed size
		 * - The size of all files plus their local headers is not larger than
		 *   4 bytes; see 4.4.16 relative offset of local header and 4.4.24
		 *   offset of start of central directory with respect to the starting
		 *   disk number
		 * - The total number of entries (files and directories) in the zip file
		 *   is not larger than 2 bytes (number of entries < 65536); see 4.4.22
		 *   total number of entries in the central dir
		 * - The size of the central directory is not larger than 4 bytes; see
		 *   4.4.23 size of the central directory
		 *
		 * Due to all that, zip32 is used if the size is below 4GB and there are
		 * less than 65536 files; the margin between 4*1000^3 and 4*1024^3
		 * should give enough room for the extra zip metadata. Technically, it
		 * would still be possible to create an invalid zip32 file (for example,
		 * a zip file from files smaller than 4GB with a central directory
		 * larger than 4GiB), but it should not happen in the real world.
		 *
		 * We also have to check for a size above 0. As negative sizes could be
		 * from not fully scanned external storage. And then things fall apart
		 * if somebody tries to package to much.
		 */
		if ($preferTar) {
			// If TAR ball is preferred use it
			$this->streamerInstance = new TarStreamer();
		} elseif ($size > 0 && $size < 4 * 1000 * 1000 * 1000 && $numberOfFiles < 65536) {
			$this->streamerInstance = new ZipStreamer(['zip64' => false]);
		} else {
			$this->streamerInstance = new ZipStreamer(['zip64' => true]);
		}
	}

	/**
	 * Send HTTP headers
	 * @param string $name
	 */
	public function sendHeaders($name) {
		header('X-Accel-Buffering: no');
		$extension = $this->streamerInstance instanceof ZipStreamer ? '.zip' : '.tar';
		$fullName = $name . $extension;
		$this->streamerInstance->sendHeaders($fullName);
	}

	/**
	 * Stream directory recursively
	 *
	 * @param string $dir Directory path relative to root of current user
	 * @throws NotFoundException
	 * @throws NotPermittedException
	 * @throws InvalidPathException
	 */
	public function addDirRecursive(string $dir, string $internalDir = ''): void {
		$dirname = basename($dir);
		$rootDir = $internalDir . $dirname;
		if (!empty($rootDir)) {
			$this->streamerInstance->addEmptyDir($rootDir);
		}
		$internalDir .= $dirname . '/';
		// prevent absolute dirs
		$internalDir = ltrim($internalDir, '/');

		$userFolder = \OC::$server->get(IRootFolder::class)->get(Filesystem::getRoot());
		/** @var Folder $dirNode */
		$dirNode = $userFolder->get($dir);
		$files = $dirNode->getDirectoryListing();

		/** @var LoggerInterface $logger */
		$logger = \OC::$server->query(LoggerInterface::class);
		foreach ($files as $file) {
			if ($file instanceof File) {
				try {
					$fh = $file->fopen('r');
					if ($fh === false) {
						$logger->error('Unable to open file for stream: ' . print_r($file, true));
						continue;
					}
				} catch (NotPermittedException $e) {
					continue;
				}
				$this->addFileFromStream(
					$fh,
					$internalDir . $file->getName(),
					$file->getSize(),
					$file->getMTime()
				);
				fclose($fh);
			} elseif ($file instanceof Folder) {
				if ($file->isReadable()) {
					$this->addDirRecursive($dir . '/' . $file->getName(), $internalDir);
				}
			}
		}
	}

	/**
	 * Add a file to the archive at the specified location and file name.
	 *
	 * @param resource $stream Stream to read data from
	 * @param string $internalName Filepath and name to be used in the archive.
	 * @param int|float $size Filesize
	 * @param int|false $time File mtime as int, or false
	 * @return bool $success
	 */
	public function addFileFromStream($stream, string $internalName, int|float $size, $time): bool {
		$options = [];
		if ($time) {
			$options = [
				'timestamp' => $time
			];
		}

		if ($this->streamerInstance instanceof ZipStreamer) {
			return $this->streamerInstance->addFileFromStream($stream, $internalName, $options);
		} else {
			return $this->streamerInstance->addFileFromStream($stream, $internalName, $size, $options);
		}
	}

	/**
	 * Add an empty directory entry to the archive.
	 *
	 * @param string $dirName Directory Path and name to be added to the archive.
	 * @return bool $success
	 */
	public function addEmptyDir($dirName) {
		return $this->streamerInstance->addEmptyDir($dirName);
	}

	/**
	 * Close the archive.
	 * A closed archive can no longer have new files added to it. After
	 * closing, the file is completely written to the output stream.
	 * @return bool $success
	 */
	public function finalize() {
		return $this->streamerInstance->finalize();
	}
}
