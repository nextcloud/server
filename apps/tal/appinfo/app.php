<?php
OC::$CLASSPATH['OC_TALTemplate'] =				'tal/lib/taltemplate.php';
OC::$CLASSPATH['OC_TALL10N'] =					'tal/lib/tall10n.php';
OC::$CLASSPATH['PHPTAL'] =						'tal/lib/PHPTAL/classes/PHPTAL.php';
OC::$CLASSPATH['PHPTAL_TranslationService'] =	'tal/lib/PHPTAL/classes/PHPTAL/TranslationService.php';

OCP\App::register( array(
	'id' => 'tal',
	'name' => 'TAL Page Templates' ));

OCP\App::registerPersonal('tal','settings');