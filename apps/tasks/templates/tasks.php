<div id="controls">
	<input type="button" id="tasks_newtask" value="<?php echo $l->t('Add Task'); ?>">
	<input type="button" id="tasks_order_due" value="<?php echo $l->t('Order Due'); ?>">
	<input type="button" id="tasks_order_category" value="<?php echo $l->t('Order Category'); ?>">
	<input type="button" id="tasks_order_complete" value="<?php echo $l->t('Order Complete'); ?>">
	<input type="button" id="tasks_order_location" value="<?php echo $l->t('Order Location'); ?>">
	<input type="button" id="tasks_order_prio" value="<?php echo $l->t('Order Priority'); ?>">
	<input type="button" id="tasks_order_label" value="<?php echo $l->t('Order Label'); ?>">
</div>
<div id="tasks">
<p class="loading"><?php echo $l->t('Loading tasks...') ?></p>
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
