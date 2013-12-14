<?php

$l = OC_L10N::get('files');

OCP\App::registerAdmin('files', 'admin');

OCP\App::addNavigationEntry(array("id" => "files_index",
	"order" => 0,
	"href" => OCP\Util::linkTo("files", "index.php"),
	"icon" => OCP\Util::imagePath("core", "places/files.svg"),
	"name" => $l->t("Files")));

OC_Search::registerProvider('OC_Search_Provider_File');

// cache hooks must be connected before all other apps.
// since 'files' is always loaded first the hooks need to be connected here
\OC_Hook::connect('OC_Filesystem', 'post_write', '\OC\Files\Cache\Updater', 'writeHook');
\OC_Hook::connect('OC_Filesystem', 'post_touch', '\OC\Files\Cache\Updater', 'touchHook');
\OC_Hook::connect('OC_Filesystem', 'post_delete', '\OC\Files\Cache\Updater', 'deleteHook');
\OC_Hook::connect('OC_Filesystem', 'post_rename', '\OC\Files\Cache\Updater', 'renameHook');

\OCP\BackgroundJob::addRegularTask('\OC\Files\Cache\BackgroundWatcher', 'checkNext');

$templateManager = OC_Helper::getFileTemplateManager();
$templateManager->registerTemplate('text/html', 'core/templates/filetemplates/template.html');
$templateManager->registerTemplate('application/vnd.oasis.opendocument.presentation', 'core/templates/filetemplates/template.odp');
$templateManager->registerTemplate('application/vnd.oasis.opendocument.text', 'core/templates/filetemplates/template.odt');
$templateManager->registerTemplate('application/vnd.oasis.opendocument.spreadsheet', 'core/templates/filetemplates/template.ods');
