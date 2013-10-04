<table cellspacing="0" cellpadding="0" border="0" width="100%">
<tr><td>
<table cellspacing="0" cellpadding="0" border="0" width="600px">
<tr>
<td bgcolor="#1d2d44" width="20px">&nbsp;</td>
<td bgcolor="#1d2d44">
<img src="<?php print_unescaped(OC_Helper::makeURLAbsolute(image_path('', 'logo-mail.gif'))); ?>" alt="<?php p($theme->getName()); ?>"/>
</td>
</tr>
<tr><td bgcolor="#f8f8f8" colspan="2">&nbsp;</td></tr>
<tr>
<td bgcolor="#f8f8f8" width="20px">&nbsp;</td>
<td bgcolor="#f8f8f8" style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
<?php
print_unescaped($l->t('Hey there,<br><br>just letting you know that %s shared »%s« with you.<br><a href="%s">View it!</a><br><br>', array($_['user_displayname'], $_['filename'], $_['link'])));
if ( isset($_['expiration']) ) {
	print_unescaped($l->t("The share will expire on %s.<br><br>", array($_['expiration'])));
}
p($l->t('Cheers!'));
?>
</td>
</tr>
<tr><td bgcolor="#f8f8f8" colspan="2">&nbsp;</td></tr>
<tr>
<td bgcolor="#f8f8f8" width="20px">&nbsp;</td>
<td bgcolor="#f8f8f8" style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">--<br>
<?php p($theme->getName()); ?> -
<?php p($theme->getSlogan()); ?>
<br><a href="<?php print_unescaped($theme->getBaseUrl()); ?>"><?php print_unescaped($theme->getBaseUrl());?></a>
</td>
</tr>
<tr>
<td bgcolor="#f8f8f8" colspan="2">&nbsp;</td>
</tr>
</table>
</td></tr>
</table>
