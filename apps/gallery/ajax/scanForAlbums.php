<?php

require_once('../../../lib/base.php');
OC_JSON::checkLoggedIn();
OC_JSON::checkAppEnabled('gallery');

OC_Gallery_Scanner::cleanUp();
OC_JSON::success(array('albums' => OC_Gallery_Scanner::scan('')));

?>
