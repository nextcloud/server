<?php

// only need filesystem apps
$RUNTIME_APPTYPES = array('filesystem');

OCP\JSON::checkLoggedIn();

$l=new OC_L10N('files');
$maxUploadFilesize = OCP\Util::maxUploadFilesize($dir);
$maxHumanFilesize = OCP\Util::humanFileSize($maxUploadFilesize);
$maxHumanFilesize = $l->t('Upload') . ' max. ' . $maxHumanFilesize;

// send back json
OCP\JSON::success(array('data' => array('uploadMaxFilesize' => $maxUploadFilesize,
										'maxHumanFilesize'  => $maxHumanFilesize
)));
