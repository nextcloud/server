<form id="tasks_addtaskform">
	<?php if(count($_['calendars'])==1): ?>
		<input type="hidden" name="id" value="<?php echo $_['calendars'][0]['id']; ?>">
	<?php else: ?>
		<label for="id"><?php echo $l->t('Calendar'); ?></label>
		<select name="id" size="1">
			<?php foreach($_['calendars'] as $calendar): ?>
				<option value="<?php echo $calendar['id']; ?>"><?php echo $calendar['displayname']; ?></option>
			<?php endforeach; ?>
		</select>
		<br>
	<?php endif; ?>
	<?php echo $this->inc('part.taskform'); ?>
	<input type="submit" name="submit" value="<?php echo $l->t('Create Task'); ?>">
</form>
