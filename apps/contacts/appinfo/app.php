<?php
OC::$CLASSPATH['OC_Contacts_App'] = 'apps/contacts/lib/app.php';
OC::$CLASSPATH['OC_Contacts_Addressbook'] = 'apps/contacts/lib/addressbook.php';
OC::$CLASSPATH['OC_Contacts_VCard'] = 'apps/contacts/lib/vcard.php';
OC::$CLASSPATH['OC_Contacts_Hooks'] = 'apps/contacts/lib/hooks.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV'] = 'apps/contacts/lib/connector_sabre.php';
OC::$CLASSPATH['OC_Search_Provider_Contacts'] = 'apps/contacts/lib/search.php';
OC_HOOK::connect('OC_User', 'post_deleteUser', 'OC_Contacts_Hooks', 'deleteUser');
OC_HOOK::connect('OC_Calendar', 'getEvents', 'OC_Contacts_Hooks', 'getBirthdayEvents');
OC_HOOK::connect('OC_Calendar', 'getSources', 'OC_Contacts_Hooks', 'getCalenderSources');

OC_App::register( array(
  'order' => 10,
  'id' => 'contacts',
  'name' => 'Contacts' ));

OC_App::addNavigationEntry( array(
  'id' => 'contacts_index',
  'order' => 10,
  'href' => OC_Helper::linkTo( 'contacts', 'index.php' ),
  'icon' => OC_Helper::imagePath( 'settings', 'users.svg' ),
  'name' => OC_L10N::get('contact')->t('Contacts') ));


OC_APP::registerPersonal('contacts','settings');
OC_UTIL::addScript('contacts', 'loader');
OC_Search::registerProvider('OC_Search_Provider_Contacts');
