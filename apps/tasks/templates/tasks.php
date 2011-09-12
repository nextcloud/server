<div id="controls">
	<form>
		<input type="button" id="tasks_newtask" value="<?php echo $l->t('Add Task'); ?>">
	</form>
</div>
<div id="tasks" class="leftcontent">
	<ul>
		<?php echo $this->inc("part.tasks"); ?>
	</ul>
</div>
<div id="task_details" class="rightcontent" data-id="<?php echo $_['id']; ?>">
	<?php echo $this->inc("part.details"); ?>
</div>
