<?php
/**
 * @copyright Copyright (c) 2023 John Molakvoæ <skjnldsv@protonmail.com>
 *
 * @author John Molakvoæ <skjnldsv@protonmail.com>
 *
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
namespace OC\Preview;

use OCA\Theming\ThemingDefaults;
use OCP\App\IAppManager;
use OCP\Files\IMimeTypeDetector;
use OCP\IConfig;
use OCP\IURLGenerator;
use OCP\Preview\IMimeIconProvider;

class MimeIconProvider implements IMimeIconProvider {
	public function __construct(
		protected IMimeTypeDetector $mimetypeDetector,
		protected IConfig $config,
		protected IURLGenerator $urlGenerator,
		protected IAppManager $appManager,
		protected ThemingDefaults $themingDefaults,
	) {
	}

	public function getMimeIconUrl(string $mime): null|string {
		if (!$mime) {
			return null;
		}

		// Fetch all the aliases
		$aliases = $this->mimetypeDetector->getAllAliases();

		// Remove comments
		$aliases = array_filter($aliases, static function ($key) {
			return !($key === '' || $key[0] === '_');
		}, ARRAY_FILTER_USE_KEY);

		// Map all the aliases recursively
		foreach ($aliases as $alias => $value) {
			if ($alias === $mime) {
				$mime = $value;
			}
		}

		$fileName = str_replace('/', '-', $mime);
		if ($url = $this->searchfileName($fileName)) {
			return $url;
		}

		$mimeType = explode('/', $mime)[0];
		if ($url = $this->searchfileName($mimeType)) {
			return $url;
		}

		return null;
	}
	
	private function searchfileName(string $fileName): null|string {
		// If the file exists in the current enabled legacy
		// custom theme, let's return it
		$theme = $this->config->getSystemValue('theme', '');
		if (!empty($theme)) {
			$path = "/themes/$theme/core/img/filetypes/$fileName.svg";
			if (file_exists(\OC::$SERVERROOT . $path)) {
				return $this->urlGenerator->getAbsoluteURL($path);
			}
		}
		
		// Previously, we used to pass this through Theming
		// But it was only used to colour icons containing
		// 0082c9. Since with vue we moved to inline svg icons,
		// we can just use the default core icons.

		// Finally, if the file exists in core, let's return it
		$path = "/core/img/filetypes/$fileName.svg";
		if (file_exists(\OC::$SERVERROOT . $path)) {
			return $this->urlGenerator->getAbsoluteURL($path);
		}

		return null;
	}
}
