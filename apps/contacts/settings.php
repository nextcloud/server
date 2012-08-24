<?php

$tmpl = new OCP\Template( 'contacts', 'settings');
$tmpl->assign('addressbooks', OC_Contacts_Addressbook::all(OCP\USER::getUser()), false);

$tmpl->printPage();
