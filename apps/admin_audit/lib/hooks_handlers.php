<?php

class OC_Admin_Audit_Hooks_Handlers {
	static public function pre_login($params) {
		$path = $params['uid'];
		OCP\Util::writeLog('admin_audit', 'Trying login '.$user, OCP\Util::INFO);
	}
	static public function post_login($params) {
		$path = $params['uid'];
		OCP\Util::writeLog('admin_audit', 'Login '.$user, OCP\Util::INFO);
	}
	static public function logout($params) {
		$user = OCP\User::getUser();
		OCP\Util::writeLog('admin_audit', 'Logout '.$user, OCP\Util::INFO);
	}

	static public function rename($params) {
		$oldpath = $params[OC_Filesystem::signal_param_oldpath];
		$newpath = $params[OC_Filesystem::signal_param_newpath];
		$user = OCP\User::getUser();
		OCP\Util::writeLog('admin_audit', 'Rename "'.$oldpath.'" to "'.$newpath.'" by '.$user, OCP\Util::INFO);
	}
	static public function create($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		OCP\Util::writeLog('admin_audit', 'Create "'.$path.'" by '.$user, OCP\Util::INFO);
	}
	static public function copy($params) {
		$oldpath = $params[OC_Filesystem::signal_param_oldpath];
		$newpath = $params[OC_Filesystem::signal_param_newpath];
		$user = OCP\User::getUser();
		OCP\Util::writeLog('admin_audit', 'Copy "'.$oldpath.'" to "'.$newpath.'" by '.$user, OCP\Util::INFO);
	}
	static public function write($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		OCP\Util::writeLog('admin_audit', 'Write "'.$path.'" by '.$user, OCP\Util::INFO);
	}
	static public function read($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		OCP\Util::writeLog('admin_audit', 'Read "'.$path.'" by '.$user, OCP\Util::INFO);
	}
	static public function delete($params) {
		$path = $params[OC_Filesystem::signal_param_path];
		$user = OCP\User::getUser();
		OCP\Util::writeLog('admin_audit', 'Delete "'.$path.'" by '.$user, OCP\Util::INFO);
	}
}
