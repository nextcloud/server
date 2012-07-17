<form id="resharing">
	<fieldset class="personalblock">
	<p><input type="checkbox" name="allowResharing" id="allowResharing" value="1" <?php if ($_['allowResharing'] == 'yes') echo ' checked="checked"'; ?> /> <label for="allowResharing"><?php echo $l->t('Enable Resharing'); ?></label> <br/>
	<em><?php echo $l->t('Allow users to reshare files they don\'t own');?></em></p>
	<p><input type="checkbox" name="allowSharingWithEveryone" id="allowSharingWithEveryone" value="1" <?php if ($_['allowSharingWithEveryone'] == 'yes') echo ' checked="checked"'; ?> /> <label for="allowSharingWithEveryone"><?php echo $l->t('Enable sharing with everyone'); ?></label> <br/>
	<em><?php echo $l->t('Allow users to share files with everyone');?></em></p>
	</fieldset>
</form>