<?php
// Check if we are a user
OCP\User::checkLoggedIn();

$tmpl = new OCP\Template('files', 'recentlist', '');

$tmpl->printPage();
