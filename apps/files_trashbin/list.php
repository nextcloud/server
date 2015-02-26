<?php

// Check if we are a user
OCP\User::checkLoggedIn();


$tmpl = new OCP\Template('files_trashbin', 'index', '');
OCP\Util::addStyle('files_trashbin', 'trash');
OCP\Util::addScript('files_trashbin', 'app');
OCP\Util::addScript('files_trashbin', 'filelist');
$tmpl->printPage();
