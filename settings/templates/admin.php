<?php /**
 * Copyright (c) 2011, Robin Appelman <icewind1991@gmail.com>
 * This file is licensed under the Affero General Public License version 3 or later.
 * See the COPYING-README file.
 */
$levels=array('Debug','Info','Warning','Error','Fatal');
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
