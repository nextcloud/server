<form id="versions">
	<fieldset class="personalblock">
		<legend>
			<strong>Versions</strong><!-- translate using echo $l->t('foo'); -->
		</legend>
		<p>This will delete all existing backup versions of your files</p><!-- translate using echo $l->t('foo'); -->
		<button id="expireAllBtn">Expire all versions<img style="display: none;" class="expireAllLoading" src="<?php echo OCP\Util::imagePath('core', 'loading.gif'); ?>" /></button>
	</fieldset>
</form>
