<?php
$this->create('core_ajax_trashbin_preview', '/preview.png')->action(
function() {
	require_once __DIR__ . '/../ajax/preview.php';
});