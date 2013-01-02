<?php
$installedVersion = OCP\Config::getAppValue('files_external', 'installed_version');
if (version_compare($installedVersion, '0.2', '<')) {
	
}