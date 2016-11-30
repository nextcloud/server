<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr><td>
			<table cellspacing="0" cellpadding="0" border="0" width="600px">
				<tr>
					<td colspan="2" bgcolor="<?php p($theme->getMailHeaderColor());?>" bordercolor="<?php p($theme->getMailHeaderColor());?>" border>
						<img src="<?php p(\OC::$server->getURLGenerator()->getAbsoluteURL(image_path('', 'logo-mail.png'))); ?>" alt="<?php p($theme->getName()); ?>"/>
					</td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td width="20px">&nbsp;</td>
					<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
						<?php
						print_unescaped($l->t('Hey there,<br><br>just letting you know that you now have a %s account.<br><br>Your username: <strong>%s</strong><br>Access it: <strong><a href="%s">%s</a></strong><br><br>', array($theme->getName(), $_['username'], $_['url'], $_['url'])));

						// TRANSLATORS term at the end of a mail
						p($l->t('Cheers!'));
						?>
					</td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td width="20px">&nbsp;</td>
					<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">--<br>
						<?php p($theme->getName()); ?> -
						<?php p($theme->getSlogan()); ?>
						<br><a href="<?php p($theme->getBaseUrl()); ?>"><?php p($theme->getBaseUrl());?></a>
					</td>
				</tr>
				<tr>
					<td colspan="2">&nbsp;</td>
				</tr>
			</table>
		</td></tr>
</table>
