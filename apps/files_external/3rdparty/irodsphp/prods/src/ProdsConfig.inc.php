<?php

/**#@+
 * Constants
 */

define("RODS_REL_VERSION", 'rods1.1');
define("RODS_API_VERSION", 'd');

/**#@-*/

if (file_exists(__DIR__ . "/prods.ini")) {
  $GLOBALS['PRODS_CONFIG'] = parse_ini_file(__DIR__ . "/prods.ini", true);
}
else {
  $GLOBALS['PRODS_CONFIG'] = array();
}
