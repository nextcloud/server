<?php
$l=new OC_L10N('contacts');

OC::$CLASSPATH['OC_Contacts_App'] = 'apps/contacts/lib/app.php';
OC::$CLASSPATH['OC_Contacts_Addressbook'] = 'apps/contacts/lib/addressbook.php';
OC::$CLASSPATH['OC_Contacts_VCard'] = 'apps/contacts/lib/vcard.php';
OC::$CLASSPATH['OC_Contacts_Hooks'] = 'apps/contacts/lib/hooks.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV'] = 'apps/contacts/lib/connector_sabre.php';
OC_HOOK::connect('OC_User', 'post_deleteUser', 'OC_Contacts_Hooks', 'deleteUser');

OC_App::register( array(
  'order' => 10,
  'id' => 'contacts',
  'name' => 'Contacts' ));

OC_App::addNavigationEntry( array(
  'id' => 'contacts_index',
  'order' => 10,
  'href' => OC_Helper::linkTo( 'contacts', 'index.php' ),
  'icon' => OC_Helper::imagePath( 'settings', 'users.svg' ),
  'name' => $l->t('Contacts') ));


OC_APP::registerPersonal('contacts','settings');
require_once('apps/contacts/lib/search.php');