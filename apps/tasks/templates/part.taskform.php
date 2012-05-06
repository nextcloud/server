	<label for="summary"><?php echo $l->t('Summary'); ?></label>
	<input type="text" id="summary" name="summary" placeholder="<?php echo $l->t('Summary of the task');?>" value="<?php echo isset($_['details']->SUMMARY) ? $_['details']->SUMMARY[0]->value : '' ?>">
	<br>
	<label for="location"><?php echo $l->t('Location'); ?></label>
	<input type="text" id="location" name="location" placeholder="<?php echo $l->t('Location of the task');?>" value="<?php echo isset($_['details']->LOCATION) ? $_['details']->LOCATION[0]->value : '' ?>">
	<br>
	<label for="categories"><?php echo $l->t('Categories'); ?></label>
	<input id="categories" name="categories" type="text" placeholder="<?php echo $l->t('Separate categories with commas'); ?>" value="<?php echo isset($_['categories']) ? htmlspecialchars($_['categories']) : '' ?>">
	<a class="action edit" onclick="$(this).tipsy('hide');OCCategories.edit();" title="<?php echo $l->t('Edit categories'); ?>"><img alt="<?php echo $l->t('Edit categories'); ?>" src="<?php echo OCP\image_path('core','actions/rename.svg')?>" class="svg action" style="width: 16px; height: 16px;"></a>
	<br>
	<label for="due"><?php echo $l->t('Due'); ?></label>
	<input type="text" id="due" name="due" placeholder="<?php echo $l->t('Due date') ?>" value="<?php echo isset($_['details']->DUE) ? $l->l('datetime', $_['details']->DUE[0]->getDateTime()) : '' ?>">
	<br>
	<select name="percent_complete" id="percent_complete">
		<?php
		foreach($_['percent_options'] as $percent){
			echo '<option value="' . $percent . '"' . (($_['details']->__get('PERCENT-COMPLETE') && $percent == $_['details']->__get('PERCENT-COMPLETE')->value) ? ' selected="selected"' : '') . '>' . $percent . ' %</option>';
		}
		?>
	</select>
	<label for="percent_complete"><?php echo $l->t('Complete'); ?></label>
	<span id="complete"<?php echo ($_['details']->__get('PERCENT-COMPLETE') && $_['details']->__get('PERCENT-COMPLETE')->value == 100) ? '' : ' style="display:none;"' ?>><label for="completed"><?php echo $l->t('completed on'); ?></label>
	<input type="text" id="completed" name="completed" value="<?php echo isset($_['details']->COMPLETED) ? $l->l('datetime', $_['details']->COMPLETED[0]->getDateTime()) : '' ?>"></span>
	<br>
	<label for="priority"><?php echo $l->t('Priority'); ?></label>
	<select name="priority">
		<?php
		foreach($_['priority_options'] as $priority => $label){
			echo '<option value="' . $priority . '"' . ((isset($_['details']->PRIORITY) && $priority == $_['details']->PRIORITY->value) ? ' selected="selected"' : '') . '>' . $label . '</option>';
		}
		?>
	</select>
	<br>
	<label for="description"><?php echo $l->t('Description'); ?></label><br>
	<textarea placeholder="<?php echo $l->t('Description of the task');?>" name="description"><?php echo isset($_['details']->DESCRIPTION) ? $_['details']->DESCRIPTION[0]->value : '' ?></textarea>
	<br>
