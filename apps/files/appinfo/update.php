<?php

// this drops the keys below, because they aren't needed anymore
// core related
if (version_compare(\OCP\Config::getSystemValue('version', '0.0.0'), '7.0.0', '<')) {
	\OCP\Config::deleteSystemValue('allowZipDownload');
	\OCP\Config::deleteSystemValue('maxZipInputSize');
}

if (version_compare(\OCP\Config::getAppValue('files', 'installed_version'), '1.1.8', '<')) {

	// update wrong mimetypes
	$wrongMimetypes = array(
		'application/mspowerpoint' => 'application/vnd.ms-powerpoint',
		'application/msexcel' => 'application/vnd.ms-excel',
	);

	$stmt = OC_DB::prepare('
		UPDATE `*PREFIX*mimetypes`
		SET    `mimetype` = ?
		WHERE  `mimetype` = ?
	');

	foreach ($wrongMimetypes as $wrong => $correct) {
		OC_DB::executeAudited($stmt, array($wrong, $correct));
	}

	$updatedMimetypes = array(
		'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'xlsx' => 'application/vnd.ms-excel',
		'pptx' => 'application/vnd.ms-powerpoint',
	);

	// separate doc from docx etc
	foreach ($updatedMimetypes as $extension => $mimetype ) {
		$result = OC_DB::executeAudited('
			SELECT count(`mimetype`)
			FROM   `*PREFIX*mimetypes`
			WHERE  `mimetype` = ?
			', array($mimetype)
		);

		$exists = $result->fetchOne();

		if ( ! $exists ) {
			// insert mimetype
			OC_DB::executeAudited('
				INSERT INTO `*PREFIX*mimetypes` ( `mimetype` )
				VALUES ( ? )
				', array($mimetype)
			);
		}

		// change mimetype for files with x extension
		OC_DB::executeAudited('
			UPDATE `*PREFIX*filecache`
			SET `mimetype` = (
				SELECT `id`
				FROM `*PREFIX*mimetypes`
				WHERE `mimetype` = ?
			) WHERE `name` LIKE ?
			', array($mimetype, '%.'.$extension)
		);
	}
}