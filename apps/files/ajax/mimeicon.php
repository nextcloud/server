<?php
\OC::$server->getSession()->close();

print OC_Helper::mimetypeIcon($_GET['mime']);
