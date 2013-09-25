<?php
$expiration = isset($_['expiration']) ? $_['expiration'] : null;
print_unescaped($theme->getShareNotificationTextAlt($_['user_displayname'], $_['filename'], $_['link'], $expiration));
?>

--
<?php print_unescaped($theme->getMailFooterAlt());
