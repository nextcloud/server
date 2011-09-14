<div id="editevent" title="<?php echo $l->t("Edit an event");?>">
<?php echo $this->inc("part.eventform"); ?>
	<span id="editevent_actions">
		<input type="button" style="float: left;" value="<?php echo $l->t("Submit");?>">
	</span>
</div>
<script type="text/javascript">
	$( "#editevent" ).dialog({
		width : 500,
		close : function() {
					oc_cal_opendialog = 0;
					var lastchild = document.getElementById("body-user").lastChild
					while(lastchild.id != "lightbox"){
						document.getElementById("body-user").removeChild(lastchild);
						lastchild = document.getElementById("body-user").lastChild;
					}
			},
		open : function(){alert("Doesn't work yet.");}
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
			document.getElementById("fromtime").style.color = "#333";
			document.getElementById("totime").style.color = "#333";
		} else {
			document.getElementById("fromtime").disabled = true;
			document.getElementById("totime").disabled = true;
			document.getElementById("fromtime").style.color = "#A9A9A9";
			document.getElementById("totime").style.color = "#A9A9A9";
		}
	}
</script>
