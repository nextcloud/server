<div id="event" title="<?php echo $l->t("Create a new event");?>">
	<form id="event_form">
<?php echo $this->inc("part.eventform"); ?>
	<div style="width: 100%;text-align: center;color: #FF1D1D;" id="errorbox"></div>
	<span id="actions">
		<input type="button" class="submit" style="float: left;" value="<?php echo $l->t("Submit");?>" onclick="validate_event_form('ajax/newevent.php');">
	</span>
	</form>
</div>
