<?php

// Check if we are a user
OCP\User::checkLoggedIn();


$tmpl = new OCP\Template('files_trashbin', 'index', '');
// TODO: re-enable after making sure the scripts doesn't
// override the files app
/*
OCP\Util::addScript('files_trashbin', 'disableDefaultActions');
OCP\Util::addStyle('files_trashbin', 'trash');
OCP\Util::addScript('files_trashbin', 'filelist');
OCP\Util::addScript('files_trashbin', 'trash');
 */
$tmpl->printPage();
