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
		$path1 = $this->stripLast($path1);
		$path2 = $this->stripLast($path2);
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

	private function stripLast($path) {
		if (substr($path, -1) == '/') {
			$path = substr_replace($path, '', -1);
		}
		return $path;
	}

	/**
	 * @param string $logicPath
	 */
	private function resolveLogicPath($logicPath) {
		$logicPath = $this->stripLast($logicPath);
		$sql = 'SELECT * FROM `*PREFIX*file_map` WHERE `logic_path_hash` = ?';
		$result = \OC_DB::executeAudited($sql, array(md5($logicPath)));
		$result = $result->fetchRow();
		if ($result === false) {
			return null;
		}

		return $result['physic_path'];
	}

	private function resolvePhysicalPath($physicalPath) {
		$physicalPath = $this->stripLast($physicalPath);
		$sql = \OC_DB::prepare('SELECT * FROM `*PREFIX*file_map` WHERE `physic_path_hash` = ?');
		$result = \OC_DB::executeAudited($sql, array(md5($physicalPath)));
		$result = $result->fetchRow();

		return $result['logic_path'];
	}

	/**
	 * @param string $logicPath
	 * @param boolean $store
	 */
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
		$sql = 'INSERT INTO `*PREFIX*file_map` (`logic_path`, `physic_path`, `logic_path_hash`, `physic_path_hash`)
				VALUES (?, ?, ?, ?)';
		\OC_DB::executeAudited($sql, array($logicPath, $physicalPath, md5($logicPath), md5($physicalPath)));
	}

	/**
	 * @param integer $index
	 */
	public function slugifyPath($path, $index=null) {
		$path = $this->stripRootFolder($path, $this->unchangedPhysicalRoot);

		$pathElements = explode('/', $path);
		$sluggedElements = array();
		
		$last= end($pathElements);
		
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
			
			// if filename contains periods - add index number before last period
			if (preg_match('~\.[^\.]+$~i',$last,$extension)){
				array_push($sluggedElements, substr($last,0,-(strlen($extension[0]))).'-'.$index.$extension[0]);
			} else {
				// if filename doesn't contain periods add index ofter the last char
				array_push($sluggedElements, $last.'-'.$index);
				}

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

		if (empty($text)) {
			return uniqid();
		}

		return $text;
	}
}
