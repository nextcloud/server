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

class SCSSCacher {

	protected $root;
	protected $file;
	protected $fileName;
	protected $fileLoc;
	protected $fileCache;
	protected $rootCssLoc;

	/** Cache folder from serverroot */
	private $scssCache = "assets";


	/** @var \OCP\ILogger */
	protected $logger;

	/**
	 * @param \OCP\ILogger $logger
	 * @param string $root
	 * @param string $file
	 */
	public function __construct(\OCP\ILogger $logger, $root, $file) {
		$this->logger = $logger;
		$this->root = $root;
		$this->file = explode('/', $root.'/'.$file);

		$this->fileName = array_pop($this->file);
		$this->fileLoc = implode('/', $this->file);
		$this->fileCache = str_replace('.scss', '.css', $this->scssCache.'/'.$this->fileName);

		// base uri to css file
		$this->rootCssLoc = explode('/', $file);
		array_pop($this->rootCssLoc);
		$this->rootCssLoc = implode('/', $this->rootCssLoc);
	}

	public function process() {

		if($this->is_cached($this->root.'/'.$this->fileCache, $this->fileLoc.'/'.$this->fileName)) {
			return true;
		} else {
			return $this->cache();
		}
		return false;
	}

	private function is_cached($in, $out) {
		if (! is_file($out) || filemtime($in) > filemtime($out)) {
            return true;
        }
        return false;
	}

	private function cache() {
		$scss = new Compiler();
		$scss->setImportPaths($this->fileLoc);

		if(\OC::$server->getSystemConfig()->getValue('debug')) {
			// Debug mode
			$scss->setFormatter('Leafo\ScssPhp\Formatter\Expanded');
			$scss->setLineNumberStyle(Compiler::LINE_COMMENTS);
		} else {
			$scss->setFormatter('Leafo\ScssPhp\Formatter\Crunched');
		}

		try {
			$compiledScss = $scss->compile('@import "'.$this->fileName.'";');
		} catch(ParserException $e) {
			$this->logger->error($e, ['app' => 'SCSSPHP']);
			return false;
		}

		if(file_put_contents($this->fileCache, $this->rebaseUrls($compiledScss))) {
			$this->logger->debug($root.'/'.$file.' compiled and successfully cached', ['app' => 'SCSSPHP']);
			return true;
		}
		return false;
	}

	private function rebaseUrls($css) {
		$re = '/url\([\'"](.*)[\'"]\)/x';
		$subst = 'url(\'../'.$this->rootCssLoc.'/$1\')';
		return preg_replace($re, $subst, $css);
	}

	public function getCachedSCSS() {
		return $this->fileCache;
	}
}
