<?php

// no php execution timeout for webdav
set_time_limit(0);

// Turn off output buffering to prevent memory problems
\OC_Util::obEnd();

$request = \OC::$server->getRequest();
$server = new \OCA\DAV\Server($request, $baseuri);
$server->exec();
