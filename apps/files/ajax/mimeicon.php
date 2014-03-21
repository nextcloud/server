<?php
\OC::$session->close();

print OC_Helper::mimetypeIcon($_GET['mime']);
