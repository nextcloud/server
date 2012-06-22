<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
$levels=array('Debug','Info','Warning','Error','Fatal');
	
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
<input id='moreLog' type='button' value='<?php echo $l->t('More');?>...'></input>
</fieldset>
