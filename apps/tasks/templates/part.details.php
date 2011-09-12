<?php if(isset($_['details']->SUMMARY)): ?>
<table>
<?php echo $this->inc('part.property', array('label' => $l->t('Summary'), 'property' => $_['details']->SUMMARY)); ?>
</table>
<form>
	<input type="button" id="tasks_delete" value="<?php echo $l->t('Delete');?>">
	<input type="button" id="tasks_edit" value="<?php echo $l->t('Edit');?>">
</form>
<?php endif ?>
