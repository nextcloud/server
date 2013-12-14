<?php
$CONFIG = array (
  "appstoreenabled" => false,
  'apps_paths' =>
  array (
    0 =>
    array (
      'path' => OC::$SERVERROOT.'/apps',
      'url' => '/apps',
      'writable' => false,
    ),
    1 =>
    array (
      'path' => OC::$SERVERROOT.'/apps2',
      'url' => '/apps2',
      'writable' => false,
    )
    ),

);

if(substr(strtolower(PHP_OS), 0, 3) == "win") {
	$CONFIG['openssl'] = array( 'config' => OC::$SERVERROOT.'/tests/data/openssl.cnf');
}
