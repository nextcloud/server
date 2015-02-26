<?php

OCP\Util::addscript('files_versions', 'versions');
OCP\Util::addStyle('files_versions', 'versions');

\OCA\Files_Versions\Hooks::connectHooks();
