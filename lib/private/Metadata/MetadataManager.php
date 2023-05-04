<?php
/**
 * @copyright Copyright 2022 Carl Schwan <carl@carlschwan.eu>
 * @license AGPL-3.0-or-later
 *
 * This code is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License, version 3,
 * as published by the Free Software Foundation.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License, version 3,
 * along with this program. If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\Metadata;

use OC\Metadata\Provider\ExifProvider;
use OCP\Files\File;

class MetadataManager implements IMetadataManager {
	/** @var array<string, IMetadataProvider> */
	private array $providers;
	private array $providerClasses;
	private FileMetadataMapper $fileMetadataMapper;

	public function __construct(
		FileMetadataMapper $fileMetadataMapper
	) {
		$this->providers = [];
		$this->providerClasses = [];
		$this->fileMetadataMapper = $fileMetadataMapper;

		// TODO move to another place, where?
		$this->registerProvider(ExifProvider::class);
	}

	/**
	 * @param class-string<IMetadataProvider> $className
	 */
	public function registerProvider(string $className):void {
		if (in_array($className, $this->providerClasses)) {
			return;
		}

		if (call_user_func([$className, 'isAvailable'])) {
			$this->providers[call_user_func([$className, 'getMimetypesSupported'])] = \OC::$server->get($className);
		}
	}

	public function generateMetadata(File $file, bool $checkExisting = false): void {
		$existingMetadataGroups = [];

		if ($checkExisting) {
			$existingMetadata = $this->fileMetadataMapper->findForFile($file->getId());
			foreach ($existingMetadata as $metadata) {
				$existingMetadataGroups[] = $metadata->getGroupName();
			}
		}

		foreach ($this->providers as $supportedMimetype => $provider) {
			if (preg_match($supportedMimetype, $file->getMimeType())) {
				if (count(array_diff($provider::groupsProvided(), $existingMetadataGroups)) > 0) {
					$metaDataGroup = $provider->execute($file);
					foreach ($metaDataGroup as $group => $metadata) {
						$this->fileMetadataMapper->insertOrUpdate($metadata);
					}
				}
			}
		}
	}

	public function clearMetadata(int $fileId): void {
		$this->fileMetadataMapper->clear($fileId);
	}

	/**
	 * @return array<int, FileMetadata>
	 */
	public function fetchMetadataFor(string $group, array $fileIds): array {
		return $this->fileMetadataMapper->findForGroupForFiles($fileIds, $group);
	}

	public function getCapabilities(): array {
		$capabilities = [];
		foreach ($this->providers as $supportedMimetype => $provider) {
			foreach ($provider::groupsProvided() as $group) {
				if (isset($capabilities[$group])) {
					$capabilities[$group][] = $supportedMimetype;
				}
				$capabilities[$group] = [$supportedMimetype];
			}
		}
		return $capabilities;
	}
}
