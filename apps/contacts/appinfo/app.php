<?php
OC::$CLASSPATH['OC_Contacts_App'] = 'apps/contacts/lib/app.php';
OC::$CLASSPATH['OC_Contacts_Addressbook'] = 'apps/contacts/lib/addressbook.php';
OC::$CLASSPATH['OC_Contacts_VCard'] = 'apps/contacts/lib/vcard.php';
OC::$CLASSPATH['OC_Contacts_Hooks'] = 'apps/contacts/lib/hooks.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV'] = 'apps/contacts/lib/connector_sabre.php';
OC::$CLASSPATH['OC_Search_Provider_Contacts'] = 'apps/contacts/lib/search.php';
OCP\Util::connectHook('OC_User', 'post_createUser', 'OC_Contacts_Hooks', 'createUser');
OCP\Util::connectHook('OC_User', 'post_deleteUser', 'OC_Contacts_Hooks', 'deleteUser');
OCP\Util::connectHook('OC_Calendar', 'getEvents', 'OC_Contacts_Hooks', 'getBirthdayEvents');
OCP\Util::connectHook('OC_Calendar', 'getSources', 'OC_Contacts_Hooks', 'getCalenderSources');

OCP\App::register( array(
  'order' => 10,
  'id' => 'contacts',
  'name' => 'Contacts' ));

OCP\App::addNavigationEntry( array(
  'id' => 'contacts_index',
  'order' => 10,
  'href' => OCP\Util::linkTo( 'contacts', 'index.php' ),
  'icon' => OCP\Util::imagePath( 'settings', 'users.svg' ),
  'name' => OC_L10N::get('contacts')->t('Contacts') ));


OCP\App::registerPersonal('contacts','settings');
OCP\Util::addscript('contacts', 'loader');
OC_Search::registerProvider('OC_Search_Provider_Contacts');
