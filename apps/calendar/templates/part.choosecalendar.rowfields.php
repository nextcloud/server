<td width="20px">
  <input type="checkbox" id="active_<?php echo $_['calendar']['id'] ?>" onclick="Calendar.UI.Calendar.activation(this,<?php echo $_['calendar']['id'] ?>)"<?php echo $_['calendar']['active'] ? ' checked="checked"' : '' ?>>
</td>
<td id="<?php echo OCP\USER::getUser() ?>_<?php echo $_['calendar']['id'] ?>">
  <label for="active_<?php echo $_['calendar']['id'] ?>"><?php echo $_['calendar']['displayname'] ?></label>
</td>
<td width="20px">
  <a href="#" onclick="Calendar.UI.Share.dropdown('<?php echo OCP\USER::getUser() ?>', <?php echo $_['calendar']['id'] ?>);" title="<?php echo $l->t('Share Calendar') ?>" class="action"><img class="svg action" src="<?php echo (!$_['shared']) ? OCP\Util::imagePath('core', 'actions/share.svg') : OCP\Util::imagePath('core', 'actions/shared.svg') ?>"></a>
</td>
<td width="20px">
  <a href="#" onclick="Calendar.UI.showCalDAVUrl('<?php echo OCP\USER::getUser() ?>', '<?php echo rawurlencode(html_entity_decode($_['calendar']['uri'], ENT_QUOTES, 'UTF-8')) ?>');" title="<?php echo $l->t('CalDav Link') ?>" class="action"><img class="svg action" src="<?php echo OCP\Util::imagePath('core', 'actions/public.svg') ?>"></a>
</td>
<td width="20px">
  <a href="<?php echo OCP\Util::linkTo('calendar', 'export.php') . '?calid=' . $_['calendar']['id'] ?>" title="<?php echo $l->t('Download') ?>" class="action"><img class="svg action" src="<?php echo OCP\Util::imagePath('core', 'actions/download.svg') ?>"></a>
</td>
<td width="20px">
  <a href="#" onclick="Calendar.UI.Calendar.edit(this, <?php echo $_['calendar']['id'] ?>);" title="<?php echo $l->t('Edit') ?>" class="action"><img class="svg action" src="<?php echo OCP\Util::imagePath('core', 'actions/rename.svg') ?>"></a>
</td>
<td width="20px">
  <a href="#" onclick="Calendar.UI.Calendar.deleteCalendar(<?php echo $_['calendar']['id'] ?>);" title="<?php echo $l->t('Delete') ?>" class="action"><img class="svg action" src="<?php echo OCP\Util::imagePath('core', 'actions/delete.svg') ?>"></a>
</td>
