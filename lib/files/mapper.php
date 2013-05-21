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
	 * @param $recursive
	 * @return void
	 */
	public function removePath($path, $isLogicPath, $recursive) {
		if ($recursive) {
			$path=$path.'%';
		}

		if ($isLogicPath) {
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*file_map` WHERE `logic_path` LIKE ?');
			$query->execute(array($path));
		} else {
			$query = \OC_DB::prepare('DELETE FROM `*PREFIX*file_map` WHERE `physic_path` LIKE ?');
			$query->execute(array($path));
		}
	}

	/**
	 * @param $path1
	 * @param $path2
	 * @throws \Exception
	 */
	public function copy($path1, $path2)
	{
		$path1 = $this->stripLast($path1);
		$path2 = $this->stripLast($path2);
		$physicPath1 = $this->logicToPhysical($path1, true);
		$physicPath2 = $this->logicToPhysical($path2, true);

		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*file_map` WHERE `logic_path` LIKE ?');
		$result = $query->execute(array($path1.'%'));
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
					$updateQuery->execute(array($newLogic, md5($newLogic), $newPhysic, md5($newPhysic), $currentLogic));
				} catch (\Exception $e) {
					error_log('Mapper::Copy failed '.$currentLogic.' -> '.$newLogic.'\n'.$e);
					throw $e;
				}
			}
		}
	}

	/**
	 * @param $path
	 * @param $root
	 * @return bool|string
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

	private function stripLast($path) {
		if (substr($path, -1) == '/') {
			$path = substr_replace($path, '', -1);
		}
		return $path;
	}

	private function resolveLogicPath($logicPath) {
		$logicPath = $this->stripLast($logicPath);
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*file_map` WHERE `logic_path_hash` = ?');
		$result = $query->execute(array(md5($logicPath)));
		$result = $result->fetchRow();
		if ($result === false) {
			return null;
		}

		return $result['physic_path'];
	}

	private function resolvePhysicalPath($physicalPath) {
		$physicalPath = $this->stripLast($physicalPath);
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*file_map` WHERE `physic_path_hash` = ?');
		$result = $query->execute(array(md5($physicalPath)));
		$result = $result->fetchRow();

		return $result['logic_path'];
	}

	private function create($logicPath, $store) {
		$logicPath = $this->stripLast($logicPath);
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
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*file_map`(`logic_path`, `physic_path`, `logic_path_hash`, `physic_path_hash`) VALUES(?, ?, ?, ?)');
		$query->execute(array($logicPath, $physicalPath, md5($logicPath), md5($physicalPath)));
	}

	public function slugifyPath($path, $index=null) {
		$path = $this->stripRootFolder($path, $this->unchangedPhysicalRoot);

		$pathElements = explode('/', $path);
		$sluggedElements = array();

		// rip off the extension ext from last element
		$last= end($pathElements);
		$parts = pathinfo($last);
		$filename = $parts['filename'];
		array_pop($pathElements);
		array_push($pathElements, $filename);

		foreach ($pathElements as $pathElement) {
			// remove empty elements
			if (empty($pathElement)) {
				continue;
			}

			$sluggedElements[] = self::slugify($pathElement);
		}

		// apply index to file name
		if ($index !== null) {
			$last= array_pop($sluggedElements);
			array_push($sluggedElements, $last.'-'.$index);
		}

		// add back the extension
		if (isset($parts['extension'])) {
			$last= array_pop($sluggedElements);
			array_push($sluggedElements, $last.'.'.$parts['extension']);
		}

		$sluggedPath = $this->unchangedPhysicalRoot.implode('/', $sluggedElements);
		return $this->stripLast($sluggedPath);
	}

	/**
	 * Modifies a string to remove all non ASCII characters and spaces.
	 *
	 * @param string $text
	 * @return string
	 */
	private function slugify($text)
	{
		// replace non letter or digits by -
		$text = preg_replace('~[^\\pL\d]+~u', '-', $text);

		// trim
		$text = trim($text, '-');

		// transliterate
		if (function_exists('iconv')) {
			$text = iconv('utf-8', 'us-ascii//TRANSLIT//IGNORE', $text);
		}

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text)) {
			return uniqid();
		}

		return $text;
	}
}
