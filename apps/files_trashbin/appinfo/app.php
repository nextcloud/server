<?php
$l = \OC::$server->getL10N('files_trashbin');

// register hooks
\OCA\Files_Trashbin\Trashbin::registerHooks();

\OCA\Files\App::getNavigationManager()->add(
array(
	"id" => 'trashbin',
	"appname" => 'files_trashbin',
	"script" => 'list.php',
	"order" => 50,
	"name" => $l->t('Deleted files')
)
);
