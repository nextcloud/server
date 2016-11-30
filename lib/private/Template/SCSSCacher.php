<?php
/**
 * @copyright Copyright (c) 2016, John Molakvoæ (skjnldsv@protonmail.com)
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OC\Template;

use Leafo\ScssPhp\Compiler;
use Leafo\ScssPhp\Exception\ParserException;
use Leafo\ScssPhp\Formatter\Crunched;
use Leafo\ScssPhp\Formatter\Expanded;
use OC\SystemConfig;
use OCP\Files\IAppData;
use OCP\Files\NotFoundException;
use OCP\Files\SimpleFS\ISimpleFolder;
use OCP\ILogger;
use OCP\IURLGenerator;

class SCSSCacher {

	/** @var ILogger */
	protected $logger;

	/** @var IAppData */
	protected $appData;

	/** @var IURLGenerator */
	protected $urlGenerator;

	/** @var SystemConfig */
	protected $systemConfig;

	/**
	 * @param ILogger $logger
	 * @param IAppData $appData
	 * @param IURLGenerator $urlGenerator
	 * @param SystemConfig $systemConfig
	 */
	public function __construct(ILogger $logger, IAppData $appData, IURLGenerator $urlGenerator, SystemConfig $systemConfig) {
		$this->logger = $logger;
		$this->appData = $appData;
		$this->urlGenerator = $urlGenerator;
		$this->systemConfig = $systemConfig;
	}

	/**
	 * Process the caching process if needed
	 * @param string $root Root path to the nextcloud installation
	 * @param string $file
	 * @return boolean
	 */
	public function process($root, $file) {
		$path = explode('/', $root . '/' . $file);

		$fileNameSCSS = array_pop($path);
		$fileNameCSS = str_replace('.scss', '.css', $fileNameSCSS);

		$path = implode('/', $path);

		$webDir = explode('/', $file);
		array_pop($webDir);
		$webDir = implode('/', $webDir);

		try {
			$folder = $this->appData->getFolder('core');
		} catch(NotFoundException $e) {
			// creating css appdata folder
			$folder = $this->appData->newFolder('core');
		}

		if($this->is_cached($fileNameCSS, $fileNameSCSS, $folder, $path)) {
			return true;
		} else {
			return $this->cache($path, $fileNameCSS, $fileNameSCSS, $folder, $webDir);
		}
	}

	/**
	 * Check if the file is cached or not
	 * @param string $fileNameCSS
	 * @param string $fileNameSCSS
	 * @param ISimpleFolder $folder
	 * @param string $path
	 * @return boolean
	 */
	private function is_cached($fileNameCSS, $fileNameSCSS, ISimpleFolder $folder, $path) {
		try{
			$cachedFile = $folder->getFile($fileNameCSS);
			if( $cachedFile->getMTime() > filemtime($this->fileLoc.'/'.$fileNameSCSS)
				&& $cachedFile->getSize() > 0 ) {
				return true;
			}
		} catch(NotFoundException $e) {
			return false;
		}
		return false;
	}

	/**
	 * Cache the file with AppData
	 * @param string $path
	 * @param string $fileNameCSS
	 * @param string $fileNameSCSS
	 * @param ISimpleFolder $folder
	 * @param string $webDir
	 * @return boolean
	 */
	private function cache($path, $fileNameCSS, $fileNameSCSS, ISimpleFolder $folder, $webDir) {
		$scss = new Compiler();
		$scss->setImportPaths($path);
		if($this->systemConfig->getValue('debug')) {
			// Debug mode
			$scss->setFormatter(Expanded::class);
			$scss->setLineNumberStyle(Compiler::LINE_COMMENTS);
		} else {
			// Compression
			$scss->setFormatter(Crunched::class);
		}

		try {
			$cachedfile = $folder->getFile($fileNameCSS);
		} catch(NotFoundException $e) {
			$cachedfile = $folder->newFile($fileNameCSS);
		}

		// Compile
		try {
			$compiledScss = $scss->compile('@import "'.$fileNameSCSS.'";');
		} catch(ParserException $e) {
			$this->logger->error($e, ['app' => 'core']);
			return false;
		}

		try {
			$cachedfile->putContent($this->rebaseUrls($compiledScss, $webDir));
			$this->logger->debug($webDir.'/'.$fileNameSCSS.' compiled and successfully cached', ['app' => 'core']);
			return true;
		} catch(NotFoundException $e) {
			return false;
		}
	}

	/**
	 * Add the correct uri prefix to make uri valid again
	 * @param string $css
	 * @param string $webDir
	 * @return string
	 */
	private function rebaseUrls($css, $webDir) {
		$re = '/url\([\'"]([\.\w?=\/-]*)[\'"]\)/x';
		$subst = 'url(\'../../../'.$webDir.'/$1\')';
		return preg_replace($re, $subst, $css);
	}

	/**
	 * Return the cached css file uri
	 * @param string $appName the app name
	 * @param string $fileName
	 * @return string
	 */
	public function getCachedSCSS($appName, $fileName) {
		$fileName = array_pop(explode('/', $fileName));
		$fileName = str_replace('.scss', '.css', $fileName);

		return substr($this->urlGenerator->linkToRoute('core.Css.getCss', array('fileName' => $fileName, 'appName' => $appName)), 1);
	}
}
