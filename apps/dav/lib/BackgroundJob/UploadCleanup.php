<?php

declare(strict_types=1);

/**
 * @copyright Copyright (c) 2018, Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @author Christoph Wurst <christoph@winzerhof-wurst.at>
 * @author Julius Härtl <jus@bitgrid.net>
 * @author Roeland Jago Douma <roeland@famdouma.nl>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 *
 */
namespace OCA\DAV\BackgroundJob;

use OC\User\NoUserException;
use OCP\AppFramework\Utility\ITimeFactory;
use OCP\BackgroundJob\IJob;
use OCP\BackgroundJob\IJobList;
use OCP\BackgroundJob\TimedJob;
use OCP\Files\Node;
use OCP\Files\File;
use OCP\Files\Folder;
use OCP\Files\IRootFolder;
use OCP\Files\NotFoundException;
use Psr\Log\LoggerInterface;

class UploadCleanup extends TimedJob {
	private IRootFolder $rootFolder;
	private IJobList $jobList;
	private LoggerInterface $logger;

	public function __construct(ITimeFactory $time, IRootFolder $rootFolder, IJobList $jobList, LoggerInterface $logger) {
		parent::__construct($time);
		$this->rootFolder = $rootFolder;
		$this->jobList = $jobList;
		$this->logger = $logger;

		// Run once a day
		$this->setInterval(60 * 60 * 24);
		$this->setTimeSensitivity(IJob::TIME_INSENSITIVE);
	}

	protected function run($argument) {
		$uid = $argument['uid'];
		$folder = $argument['folder'];

		try {
			$userFolder = $this->rootFolder->getUserFolder($uid);
			$userRoot = $userFolder->getParent();
			/** @var Folder $uploads */
			$uploads = $userRoot->get('uploads');
			$uploadFolder = $uploads->get($folder);
		} catch (NotFoundException | NoUserException $e) {
			$this->jobList->remove(self::class, $argument);
			return;
		}

		// Remove if all files have an mtime of more than a day
		$time = $this->time->getTime() - 60 * 60 * 24;

		if (!($uploadFolder instanceof Folder)) {
			$this->logger->error("Found a file inside the uploads folder. Uid: " . $uid . ' folder: ' . $folder);
			if ($uploadFolder->getMTime() < $time) {
				$uploadFolder->delete();
			}
			$this->jobList->remove(self::class, $argument);
			return;
		}

		/** @var File[] $files */
		$files = $uploadFolder->getDirectoryListing();

		// The folder has to be more than a day old
		$initial = $uploadFolder->getMTime() < $time;

		$expire = array_reduce($files, function (bool $carry, File $file) use ($time) {
			return $carry && $file->getMTime() < $time;
		}, $initial);

		if ($expire) {
			$uploadFolder->delete();
			$this->jobList->remove(self::class, $argument);
		}
	}
}
