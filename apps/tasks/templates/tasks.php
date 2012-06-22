<div id="controls">
	<input type="text" id="tasks_newtask">
	<input type="button" id="tasks_addtask" value="<?php echo $l->t('Add Task'); ?>">
	<input type="button" id="tasks_order_due" value="<?php echo $l->t('Order Due'); ?>">
	<input type="button" id="tasks_order_category" value="<?php echo $l->t('Order List'); ?>">
	<input type="button" id="tasks_order_complete" value="<?php echo $l->t('Order Complete'); ?>">
	<input type="button" id="tasks_order_location" value="<?php echo $l->t('Order Location'); ?>">
	<input type="button" id="tasks_order_prio" value="<?php echo $l->t('Order Priority'); ?>">
	<input type="button" id="tasks_order_label" value="<?php echo $l->t('Order Label'); ?>">
</div>
<div id="tasks_lists" class="leftcontent">
	<div class="all">All</div>
	<div class="done">Done</div>
</div>
<div id="tasks_list" class="rightcontent">
<p class="loading"><?php echo $l->t('Loading tasks...') ?></p>
</div>
<p id="task_actions_template" class="task_actions">
	<!-- span class="task_star">
		<img title="<?php echo $l->t('Important') ?>" src="<?php echo OCP\image_path('core', 'actions/add.svg') ?>" class="svg"><?php echo $l->t('Important') ?>
	</span -->
	<span class="task_more">
		<img title="<?php echo $l->t('More') ?>" src="<?php echo OCP\image_path('core', 'actions/triangle-s.svg') ?>" class="svg"><?php echo $l->t('More') ?>
	</span>
	<span class="task_less">
		<img title="<?php echo $l->t('Less') ?>" src="<?php echo OCP\image_path('core', 'actions/triangle-n.svg') ?>" class="svg"><?php echo $l->t('Less') ?>
	</span>
	<span class="task_delete">
		<img title="<?php echo $l->t('Delete') ?>" src="<?php echo OCP\image_path('core', 'actions/delete.svg') ?>" class="svg"><?php echo $l->t('Delete') ?>
	</span>
</p>
<script type='text/javascript'>
var categories = <?php echo json_encode($_['categories']); ?>;
</script>
