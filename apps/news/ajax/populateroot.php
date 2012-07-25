<?php

OCP\JSON::checkLoggedIn();
OCP\JSON::checkAppEnabled('news');

$foldermapper = new OC_News_FolderMapper(OCP\USER::getUser());

$allfeeds = $foldermapper->populate('Everything', 0);

if ($allfeeds) {
	$feedid = isset( $_GET['feedid'] ) ? $_GET['feedid'] : null;
	if ($feedid == null) {

	}
}
else {
	$feedid = 0;
}
