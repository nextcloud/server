<?php

class OC_Files_Sharing_Log {
	static public function fopen($arguments) {
		$target = $arguments['target'];
		$source = $arguments['source'];
		$mode = $arguments['mode'];
		self::log($target, $source, $mode);
	}

	static public function file_get_contents($arguments) {
		$target = $arguments['target'];
		$source = $arguments['source'];
		$mode = 'get';
		self::log($target, $source, $mode);
	}

	static public function file_put_contents($arguments) {
		$target = $arguments['target'];
		$source = $arguments['source'];
		$mode = 'put';
		self::log($target, $source, $mode);
	}

	static public function log($target, $source, $mode) {
		$query = OCP\DB::prepare('SELECT * FROM `*PREFIX*sharing` WHERE `source` = ? AND `target` = ?');
		$info = $query->execute(array($source, $target))->fetchAll();
		$info = $info[0];
		//var_dump($info);
		$query = OCP\DB::prepare('INSERT INTO `*PREFIX*sharing_log` VALUES (?,?,?,?,?)');
		$query->execute(array($info['uid_owner'], $source, OCP\User::getUser(), time(), $mode));
		//die;
	}
}
