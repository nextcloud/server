<?php
OCP\App::register( array( 
  'order' => 11,
  'id' => 'user_webfinger',
  'name' => 'Webfinger' ));
OCP\CONFIG::setAppValue('core', 'public_host-meta', '/apps/user_webfinger/host-meta.php');
OCP\CONFIG::setAppValue('core', 'public_webfinger', '/apps/user_webfinger/webfinger.php');
