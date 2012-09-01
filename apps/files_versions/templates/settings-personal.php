<form id="versions">
	<fieldset class="personalblock">
		<legend>
			<strong><?php echo $l->t('Versions'); ?></strong>
		</legend>
		<p>
            <?php echo $l->t('This will delete all existing backup versions of your files'); ?>
        </p>
		<button id="expireAllBtn">
            <?php echo $l->t('Expire all versions'); ?>
            <img style="display: none;" class="expireAllLoading" src="<?php echo OCP\Util::imagePath('core', 'loading.gif'); ?>" />
        </button>
	</fieldset>
</form>
