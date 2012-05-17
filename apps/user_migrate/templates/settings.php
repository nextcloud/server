<fieldset class="personalblock">
	<legend><strong><?php echo $l->t('Export your user account');?></strong></legend>
	<p><?php echo $l->t('This will create a compressed file that contains your ownCloud account.');?>
	</p>
	<button id="exportbtn">Export<img style="display: none;" class="loading" src="<?php echo OCP\Util::linkTo('core', 'img/loading.gif'); ?>" /></button>
</fieldset>
