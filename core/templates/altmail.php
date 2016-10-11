<?php
print_unescaped($l->t("Hey there,\n\njust letting you know that %s shared %s with you.\nView it: %s\n\n", array($_['user_displayname'], $_['filename'], $_['link'])));
if ( isset($_['expiration']) ) {
	print_unescaped($l->t("The share will expire on %s.", array($_['expiration'])));
	print_unescaped("\n\n");
}
// TRANSLATORS term at the end of a mail
p($l->t("Cheers!"));
?>

--
<?php p($theme->getName() . ' - ' . $theme->getSlogan()); ?>
<?php print_unescaped("\n".$theme->getBaseUrl());
