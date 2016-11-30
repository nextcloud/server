<?php
/**
 * @copyright Copyright (c) 2016 Bjoern Schiessle <bjoern@schiessle.org>
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

/** @var OC_Theme $theme */
/** @var array $_ */
?>

<table cellspacing="0" cellpadding="0" border="0" width="100%">
	<tr><td>
			<table cellspacing="0" cellpadding="0" border="0" width="600px">
				<tr>
					<td colspan="2" bgcolor="<?php p($theme->getMailHeaderColor());?>">
						<img src="<?php p(\OC::$server->getURLGenerator()->getAbsoluteURL(image_path('', 'logo-mail.png'))); ?>" alt="<?php p($theme->getName()); ?>"/>
					</td>
				</tr>
				<tr><td colspan="2">&nbsp;</td></tr>
				<tr>
					<td width="20px">&nbsp;</td>
					<td style="font-weight:normal; font-size:0.8em; line-height:1.2em; font-family:verdana,'arial',sans;">
						<?php
						if ($_['onBehalfOf']) {
							print_unescaped($l->t('Hey there,<br><br>%s shared <a href="%s">%s</a> with you on behalf of %s.<br><br>', [$_['owner'], $_['link'], $_['filename'], $_['initiator']]));
						} else {
							print_unescaped($l->t('Hey there,<br><br>%s shared <a href="%s">%s</a> with you.<br><br>', [$_['owner'], $_['link'], $_['filename']]));
						}
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
