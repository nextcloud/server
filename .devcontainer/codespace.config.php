<?php

$cloudEnvironmentId = getenv('CLOUDENV_ENVIRONMENT_ID');

if($cloudEnvironmentId !== false) {
    $CONFIG = array (
    'overwritehost' => $cloudEnvironmentId . '-80.apps.codespaces.githubusercontent.com',
    );
}
