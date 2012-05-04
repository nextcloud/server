<?php
OCP\App::register( array( 
  'order' => 10,
  'id' => 'remoteStorage',
  'name' => 'remoteStorage compatibility' ));
OCP\App::registerPersonal('remoteStorage','settings');
