<?php

namespace OC\Files;

/**
 * class Mapper is responsible to translate logical paths to physical paths and reverse
 */
class Mapper
{
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
	 * @return string|null
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
			.' AND `physic_path` = ?'
			.' WHERE `logic_path` = ?');
		while( $row = $result->fetchRow()) {
			$currentLogic = $row['logic_path'];
			$currentPhysic = $row['physic_path'];
			$newLogic = $path2.$this->stripRootFolder($currentLogic, $path1);
			$newPhysic = $physicPath2.$this->stripRootFolder($currentPhysic, $physicPath1);
			if ($path1 !== $currentLogic) {
				try {
					$updateQuery->execute(array($newLogic, $newPhysic, $currentLogic));
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
			$path = substr_replace($path ,'',-1);
		}
		return $path;
	}

	private function resolveLogicPath($logicPath) {
		$logicPath = $this->stripLast($logicPath);
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*file_map` WHERE `logic_path` = ?');
		$result = $query->execute(array($logicPath));
		$result = $result->fetchRow();

		return $result['physic_path'];
	}

	private function resolvePhysicalPath($physicalPath) {
		$physicalPath = $this->stripLast($physicalPath);
		$query = \OC_DB::prepare('SELECT * FROM `*PREFIX*file_map` WHERE `physic_path` = ?');
		$result = $query->execute(array($physicalPath));
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
			$physicalPath = $this->slugifyPath($physicalPath, $index++);
		}

		// insert the new path mapping if requested
		if ($store) {
			$this->insert($logicPath, $physicalPath);
		}

		return $physicalPath;
	}

	private function insert($logicPath, $physicalPath) {
		$query = \OC_DB::prepare('INSERT INTO `*PREFIX*file_map`(`logic_path`,`physic_path`) VALUES(?,?)');
		$query->execute(array($logicPath, $physicalPath));
	}

	private function slugifyPath($path, $index=null) {
		$pathElements = explode('/', $path);
		$sluggedElements = array();

		// skip slugging the drive letter on windows - TODO: test if local path
		if (strpos(strtolower(php_uname('s')), 'win') !== false) {
			$sluggedElements[]= $pathElements[0];
			array_shift($pathElements);
		}
		foreach ($pathElements as $pathElement) {
			// TODO: remove file ext before slugify on last element
			$sluggedElements[] = self::slugify($pathElement);
		}

		//
		// TODO: add the index before the file extension
		//
		if ($index !== null) {
			$last= end($sluggedElements);
			array_pop($sluggedElements);
			array_push($sluggedElements, $last.'-'.$index);
		}
		return implode(DIRECTORY_SEPARATOR, $sluggedElements);
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
			$text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
		}

		// lowercase
		$text = strtolower($text);

		// remove unwanted characters
		$text = preg_replace('~[^-\w]+~', '', $text);

		if (empty($text))
		{
			// TODO: we better generate a guid in this case
			return 'n-a';
		}

		return $text;
	}
}
