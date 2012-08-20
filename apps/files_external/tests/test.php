<?php
require_once 'files_external/lib/config.php';
echo "<pre>";
print_r(OC_Mount_Config::getSystemMountPoints());
echo "</pre>";
// OC_Mount_Config::addMountPoint('Photos', 'OC_Filestorage_SWIFT', array('host' => 'gapinthecloud.com', 'user' => 'Gap', 'token' => '23423afdasFJEW22', 'secure' => 'true', 'root' => ''), OC_Mount_Config::MOUNT_TYPE_GROUP, 'admin', false);
?>
