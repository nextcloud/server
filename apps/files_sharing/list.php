<?php

// Check if we are a user
OCP\User::checkLoggedIn();

$tmpl = new OCP\Template('files_sharing', 'list', '');

OCP\Util::addScript('files_sharing', 'app');
OCP\Util::addScript('files_sharing', 'sharedfilelist');

$tmpl->printPage();
