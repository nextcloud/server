<form id="resharing">
	<fieldset class="personalblock">
	<input type="checkbox" name="allowResharing" id="allowResharing" value="1" <?php if ($_['allowResharing'] == 'yes') echo ' checked="checked"'; ?> /> <label for="allowResharing"><?php echo $l->t('Enable Resharing'); ?></label> <br/>
	<em><?php echo $l->t('Allow users to reshare files they don\'t own');?></em>
	</fieldset>
</form>