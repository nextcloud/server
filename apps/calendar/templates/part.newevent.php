<div id="newevent" title="<?php echo $l->t("Create a new event");?>">
	<form>
<?php echo $this->inc("part.eventform"); ?>
	<div style="width: 100%;text-align: center;color: #FF1D1D;" id="errorbox"></div>
	<span id="newcalendar_actions">
		<input type="button" class="submit" style="float: left;" value="<?php echo $l->t("Submit");?>" onclick="validate_newevent_form();">
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
			document.getElementById("fromtime").style.color = "#333";
			document.getElementById("totime").style.color = "#333";
		} else {
			document.getElementById("fromtime").disabled = true;
			document.getElementById("totime").disabled = true;
			document.getElementById("fromtime").style.color = "#A9A9A9";
			document.getElementById("totime").style.color = "#A9A9A9";
		}
	}
	function validate_newevent_form(){
		var newevent_title = document.getElementById("newevent_title").value;
		var newevent_location = document.getElementById("newevent_location").value;
		var newevent_cat = document.getElementById("formcategorie_select").options[document.getElementById("formcategorie_select").selectedIndex].value;
		var newevent_cal = document.getElementById("formcalendar_select").options[document.getElementById("formcalendar_select").selectedIndex].id;
		var newevent_allday = document.getElementById("newcalendar_allday_checkbox").checked;
		var newevent_from = document.getElementById("from").value;
		var newevent_fromtime = document.getElementById("fromtime").value;
		var newevent_to = document.getElementById("to").value;
		var newevent_totime = document.getElementById("totime").value;
		var newevent_description = document.getElementById("description").value;
		$.post("ajax/newevent.php", { title: newevent_title, location: newevent_location, cat: newevent_cat, cal: newevent_cal, allday: newevent_allday, from: newevent_from, fromtime: newevent_fromtime, to: newevent_to, totime: newevent_totime, description: newevent_description},
			function(data){
				if(data.error == "true"){
					document.getElementById("errorbox").innerHTML = "";
					var output = "Missing fields: <br />";
					if(data.title == "true"){
						output = output + "Title<br />";
					}
					if(data.cal == "true"){
						output = output + "Calendar<br />";
					}
					if(data.from == "true"){
						output = output + "From Date<br />";
					}
					if(data.fromtime == "true"){
						output = output + "From Time<br />";
					}
					if(data.to == "true"){
						output = output + "To Date<br />";
					}
					if(data.totime == "true"){
						output = output + "To Time<br />";
					}
					if(data.endbeforestart == "true"){
						output = "The event ends before it starts!";
					}
					if(data.dberror == "true"){
						output = "There was a database fail!";
					}
					document.getElementById("errorbox").innerHTML = output;
				}else{
					window.location.reload();
				}
				if(data.success == true){
					location.reload();
				}
			},"json");
	}
</script>
