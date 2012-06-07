<?php

$stmt = OCP\DB::prepare('DROP TABLE IF EXISTS *PREFIX*gallery_photos');
$stmt->execute();
$stmt = OCP\DB::prepare('DROP TABLE IF EXISTS *PREFIX*gallery_albums');
$stmt->execute();

\OC_DB::createDbFromStructure('./database.xml');

