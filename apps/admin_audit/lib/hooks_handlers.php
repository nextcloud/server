<?php

class OC_Admin_Audit_Hooks_Handlers {
	static public function pre_login($params) {
		$path = $params['uid'];
		self::log('Trying login '.$user);
	}
	static public function post_login($params) {
		$path = $params['uid'];
		self::log('Login '.$user);
	}
	static public function logout($params) {
		$user = OCP\User::getUser();
		self::log('Logout '.$user);
	}

	static public function rename($params) {
		$oldpath = $params[OC_Filesystem::signal_param_oldpath];
		$newpath = $params[OC_Filesystem::signal_param_newpath];
		$user = OCP\User::getUser();
		self::log('Rename "'.$oldpath.'" to "'.$newpath.'" by '.$user);
	}
	static public function create($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		self::log('Create "'.$path.'" by '.$user);
	}
	static public function copy($params) {
		$oldpath = $params[OC_Filesystem::signal_param_oldpath];
		$newpath = $params[OC_Filesystem::signal_param_newpath];
		$user = OCP\User::getUser();
		self::log('Copy "'.$oldpath.'" to "'.$newpath.'" by '.$user);
	}
	static public function write($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		self::log('Write "'.$path.'" by '.$user);
	}
	static public function read($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		self::log('Read "'.$path.'" by '.$user);
	}
	static public function delete($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		self::log('Delete "'.$path.'" by '.$user);
	}
	static protected function log($msg) {
		OCP\Util::writeLog('admin_audit', $msg, OCP\Util::INFO);
	}
}
