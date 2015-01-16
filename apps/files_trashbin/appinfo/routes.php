<?php
/** @var $this \OCP\Route\IRouter */
$this->create('core_ajax_trashbin_preview', 'ajax/preview.php')
	->actionInclude('files_trashbin/ajax/preview.php');
$this->create('files_trashbin_ajax_delete', 'ajax/delete.php')
	->actionInclude('files_trashbin/ajax/delete.php');
$this->create('files_trashbin_ajax_isEmpty', 'ajax/isEmpty.php')
	->actionInclude('files_trashbin/ajax/isEmpty.php');
$this->create('files_trashbin_ajax_list', 'ajax/list.php')
	->actionInclude('files_trashbin/ajax/list.php');
$this->create('files_trashbin_ajax_undelete', 'ajax/undelete.php')
	->actionInclude('files_trashbin/ajax/undelete.php');


// Register with the capabilities API
\OC_API::register('get', '/cloud/capabilities', array('OCA\Files_Trashbin\Capabilities', 'getCapabilities'), 'files_trashbin', \OC_API::USER_AUTH);
