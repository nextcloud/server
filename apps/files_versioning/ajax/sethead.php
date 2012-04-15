<?php
/**
 * Copyright (c) 2011 Craig Roberts craig0990@googlemail.com
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 */
require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
if(isset($_POST["file_versioning_head"])){
	OC_Preferences::setValue(OC_User::getUser(), 'files_versioning', 'head', $_POST["file_versioning_head"]);
	OC_JSON::success();
}else{
	OC_JSON::error();
}
