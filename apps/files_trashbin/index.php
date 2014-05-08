<?php

// Check if we are a user
OCP\User::checkLoggedIn();

OCP\Util::addScript('files_trashbin', 'disableDefaultActions');

$tmpl = new OCP\Template('files_trashbin', 'index', '');

OCP\Util::addStyle('files_trashbin', 'trash');
OCP\Util::addScript('files_trashbin', 'filelist');
OCP\Util::addScript('files_trashbin', 'trash');

$tmpl->printPage();
