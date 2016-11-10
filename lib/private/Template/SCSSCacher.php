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
use OCP\Files\NotFoundException;
use OCP\IURLGenerator;

class SCSSCacher {

	protected $root;
	protected $folder;
	protected $file;
	protected $fileNameSCSS;
	protected $fileNameCSS;
	protected $fileLoc;
	protected $rootCssLoc;

	/** @var \OCP\ILogger */
	protected $logger;
	/** @var \OCP\Files\IAppData */
	protected $appData;
	/** @var \OCP\IURLGenerator */
	protected $urlGenerator;
	/** @var \OCP\Files\IConfig */
	protected $systemConfig;

	/**
	 * @param \OCP\ILogger $logger
	 * @param string $root
	 * @param string $file
	 * @param \OCP\Files\IAppData $appData
	 */
	public function __construct(\OCP\ILogger $logger, $root, $file, \OCP\Files\IAppData $appData, \OCP\IURLGenerator $urlGenerator, $systemConfig) {
		$this->logger = $logger;
		$this->appData = $appData;
		$this->urlGenerator = $urlGenerator;
		$this->systemConfig = $systemConfig;

		$this->root = $root;
		$this->file = explode('/', $root.'/'.$file);

		/* filenames */
		$this->fileNameSCSS = array_pop($this->file);
		$this->fileNameCSS = str_replace('.scss', '.css', $this->fileNameSCSS);

		$this->fileLoc = implode('/', $this->file);

		// base uri to css file
		$this->rootCssLoc = explode('/', $file);
		array_pop($this->rootCssLoc);
		$this->rootCssLoc = implode('/', $this->rootCssLoc);

		try {
			$this->folder = $this->appData->getFolder('css');
		} catch(NotFoundException $e) {
			// creating css appdata folder
			$this->folder = $this->appData->newFolder('css');
		}
	}

	/**
	 * Process the caching process if needed
	 * @return boolean
	 */
	public function process() {

		if($this->is_cached()) {
			return true;
		} else {
			return $this->cache();
		}
		return false;
	}

	/**
	 * Check if the file is cached or not
	 * @return boolean
	 */
	private function is_cached() {
		try{
			$cachedfile = $this->folder->getFile($this->fileNameCSS);
			if( $cachedfile->getMTime() > filemtime($this->fileLoc.'/'.$this->fileNameSCSS)
				&& $cachedfile->getSize() > 0 ) {
				return true;
			}
		} catch(NotFoundException $e) {
			return false;
		}
        return false;
	}

	/**
	 * Cache the file with AppData
	 * @return boolean
	 */
	private function cache() {
		$scss = new Compiler();
		$scss->setImportPaths($this->fileLoc);
		if($this->systemConfig->getValue('debug')) {
			// Debug mode
			$scss->setFormatter('Leafo\ScssPhp\Formatter\Expanded');
			$scss->setLineNumberStyle(Compiler::LINE_COMMENTS);
		} else {
			// Compression
			$scss->setFormatter('Leafo\ScssPhp\Formatter\Crunched');
		}

		try {
			$cachedfile = $this->folder->getFile($this->fileNameCSS);
		} catch(NotFoundException $e) {
			$cachedfile = $this->folder->newFile($this->fileNameCSS);
		}

		// Compile
		try {
			$compiledScss = $scss->compile('@import "'.$this->fileNameSCSS.'";');
		} catch(ParserException $e) {
			$this->logger->error($e, ['app' => 'core']);
			return false;
		}

		try {
			$cachedfile->putContent($this->rebaseUrls($compiledScss));
			$this->logger->debug($this->rootCssLoc.'/'.$this->fileNameSCSS.' compiled and successfully cached', ['app' => 'core']);
			return true;
		} catch(NotFoundException $e) {
			return false;
		}
		return false;
	}

	/**
	 * Add the correct uri prefix to make uri valid again
	 * @param string $css
	 * @return string
	 */
	private function rebaseUrls($css) {
		$re = '/url\([\'"]([\.\w?=\/-]*)[\'"]\)/x';
		$subst = 'url(\'../../../'.$this->rootCssLoc.'/$1\')';
		return preg_replace($re, $subst, $css);
	}

	/**
	 * Return the cached css file uri
	 * @return string
	 */
	public function getCachedSCSS() {
		return substr($this->urlGenerator->linkToRoute('core.Css.getCss', array('fileName' => $this->fileNameCSS)), 1);
	}
}
