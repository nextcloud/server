<?php
/**
 * @copyright Copyright (c) 2017 Beame.io LTD <support@beame.io>
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

$urlGenerator = \OC::$server->getURLGenerator();

style('beame_insta_ssl', 'settings-admin');

?>

<div id="beame-insta-ssl" class="section">
	<h2><?php p($l->t('Secure remote access (beame-insta-ssl)')); ?></h2>
	<p class="settings-hint"><?php p($l->t('Access your site via https://xxxx.beameio.net/ without configuring your firewall or router.')); ?></p>

<?php if($_['error']) { ?>
<p>
	<div class="error"><?php p($l->t('The following error occured while trying to configure beame-insta-ssl:')); ?></div>
	<pre class="error"><?php foreach(preg_split('/[\r\n]+/', $_['error']) as $e) { p($e); print_unescaped('<br/>'); } ?></pre>
</p>
<?php } ?>

<!--
<?php if($_['got_creds']) { ?>
<p class="msg">
	<?php p($l->t("You've got certificate for HTTPS")); ?>
</p>
<?php } ?>
-->

<?php
	switch($_['mode']) {
	case 'not-installed': ?>
<p>
	<?php print_unescaped($l->t('Please install beame-insta-ssl npm using the following command: <pre><code>%s</code></pre>  Secure remote access is not available without beame-insta-ssl npm.', array('sudo npm -g install beame-insta-ssl')));?>

</p>
<p>
	<a href="https://github.com/beameio/beame-insta-ssl" target="_blank" class="unobtrusive"><?php p($l->t('More information about beame-insta-ssl.'));?></a>
</p>
<!---------- not installed ---------->
<?php
		break;
	case 'nocreds': ?>
<!---------- not set up ---------->

<p>
<form method="post" action="<?php p($urlGenerator->linkToRoute('beame_insta_ssl.settings.getCreds')); ?>">
	<label for="beame-insta-ssl-token"><?php print_unescaped($l->t('In order to use beame-insta-ssl remote access, please <a href="%s" target="_blank">register</a> and use your token below:', array('https://ypxf72akb6onjvrq.ohkv8odznwh5jpwm.v1.p.beameio.net/insta-ssl?from=nextcloud')));?></label><br/>
	<textarea id="beame-insta-ssl-token" name="token" placeholder="<?php p($l->t('Long token from email, looks like:'))?> TG9yZW0gaXBzdW0gZG9sb3Igc2l0IGFtZXQsIHRlIGRpY3RhIG1hbG9ydW0gdm9sdXRwYXQgcXVpLCBl..."></textarea><br/>
	<input type="submit" value="<?php p($l->t('Finish setup')); ?>"/><?php p($l->t('Typically takes about 30 seconds.'))?> <a href="https://github.com/beameio/beame-insta-ssl" target="_blank" class="unobtrusive"><?php p($l->t('More information about beame-insta-ssl.'));?></a>
</form>
</p>

<?php
		break;
	case 'stopped': ?>
<!---------- stopped ---------->

<p>
	<?php p($l->t('beame-insta-ssl is not running.'))?><br/>
	<form method="post" action="<?php p($urlGenerator->linkToRoute('beame_insta_ssl.settings.start')); ?>">
		<label for="beame-insta-ssl-fqdn"><?php p($l->t('HTTPS domain name to use (FQDN)'))?></label>
		<select id="beame-insta-ssl-fqdn" name="fqdn">
			<?php foreach($_['creds'] as $cred) { ?>
				<option value="<?php p($cred->fqdn)?>"><?php p($cred->name)?></option>
			<?php } ?>
		</select>
		<input type="submit" value="<?php p($l->t('Start')); ?>"/>
	</form>
</p>

<?php
		break;
	case 'running': ?>
<!---------- running ---------->
<?php echo($l->t('beame-insta-ssl is running. Your secure remote access link is <a href="%s" target="_blank">%s</a> .The link will start working in a minute or two if it\'s the first time.', [$_['run_link'], $_['run_link']]))?><br/>
<form method="post" action="<?php p($urlGenerator->linkToRoute('beame_insta_ssl.settings.stop')); ?>">
	<input type="submit" value="<?php p($l->t('Stop')); ?>"/>
</form>
<?php
		break;
	case 'stale': ?>
<!---------- stale ---------->
<p class="error">
	<?php p($l->t('beame-insta-ssl either crashed or did not start properly.'));?>
	<form method="post" action="<?php p($urlGenerator->linkToRoute('beame_insta_ssl.settings.cleanup'));?>">
		<input type="submit" value="<?php p($l->t('OK')); ?>"/>
	</form>
</p>
<?php
		break;
	default:
		echo($l->t('Internal error: unknown template mode %s', array($_['mode'])));
} ?>

</div>
