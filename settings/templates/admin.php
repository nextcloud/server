<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
$levels=array('Debug','Info','Warning','Error','Fatal');
?>

<?php

if(!$_['htaccessworking']) {
?>
<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Security Warning');?></strong></legend>

	<span class="securitywarning">Your data directory and your files are probably accessible from the internet. The .htaccess file that ownCloud provides is not working. We strongly suggest that you configure your webserver in a way that the data directory is no longer accessible or you move the data directory outside the webserver document root.</span>
	
</fieldset>	
<?php	
}
?>


<?php foreach($_['forms'] as $form){
	echo $form;
};?>

<fieldset class="personalblock" id="backgroundjobs">
	<legend><strong><?php echo $l->t('Cron');?></strong></legend>
	<input type="radio" name="mode" value="ajax" id="backgroundjobs_ajax" <?php if( $_['backgroundjobs_mode'] == "ajax" ){ echo 'checked="checked"'; } ?>>
	<label for="backgroundjobs_ajax" title="<?php echo $l->t("execute one task with each page loaded"); ?>">AJAX</label><br />
	<input type="radio" name="mode" value="webcron" id="backgroundjobs_webcron" <?php if( $_['backgroundjobs_mode'] == "webcron" ){ echo 'checked="checked"'; } ?>>
	<label for="backgroundjobs_webcron" title="<?php echo $l->t("cron.php is registered at a webcron service"); ?>">Webcron</label><br />
	<input type="radio" name="mode" value="cron" id="backgroundjobs_cron" <?php if( $_['backgroundjobs_mode'] == "cron" ){ echo 'checked="checked"'; } ?>>
	<label for="backgroundjobs_cron" title="<?php echo $l->t("use systems cron service"); ?>">Cron</label><br />
</fieldset>

<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Log');?></strong></legend>
	Log level: <select name='loglevel' id='loglevel'>
		<option value='<?php echo $_['loglevel']?>'><?php echo $levels[$_['loglevel']]?></option>
		<?php for($i=0;$i<5;$i++):
			if($i!=$_['loglevel']):?>
				<option value='<?php echo $i?>'><?php echo $levels[$i]?></option>
			<?php endif;
		endfor;?>
	</select>
	<table id='log'>
	<?php foreach($_['entries'] as $entry):?>
		<tr>
			<td>
				<?php echo $levels[$entry->level];?>
			</td>
			<td>
				<?php echo $entry->app;?>
			</td>
			<td>
				<?php echo $entry->message;?>
			</td>
			<td>
				<?php echo OC_Util::formatDate($entry->time);?>
			</td>
		</tr>
	<?php endforeach;?>
</table>
<?php if($_['entriesremain']): ?>
<input id='moreLog' type='button' value='<?php echo $l->t('More');?>...'></input>
<?php endif; ?>

</fieldset>


<p class="personalblock">
	<strong>ownCloud</strong> <?php echo(OC_Util::getVersionString()); ?> <?php echo(OC_Util::getEditionString()); ?> (<?php echo(OC_Updater::ShowUpdatingHint()); ?>)<br />
	Developed by the <a href="http://ownCloud.org/contact" target="_blank">ownCloud community</a>, the <a href="http://gitorious.org/owncloud" target="_blank">source code</a> is licensed under the <a href="http://www.gnu.org/licenses/agpl-3.0.html" target="_blank"><abbr title="Affero General Public License">AGPL</abbr></a>.
</p>

