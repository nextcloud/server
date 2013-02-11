<?php
/**
 * Copyright (c) 2013 Lukas Reschke <lukas@statuscode.ch>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

// Check if admin user
OC_Util::checkAdminUser();

// Set the content type to JSON
header('Content-type: application/json');

// Disallow caching
header("Cache-Control: no-cache, must-revalidate"); 
header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); 

$combinedApps = OC_App::listAllApps();

foreach($combinedApps as $app) {
	echo("appData_".$app['id']."=".json_encode($app));
	echo("\n");
}

echo ("var appid =".json_encode($_GET['appid']).";");