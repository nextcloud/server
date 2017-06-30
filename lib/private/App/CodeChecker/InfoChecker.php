<?php
/**
 * @copyright Copyright (c) 2016, ownCloud, Inc.
 *
 * @author Joas Schilling <coding@schilljs.com>
 * @author Morris Jobke <hey@morrisjobke.de>
 *
 * @license AGPL-3.0
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
 * along with this program.  If not, see <http://www.gnu.org/licenses/>
 *
 */

namespace OC\App\CodeChecker;

use OC\App\InfoParser;
use OC\Hooks\BasicEmitter;

class InfoChecker extends BasicEmitter {

	/** @var InfoParser */
	private $infoParser;

	private $mandatoryFields = [
		'author',
		'description',
		'dependencies',
		'id',
		'licence',
		'name',
		'version',
	];
	private $optionalFields = [
		'bugs',
		'category',
		'default_enable',
		'documentation',
		'namespace',
		'ocsid',
		'public',
		'remote',
		'repository',
		'types',
		'website',
	];
	private $deprecatedFields = [
		'info',
		'require',
		'requiremax',
		'requiremin',
		'shipped',
		'standalone',
	];

	public function __construct(InfoParser $infoParser) {
		$this->infoParser = $infoParser;
	}

	/**
	 * @param string $appId
	 * @return array
	 */
	public function analyse($appId) {
		$appPath = \OC_App::getAppPath($appId);
		if ($appPath === false) {
			throw new \RuntimeException("No app with given id <$appId> known.");
		}

		$errors = [];

		$info = $this->infoParser->parse($appPath . '/appinfo/info.xml');

		if (!isset($info['dependencies']['nextcloud']['@attributes']['min-version'])) {
			$errors[] = [
				'type' => 'missingRequirement',
				'field' => 'min',
			];
			$this->emit('InfoChecker', 'missingRequirement', ['min']);
		}

		if (!isset($info['dependencies']['nextcloud']['@attributes']['max-version'])) {
			$errors[] = [
				'type' => 'missingRequirement',
				'field' => 'max',
			];
			$this->emit('InfoChecker', 'missingRequirement', ['max']);
		}

		foreach ($info as $key => $value) {
			if(is_array($value)) {
				$value = json_encode($value);
			}
			if (in_array($key, $this->mandatoryFields)) {
				$this->emit('InfoChecker', 'mandatoryFieldFound', [$key, $value]);
				continue;
			}

			if (in_array($key, $this->optionalFields)) {
				$this->emit('InfoChecker', 'optionalFieldFound', [$key, $value]);
				continue;
			}

			if (in_array($key, $this->deprecatedFields)) {
				// skip empty arrays - empty arrays for remote and public are always added
				if($value === '[]' && in_array($key, ['public', 'remote', 'info'])) {
					continue;
				}
				$this->emit('InfoChecker', 'deprecatedFieldFound', [$key, $value]);
				continue;
			}

			$this->emit('InfoChecker', 'unusedFieldFound', [$key, $value]);
		}

		foreach ($this->mandatoryFields as $key) {
			if(!isset($info[$key])) {
				$this->emit('InfoChecker', 'mandatoryFieldMissing', [$key]);
				$errors[] = [
					'type' => 'mandatoryFieldMissing',
					'field' => $key,
				];
			}
		}

		$versionFile = $appPath . '/appinfo/version';
		if (is_file($versionFile)) {
			$version = trim(file_get_contents($versionFile));
			$this->emit('InfoChecker', 'migrateVersion', [$version]);
		}

		return $errors;
	}
}
