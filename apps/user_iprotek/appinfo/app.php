<?php
namespace OCA\UserIprotek\AppInfo;

use OCP\App;
use OCP\User;
use OCA\UserLaravel\LaravelBackend; 

\OC::$server->getUserManager()->registerBackend(new \OCA\UserIprotek\iProtekBackend());

//$app = new \OCA\UserLaravel\LaravelBackend();
//User::useBackend($app);