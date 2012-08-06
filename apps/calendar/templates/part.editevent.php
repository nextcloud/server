<div id="event" title="<?php echo $l->t("Edit an event");?>">
	<form id="event_form">
		<input type="hidden" name="id" value="<?php echo $_['eventid'] ?>">
		<input type="hidden" name="lastmodified" value="<?php echo $_['lastmodified'] ?>">
<?php echo $this->inc("part.eventform"); ?>
	<div style="width: 100%;text-align: center;color: #FF1D1D;" id="errorbox"></div>
	<span id="actions">
		<input type="button" class="submit" style="float: left;" value="<?php echo $l->t("Submit");?>" onclick="Calendar.UI.validateEventForm('<?php echo OCP\Util::linkTo('calendar', 'ajax/event/edit.php') ?>');">
		<input type="button" class="submit" style="float: left;" name="delete" value="<?php echo $l->t("Delete");?>" onclick="Calendar.UI.submitDeleteEventForm('<?php echo OCP\Util::linkTo('calendar', 'ajax/event/delete.php') ?>');">
		<input type="button" class="submit" style="float: right;" name="export" value="<?php echo $l->t("Export");?>" onclick="window.location='<?php echo OCP\Util::linkTo('calendar', 'export.php') ?>?eventid=<?php echo $_['eventid'] ?>';">
	</span>
	</form>
</div>
