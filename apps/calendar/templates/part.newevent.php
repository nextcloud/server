<div id="newevent" title="<?php echo $l->t("Create a new event");?>">
	<form id="event_form">
<?php echo $this->inc("part.eventform"); ?>
	<div style="width: 100%;text-align: center;color: #FF1D1D;" id="errorbox"></div>
	<span id="actions">
		<input type="button" class="submit" style="float: left;" value="<?php echo $l->t("Submit");?>" onclick="validate_event_form('ajax/newevent.php');">
	</span>
	</form>
</div>
<script type="text/javascript">
	$("#newevent").dialog({
		width : 500,
		close : function() {
					oc_cal_opendialog = 0;
					var lastchild = document.getElementById("body-user").lastChild
					while(lastchild.id != "lightbox"){
						document.getElementById("body-user").removeChild(lastchild);
						lastchild = document.getElementById("body-user").lastChild;
					}
			}
	});
	$( "#from" ).datepicker({
		dateFormat : 'dd-mm-yy'
	});
	$( "#to" ).datepicker({
		dateFormat : 'dd-mm-yy'
	});
	function lock_time() {
		if(document.getElementById("totime").disabled == true) {
			document.getElementById("fromtime").disabled = false;
			document.getElementById("totime").disabled = false;
			$("#fromtime").css('color', "#333");
			$("#totime").css('color', "#333");
		} else {
			document.getElementById("fromtime").disabled = true;
			document.getElementById("totime").disabled = true;
			$("#fromtime").css('color', "#A9A9A9");
			$("#totime").css('color', "#A9A9A9");
		}
	}
</script>
