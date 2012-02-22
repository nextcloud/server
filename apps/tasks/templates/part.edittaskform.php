<form id="tasks_edittaskform">
	<input type="hidden" name="id" value="<?php echo $_['id']; ?>">
	<?php echo $this->inc('part.taskform'); ?>
	<input type="submit" name="submit" value="<?php echo $l->t('Update Task'); ?>">
</form>
