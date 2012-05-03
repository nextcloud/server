<?php

require_once('../../../lib/base.php');
OCP\JSON::checkAppEnabled('files_versions');
require_once('../versions.php');

$userDirectory = "/".OCP\USER::getUser()."/files";

$source = $_GET['source'];

$source = strip_tags( $source );

echo "\n\n$source\n\n";

$revision = strtotime( $source );

echo "\n\n$revision\n\n";

if( OCA_Versions\Storage::isversioned( $source ) ) {


        #\OCA_Versions\Storage::rollback( $source, $revision );
	
}

?>