<?php
\OC::$server->getSession()->close();

$mime = isset($_GET['mime']) ? $_GET['mime'] : '';

print OC_Helper::mimetypeIcon($mime);
