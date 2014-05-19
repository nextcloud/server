<?php
/** @var $this \OCP\Route\IRouter */
$this->create('core_ajax_trashbin_preview', '/preview')->action(
function() {
	require_once __DIR__ . '/../ajax/preview.php';
});
