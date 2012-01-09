<?php
require_once ("../../lib/base.php"); 
OC_Preferences::deleteKey(OC_USER::getUser(), 'calendar', 'timezone');
?>