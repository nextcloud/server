<?php
/**
 * Copyright (c) 2012 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Settings pages
$this->create('settings_help', '/settings/help')
	->actionInclude('settings/help.php');
$this->create('settings_personal', '/settings/personal')
	->actionInclude('settings/personal.php');
$this->create('settings_settings', '/settings')
	->actionInclude('settings/settings.php');
$this->create('settings_users', '/settings/users')
	->actionInclude('settings/users.php');
$this->create('settings_apps', '/settings/apps')
	->actionInclude('settings/apps.php');
$this->create('settings_admin', '/settings/admin')
	->actionInclude('settings/admin.php');
// Settings ajax actions
// users
$this->create('settings_ajax_userlist', '/settings/ajax/userlist')
	->actionInclude('settings/ajax/userlist.php');
$this->create('settings_ajax_createuser', '/settings/ajax/createuser.php')
	->actionInclude('settings/ajax/createuser.php');
$this->create('settings_ajax_removeuser', '/settings/ajax/removeuser.php')
	->actionInclude('settings/ajax/removeuser.php');
$this->create('settings_ajax_setquota', '/settings/ajax/setquota.php')
	->actionInclude('settings/ajax/setquota.php');
$this->create('settings_ajax_creategroup', '/settings/ajax/creategroup.php')
	->actionInclude('settings/ajax/creategroup.php');
$this->create('settings_ajax_togglegroups', '/settings/ajax/togglegroups.php')
	->actionInclude('settings/ajax/togglegroups.php');
$this->create('settings_ajax_togglesubadmins', '/settings/ajax/togglesubadmins.php')
	->actionInclude('settings/ajax/togglesubadmins.php');
$this->create('settings_ajax_removegroup', '/settings/ajax/removegroup.php')
	->actionInclude('settings/ajax/removegroup.php');
$this->create('settings_ajax_changepassword', '/settings/ajax/changepassword.php')
	->actionInclude('settings/ajax/changepassword.php');
$this->create('settings_ajax_changedisplayname', '/settings/ajax/changedisplayname.php')
	->actionInclude('settings/ajax/changedisplayname.php');
// personel
$this->create('settings_ajax_lostpassword', '/settings/ajax/lostpassword.php')
	->actionInclude('settings/ajax/lostpassword.php');
$this->create('settings_ajax_setlanguage', '/settings/ajax/setlanguage.php')
	->actionInclude('settings/ajax/setlanguage.php');
// apps
$this->create('settings_ajax_apps_ocs', '/settings/ajax/apps/ocs.php')
	->actionInclude('settings/ajax/apps/ocs.php');
$this->create('settings_ajax_enableapp', '/settings/ajax/enableapp.php')
	->actionInclude('settings/ajax/enableapp.php');
$this->create('settings_ajax_disableapp', '/settings/ajax/disableapp.php')
	->actionInclude('settings/ajax/disableapp.php');
$this->create('settings_ajax_updateapp', '/settings/ajax/updateapp.php')
	->actionInclude('settings/ajax/updateapp.php');
$this->create('settings_ajax_navigationdetect', '/settings/ajax/navigationdetect.php')
	->actionInclude('settings/ajax/navigationdetect.php');
$this->create('apps_custom', '/settings/js/apps-custom.js')
	->actionInclude('settings/js/apps-custom.php');
// admin
$this->create('settings_ajax_getlog', '/settings/ajax/getlog.php')
	->actionInclude('settings/ajax/getlog.php');
$this->create('settings_ajax_setloglevel', '/settings/ajax/setloglevel.php')
	->actionInclude('settings/ajax/setloglevel.php');
$this->create('settings_ajax_setsecurity', '/settings/ajax/setsecurity.php')
	->actionInclude('settings/ajax/setsecurity.php');
$this->create('isadmin', '/settings/js/isadmin.js')
	->actionInclude('settings/js/isadmin.php');
