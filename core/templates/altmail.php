<?php
$defaults = new OC_Defaults();

print_unescaped($l->t("Hey there,\n\njust letting you know that %s shared %s with you.\nView it: %s\n\nCheers!", array($_['user_displayname'], $_['filename'], $_['link'])));
?>

--
<?php p($defaults->getName() . ' - ' . $defaults->getSlogan()); ?>
<?php print_unescaped("\n".$defaults->getBaseUrl());
