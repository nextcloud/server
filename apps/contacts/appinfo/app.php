<?php

OC::$CLASSPATH['OC_Contacts_Addressbook'] = 'apps/contacts/lib/addressbook.php';
OC::$CLASSPATH['OC_Connector_Sabre_CardDAV'] = 'apps/contacts/lib/connector_sabre.php';

OC_App::register( array( 
  'order' => 10,
  'id' => 'contacts',
  'name' => 'Contacts' ));

OC_App::addNavigationEntry( array( 
  'id' => 'contacts_index',
  'order' => 10,
  'href' => OC_Helper::linkTo( 'contacts', 'index.php' ),
  'icon' => OC_Helper::imagePath( 'contacts', 'icon.png' ),
  'name' => 'Contacts' ));

?>
