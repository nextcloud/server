<?php

$tmpl = new OCP\Template( 'files_versions', 'settings-personal');

OCP\Util::addscript('files_versions','settings-personal');

return $tmpl->fetchPage();
