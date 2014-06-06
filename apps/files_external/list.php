<?php

// Check if we are a user
OCP\User::checkLoggedIn();

$tmpl = new OCP\Template('files_external', 'list', '');

OCP\Util::addScript('files_external', 'app');
OCP\Util::addScript('files_external', 'mountsfilelist');

$tmpl->printPage();
