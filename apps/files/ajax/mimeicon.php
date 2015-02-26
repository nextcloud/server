<?php
\OC::$server->getSession()->close();

$mime = isset($_GET['mime']) ? (string)$_GET['mime'] : '';

print OC_Helper::mimetypeIcon($mime);
