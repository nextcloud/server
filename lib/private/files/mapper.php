<?php

namespace OC\Files;

/**
 * class Mapper is responsible to translate logical paths to physical paths and reverse
 */
class Mapper
{
	private $unchangedPhysicalRoot;

	public function __construct($rootDir) {
		$this->unchangedPhysicalRoot = $rootDir;
	}

	/**
	 * @param string $logicPath
	 * @param bool $create indicates if the generated physical name shall be stored in the database or not
	 * @return string the physical path
	 */
	public function logicToPhysical($logicPath, $create) {
		$physicalPath = $this->resolveLogicPath($logicPath);
		if ($physicalPath !== null) {
			return $physicalPath;
		}

		return $this->create($logicPath, $create);
	}

	/**
	 * @param string $physicalPath
	 * @return string
	 */
	public function physicalToLogic($physicalPath) {
		$logicPath = $this->resolvePhysicalPath($physicalPath);
		if ($logicPath !== null) {
			return $logicPath;
		}

		$this->insert($physicalPath, $physicalPath);
		return $physicalPath;
	}

	/**
	 * @param string $path
	 * @param bool $isLogicPath indicates if $path is logical or physical
	 * @param boolean $recursive
	 * @return void
	 */
	public function removePath($path, $isLogicPath, $recursive) {
		if ($recursive) {
			$path=$path.'%';
		}

		if ($isLogicPath) {
			\OC_DB::executeAudited('DELETE FROM `*PREFIX*file_map` WHERE `logic_path` LIKE ?', array($path));
		} else {
			\OC_DB::executeAudited('DELETE FROM `*PREFIX*file_map` WHERE `physic_path` LIKE ?', array($path));
		}
	}

	/**
	 * @param string $path1
	 * @param string $path2
	 * @throws \Exception
	 */
	public function copy($path1, $path2)
	{
		$path1 = $this->resolveRelativePath($path1);
		$path2 = $this->resolveRelativePath($path2);
		$physicPath1 = $this->logicToPhysical($path1, true);
		$physicPath2 = $this->logicToPhysical($path2, true);

		$sql = 'SELECT * FROM `*PREFIX*file_map` WHERE `logic_path` LIKE ?';
		$result = \OC_DB::executeAudited($sql, array($path1.'%'));
		$updateQuery = \OC_DB::prepare('UPDATE `*PREFIX*file_map`'
			.' SET `logic_path` = ?'
			.' , `logic_path_hash` = ?'
			.' , `physic_path` = ?'
			.' , `physic_path_hash` = ?'
			.' WHERE `logic_path` = ?');
		while( $row = $result->fetchRow()) {
			$currentLogic = $row['logic_path'];
			$currentPhysic = $row['physic_path'];
			$newLogic = $path2.$this->stripRootFolder($currentLogic, $path1);
			$newPhysic = $physicPath2.$this->stripRootFolder($currentPhysic, $physicPath1);
			if ($path1 !== $currentLogic) {
				try {
					\OC_DB::executeAudited($updateQuery, array($newLogic, md5($newLogic), $newPhysic, md5($newPhysic),
						$currentLogic));
				} catch (\Exception $e) {
					error_log('Mapper::Copy failed '.$currentLogic.' -> '.$newLogic.'\n'.$e);
					throw $e;
				}
			}
		}
	}

	/**
	 * @param string $path
	 * @param string $root
	 * @return false|string
	 */
	public function stripRootFolder($path, $root) {
		if (strpos($path, $root) !== 0) {
			// throw exception ???
			return false;
		}
		if (strlen($path) > strlen($root)) {
			return substr($path, strlen($root));
		}

		return '';
	}

	/**
	 * @param string $logicPath
	 * @return null
	 * @throws \OC\DatabaseException
	 */
	private function resolveLogicPath($logicPath) {
		$logicPath = $this->resolveRelativePath($logicPath);
		$sql = 'SELECT * FROM `*PREFIX*file_map` WHERE `logic_path_hash` = ?';
		$result = \OC_DB::executeAudited($sql, array(md5($logicPath)));
		$result = $result->fetchRow();
		if ($result === false) {
			return null;
		}

		return $result['physic_path'];
	}

