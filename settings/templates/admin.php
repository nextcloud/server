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
	<legend><strong><?php echo $l->t('Log level');?></strong></legend>
	<select name='loglevel' id='loglevel'>
		<option value='<?php echo $_['loglevel']?>'><?php echo $levels[$_['loglevel']]?></option>
		<?php for($i=0;$i<5;$i++):
			if($i!=$_['loglevel']):?>
				<option value='<?php echo $i?>'><?php echo $levels[$i]?></option>
			<?php endif;
		endfor;?>
	</select>
</fieldset>
