<div id="controls">
	<form>
		<input type="button" id="tasks_newtask" value="<?php echo $l->t('Add Task'); ?>">
	</form>
</div>
<div id="tasks">
<p><?php echo $l->t('Loading tasks...') ?></p>
</div>
<div id="task_details">
</div>
<p id="task_actions_template" class="task_actions">
	<span class="task_delete">
		<img title="Delete" src="<?php echo image_path('core', 'actions/delete.svg') ?>" class="svg">
	</span>
	&nbsp;<span class="task_edit">
		<img title="Edit" src="<?php echo image_path('core', 'actions/rename.svg') ?>" class="svg">
	</span>
</p>
