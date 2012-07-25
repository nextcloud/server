<?php return array(
	'global' => array(
			'/' => array('class' => 'OC_Filestorage_Local', 'options' => array())
		),
	'group' => array(
			'admin' => array('/$user/files/Admin Stuff' => array('class' => 'OC_Filestorage_Local', 'options' => array()))
		),
	'user' => array(
			'all' => array('/$user/files/Pictures' => array('class' => 'OC_Filestorage_DAV', 'options' => array())),
			'MTGap' => array('/$user/files/Test' => array('class' => 'OC_Filestorage_FTP', 'options' => array()))
		)
	);
?>