	private function resolvePhysicalPath($physicalPath) {
		$physicalPath = $this->resolveRelativePath($physicalPath);
		$sql = \OC_DB::prepare('SELECT * FROM `*PREFIX*file_map` WHERE `physic_path_hash` = ?');
		$result = \OC_DB::executeAudited($sql, array(md5($physicalPath)));
		$result = $result->fetchRow();

		return $result['logic_path'];
	}

	private function resolveRelativePath($path) {
		$explodedPath = explode('/', $path);
		$pathArray = array();
		foreach ($explodedPath as $pathElement) {
			if (empty($pathElement) || ($pathElement == '.')) {
				continue;
			} elseif ($pathElement == '..') {
				if (count($pathArray) == 0) {
					return false;
				}
				array_pop($pathArray);
			} else {
				array_push($pathArray, $pathElement);
			}
		}
		if (substr($path, 0, 1) == '/') {
			$path = '/';
		} else {
			$path = '';
		}
		return $path.implode('/', $pathArray);
	}

	/**
	 * @param string $logicPath
	 * @param bool $store
	 * @return string
	 */
	private function create($logicPath, $store) {
		$logicPath = $this->resolveRelativePath($logicPath);
		$index = 0;

		// create the slugified path
		$physicalPath = $this->slugifyPath($logicPath);

		// detect duplicates
		while ($this->resolvePhysicalPath($physicalPath) !== null) {
			$physicalPath = $this->slugifyPath($logicPath, $index++);
		}

		// insert the new path mapping if requested
		if ($store) {
			$this->insert($logicPath, $physicalPath);
		}

		return $physicalPath;
	}

	private function insert($logicPath, $physicalPath) {
		$sql = 'INSERT INTO `*PREFIX*file_map` (`logic_path`, `physic_path`, `logic_path_hash`, `physic_path_hash`)
				VALUES (?, ?, ?, ?)';
		\OC_DB::executeAudited($sql, array($logicPath, $physicalPath, md5($logicPath), md5($physicalPath)));
	}

	/**
	 * @param string $path
	 * @param int $index
	 * @return string
	 */
	public function slugifyPath($path, $index = null) {
		$path = $this->stripRootFolder($path, $this->unchangedPhysicalRoot);

		$pathElements = explode('/', $path);
		$sluggedElements = array();

		foreach ($pathElements as $pathElement) {
			// remove empty elements
			if (empty($pathElement)) {
				continue;
			}

			$sluggedElements[] = $this->slugify($pathElement);
		}

		// apply index to file name
		if ($index !== null) {
			$last = array_pop($sluggedElements);
			
			// if filename contains periods - add index number before last period
			if (preg_match('~\.[^\.]+$~i', $last, $extension)) {
				array_push($sluggedElements, substr($last, 0, -(strlen($extension[0]))) . '-' . $index . $extension[0]);
			} else {
				// if filename doesn't contain periods add index ofter the last char
				array_push($sluggedElements, $last . '-' . $index);
			}
		}

		$sluggedPath = $this->unchangedPhysicalRoot.implode('/', $sluggedElements);
		return $this->resolveRelativePath($sluggedPath);
	}

	/**
	 * Modifies a string to remove all non ASCII characters and spaces.
	 *
	 * @param string $text
	 * @return string
	 */
	private function slugify($text) {
		$originalText = $text;
		// replace non letter or digits or dots by -
		$text = preg_replace('~[^\\pL\d\.]+~u', '-', $text);

		// trim
		$text = trim($text, '-');

		// transliterate
		if (function_exists('iconv')) {
			$text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text);
		}

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w\.]+~', '', $text);
		
		// trim ending dots (for security reasons and win compatibility)
		$text = preg_replace('~\.+$~', '', $text);

		if (empty($text) || \OC\Files\Filesystem::isFileBlacklisted($text)) {
			/**
			 * Item slug would be empty. Previously we used uniqid() here.
			 * However this means that the behaviour is not reproducible, so
			 * when uploading files into a "empty" folder, the folders name is
			 * different.
			 *
			 * The other case is, that the slugified name would be a blacklisted
			 * filename. In this case we just use the same workaround by
			 * returning the secure md5 hash of the original name.
			 *
			 *
			 * If there would be a md5() hash collision, the deduplicate check
			 * will spot this and append an index later, so this should not be
			 * a problem.
			 */
			return md5($originalText);
		}

		return $text;
	}
}
