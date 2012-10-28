<?php

class OC_Sharing_API {
	
	static public function shareFile($parameters) {
		$path = '/'.$parameters['path'];
		$fileid = OC_FileCache::getId($path);
		$typemap = array(
			'user' => OCP\Share::SHARE_TYPE_USER,
			'group' => OCP\Share::SHARE_TYPE_GROUP,
			'link' => OCP\Share::SHARE_TYPE_LINK,
			'email' => OCP\Share::SHARE_TYPE_EMAIL,
			'contact' => OCP\Share::SHARE_TYPE_CONTACT,
			'remote' => OCP\Share::SHARE_TYPE_USER,
			);
		$type = $typemap[$parameters['type']];
		$shareWith = isset($_POST['shareWith']) ? $_POST['shareWith'] : null;
		$permissionstring = isset($_POST['permissions']) ? $_POST['permissions'] : '';
		$permissionmap = array(
			'C' => OCP\Share::PERMISSION_CREATE,
			'R' => OCP\Share::PERMISSION_READ,
			'U' => OCP\Share::PERMISSION_UPDATE,
			'D' => OCP\Share::PERMISSION_DELETE,
			'S' => OCP\Share::PERMISSION_SHARE,
			);
		$permissions = 0;
		foreach($permissionmap as $letter => $permission) {
			if(strpos($permissionstring, $letter) !== false) {
				$permissions += $permission;
			}
		}

		try {
			OCP\Share::shareItem('file', $fileid, $type, $shareWith, $permissions);
		} catch (Exception $e){
			error_log($e->getMessage());
		}
		switch($type){
			case OCP\Share::SHARE_TYPE_LINK:
				return array('url' => OC_Helper::linkToPublic('files') . '&file=/' . OC_User::getUser() . '/files' . $path);
			break;
		}
		
	}
	
}