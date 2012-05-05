<?php if(isset($_['details']->SUMMARY)): ?>
<table>
<?php
echo $this->inc('part.property', array('label' => $l->t('Summary'), 'property' => $_['details']->SUMMARY));
if(isset($_['details']->LOCATION)):
	echo $this->inc('part.property', array('label' => $l->t('Location'), 'property' => $_['details']->LOCATION));
endif;
if(isset($_['details']->CATEGORIES)):
	echo $this->inc('part.property', array('label' => $l->t('Categories'), 'property' => $_['details']->CATEGORIES));
endif;
if(isset($_['details']->DUE)):
	echo $this->inc('part.property', array('label' => $l->t('Due'), 'property' => $_['details']->DUE[0]));
endif;
if(isset($_['details']->PRIORITY)):
	echo $this->inc('part.property', array('label' => $l->t('Priority'), 'property' => $_['details']->PRIORITY[0], 'options' => $_['priority_options']));
endif;
if($_['details']->__isset('PERCENT-COMPLETE') || isset($_['details']->COMPLETED)):
?>
<tr>
	<th>
		<?php echo $l->t('Complete') ?>
	</th>
	<td>
<?php if($_['details']->__isset('PERCENT-COMPLETE')):
		echo $_['details']->__get('PERCENT-COMPLETE')->value.' % ';
	endif;
	if(isset($_['details']->COMPLETED)):
		echo $l->t('on '). $l->l('datetime', $_['details']->COMPLETED[0]->getDateTime());
	endif;
	echo '</tr>';
endif;
if(isset($_['details']->DESCRIPTION)):
	echo $this->inc('part.property', array('label' => $l->t('Description'), 'property' => $_['details']->DESCRIPTION));
endif; ?>
</table>
<form>
	<input type="button" id="tasks_delete" value="<?php echo $l->t('Delete');?>">
	<input type="button" id="tasks_edit" value="<?php echo $l->t('Edit');?>">
</form>
<?php else: ?>
<?php //var_dump($_['details']); ?>
<?php endif ?>
