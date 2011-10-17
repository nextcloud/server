/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

Calendar={
	space:' ',
	firstdayofweek: '',
	weekend: '',
	Date:{
		normal_year_cal: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
		leap_year_cal: [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
		calw:function() {
			var dayofweek = this.current.getDay();
			if(dayofweek == 0) {
				dayofweek = 7;
			}
			var calw = Math.floor((this.doy() - dayofweek) / 7) + 1;
			return calw;
		},

		doy:function() {
			var cal = this.getnumberofdays(this.current.getFullYear());
			var doy = 0;
			for(var i = 0; i < this.current.getMonth(); i++) {
				doy = doy + cal[i];
			}
			doy = doy + this.current.getDate();
			return doy;
		},

		getnumberofdays:function(year) {
			if(this.checkforleapyear(year) == true) {
				var cal = this.leap_year_cal;
			} else {
				var cal = this.normal_year_cal;
			}
			return cal;
		},

		checkforleapyear:function(year2check) {
			if((year2check / 600) == Math.floor(year2check / 400)) {
				return true;
			}
			if((year2check / 4) == Math.floor(year2check / 4)) {
				if((year2check / 100) == Math.floor(year2check / 100)) {
					return false;
				}
				return true;
			}
			return false;
		},

		current:new Date(),
		forward_day:function(){
			this.current.setDate(this.current.getDate()+1);
		},

		forward_week:function(){
			this.current.setDate(this.current.getDate()+7);
		},

		forward_month:function(){
			this.current.setMonth(this.current.getMonth()+1);
		},

		backward_day:function(){
			this.current.setDate(this.current.getDate()-1);
		},

		backward_week:function(){
			this.current.setDate(this.current.getDate()-7);
		},

		backward_month:function(){
			this.current.setMonth(this.current.getMonth()-1);
		},

	},
	UI:{
		weekdays: '',
		formatDayShort:function(day){
			if (typeof(day) == 'undefined'){
				day = Calendar.Date.current.getDay();
			}
			return this.dayshort[day];
		},
		formatDayLong:function(day){
			if (typeof(day) == 'undefined'){
				day = Calendar.Date.current.getDay();
			}
			return this.daylong[day];
		},
		formatMonthShort:function(month){
			if (typeof(month) == 'undefined'){
				month = Calendar.Date.current.getMonth();
			}
			return this.monthshort[month];
		},
		formatMonthLong:function(month){
			if (typeof(month) == 'undefined'){
				month = Calendar.Date.current.getMonth();
			}
			return this.monthlong[month];
		},
		formatDate:function(date){
			return date[0] + '-' + date[1] + '-' + date[2];
		},
		formatTime:function(date){
			return date[3] + ':' + date[4];
		},
		updateView:function() {
		},
		currentview:'none',
		setCurrentView:function(view){
			if (view == this.currentview){
				return;
			}
			$('#'+this.currentview).hide();
			$('#'+this.currentview + "_radio").removeClass('active');
			this.currentview = view;
			//sending ajax request on every change view
			$("#sysbox").load(OC.filePath('calendar', 'ajax', 'changeview.php') + "?v="+view);
			//not necessary to check whether the response is true or not
			switch(view) {
				case "onedayview":
					this.current = this.OneDay;
					break;
				case "oneweekview":
					this.current = this.OneWeek;
					break;
				case "fourweeksview":
					this.current = this.FourWeeks;
					break;
				case "onemonthview":
					this.current = this.OneMonth;
					break;
				case "listview":
					this.current = this.List;
					break;
				default:
					alert('Unknown view:'+view);
					break;
			}
			$(document).ready(function() {
				$('#'+Calendar.UI.currentview).show();
				$('#'+Calendar.UI.currentview + "_radio")
					.addClass('active');
				Calendar.UI.updateView()
			});
		},
		drageventid: '',
		updateDate:function(direction){
			if(direction == 'forward' && this.current.forward) {
				this.current.forward();
				if(Calendar.Date.current.getMonth() == 11){
					this.loadEvents(Calendar.Date.current.getFullYear() + 1);
				}
				this.updateView();
			}
			if(direction == 'backward' && this.current.backward) {
				this.current.backward();
				if(Calendar.Date.current.getMonth() == 0){
					this.loadEvents(Calendar.Date.current.getFullYear() - 1);
				}
				this.updateView();
			}
		},
		events:[],
		loadEvents:function(year){
		},
		getEventsForDate:function(date){
			var day = date.getDate();
			var month = date.getMonth();
			var year = date.getFullYear();
			if( typeof (this.events[year]) == "undefined") {
				this.loadEvents(year);
				return false;
			}
			if( typeof (this.events[year][month]) == "undefined") {
				return false;
			}
			if( typeof (this.events[year][month][day]) == "undefined") {
				return false;
			}
			return this.events[year][month][day];
		},
		createEventsForDate:function(date, week){
			events = this.getEventsForDate(date);
			if (!events) {
				return;
			}
			var weekday = (date.getDay()+7-Calendar.firstdayofweek)%7;
			if( typeof (events["allday"]) != "undefined") {
				var eventnumber = 1;
				var eventcontainer = this.current.getEventContainer(week, weekday, "allday");
				while( typeof (events["allday"][eventnumber]) != "undefined") {
					this.addEventLabel(eventcontainer, events['allday'][eventnumber]);
					eventnumber++;
				}
			}
			for(var time = 0; time <= 23; time++) {
				if( typeof (events[time]) != "undefined") {
					var eventnumber = 1;
					var eventcontainer = this.current.getEventContainer(week, weekday, time);
					while( typeof (events[time][eventnumber]) != "undefined") {
						this.addEventLabel(eventcontainer, events[time][eventnumber]);
						eventnumber++;
					}
				}
			}
		},
		addEventLabel:function(eventcontainer, event){
			var event_holder = this.current.createEventLabel(event)
				.addClass('event')
				.data('event_info', event)
				.hover(this.createEventPopup,
				       this.hideEventPopup)
				.draggable({
					drag: function() {
						Calendar.UI.drageventid = event.id;
					}
				})
				.click(this.editEvent);
			var color = this.calendars[event['calendarid']]['color'];
			if (color){
				event_holder.css('background-color', color)
					.addClass('colored');
			}
			eventcontainer.append(event_holder);
		},
		startEventDialog:function(){
			Calendar.UI.lockTime();
			$( "#from" ).datepicker({
				dateFormat : 'dd-mm-yy'
			});
			$( "#to" ).datepicker({
				dateFormat : 'dd-mm-yy'
			});
			$('#category').multiselect({
					header: false,
					noneSelectedText: $('#category').attr('title'),
					selectedList: 2,
					minWidth:'auto',
					classes: 'category',
			});
			$('#event').dialog({
				width : 500,
				close : function(event, ui) {
					$(this).dialog('destroy').remove();
				}
			});
		},
		newEvent:function(selector, time){
			var date_info = $(selector).data('date_info');
			var dayofmonth = date_info.getDate();
			var month = date_info.getMonth();
			var year = date_info.getFullYear();
			if(dayofmonth <= 9){
				dayofmonth = '0' + dayofmonth;
			}
			month++;
			if(month <= 9){
				month = '0' + month;
			}
			var date = String(dayofmonth) + String(month) + String(year);
			if($('#event').dialog('isOpen') == true){
				// TODO: save event
				$('#event').dialog('destroy').remove();
			}else{
				$('#dialog_holder').load(OC.filePath('calendar', 'ajax', 'neweventform.php') + '?d=' + date + '&t=' + time, Calendar.UI.startEventDialog);
			}
		},
		editEvent:function(event){
			event.stopPropagation();
			var event_data = $(this).data('event_info');
			var id = event_data.id;
			if($('#event').dialog('isOpen') == true){
				// TODO: save event
				$('#event').dialog('destroy').remove();
			}else{
				$('#dialog_holder').load(OC.filePath('calendar', 'ajax', 'editeventform.php') + '?id=' + id, Calendar.UI.startEventDialog);
			}
		},
		submitDeleteEventForm:function(url){
			var post = $( "#event_form" ).serialize();
			$("#errorbox").empty();
			$.post(url, post, function(data){
					if(data.status == 'success'){
						$('#event').dialog('destroy').remove();
						Calendar.UI.loadEvents();
					} else {
						$("#errorbox").html("Deletion failed");
					}

			}, "json");
		},
		validateEventForm:function(url){
			var post = $( "#event_form" ).serialize();
			$("#errorbox").empty();
			$.post(url, post,
				function(data){
					if(data.status == "error"){
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
						$("#errorbox").html(output);
					} else
					if(data.status == 'success'){
						$('#event').dialog('destroy').remove();
						Calendar.UI.loadEvents();
					}
				},"json");
		},
		moveevent:function(eventid, newstartdate){
			$.post(OC.filePath('calendar', 'ajax', 'moveevent.php'), { id: eventid, newdate: newstartdate},
			function(data) {
				console.log("Event moved successfully");
			});
		},
		showadvancedoptions:function(){
			$("#advanced_options").css("display", "block");
			$("#advanced_options_button").css("display", "none");
		},
		createEventPopup:function(e){
			var popup = $(this).data('popup');
			if (!popup){
				var event = $(this).data('event_info');
				popup = $(document.createElement('div'));
				$(this).data('popup', popup).append(popup);
				popup.addClass('popup')
				popup.addClass('event_popup')
					.html(Calendar.UI.getEventPopupText(event));
			}
			popup.css('left', -(popup.width() - $(this).width())/2)
				.show();
		},
		hideEventPopup:function(){
			$(this).data('popup').hide();
		},
		getEventPopupText:function(event){
			var startdate = this.formatDate(event.startdate)
			var starttime = this.formatTime(event.startdate)
			var enddate = this.formatDate(event.enddate)
			var endtime = this.formatTime(event.enddate)
			if (event.allday){
				var timespan = startdate;
				if (event.startdate[2] != parseInt(event.enddate[2])-1){
					timespan += ' - ' + enddate;
				}
			}else{
				var start = startdate + ' ' + starttime;
				if (startdate == enddate){
					var end = endtime;
				}else{
					var end = enddate + ' ' + endtime;
				}
				var timespan = start + ' - ' + end;
			}
			return '<span class="timespan">' + timespan + '</span>'
				+ ' '
				+ '<span class="summary">' + event.description + '</span>';
		},
		addDateInfo:function(selector, date){
			$(selector).data('date_info', date);
		},
		switch2Today:function(){
			Calendar.Date.current = new Date();
			Calendar.UI.updateView();
		},
		lockTime:function(){
			if($('#allday_checkbox').is(':checked')) {
				$("#fromtime").attr('disabled', true)
					.addClass('disabled');
				$("#totime").attr('disabled', true)
					.addClass('disabled');
			} else {
				$("#fromtime").attr('disabled', false)
					.removeClass('disabled');
				$("#totime").attr('disabled', false)
					.removeClass('disabled');
			}
		},
		showCalDAVUrl:function(username, calname){
			$('#caldav_url').val(totalurl + '/' + username + '/' + calname);
			$('#caldav_url').show();
			$("#caldav_url_close").show();
		},
		deleteCalendar:function(calid){
			var check = confirm("Do you really want to delete this calendar?");
			if(check == false){
				return false;
			}else{
				$.post(OC.filePath('calendar', 'ajax', 'deletecalendar.php'), { calendarid: calid},
				  function(data) {
					Calendar.UI.loadEvents();
					$('#choosecalendar_dialog').dialog('destroy').remove();
					Calendar.UI.Calendar.overview();
				  });
			}
		},
		initscroll:function(){ 
			if(window.addEventListener)
				document.addEventListener('DOMMouseScroll', Calendar.UI.scrollcalendar);
			//}else{
				document.onmousewheel = Calendar.UI.scrollcalendar;
			//}
		},
		scrollcalendar:function(event){
			var direction;
			if(event.detail){
				if(event.detail < 0){
					direction = "top";
				}else{
					direction = "down";
				}
			}
			if (event.wheelDelta){
				if(event.wheelDelta > 0){
					direction = "top";
				}else{
					direction = "down";
				}
			}
			if(Calendar.UI.currentview == "onemonthview"){
				if(direction == "down"){
					Calendar.UI.updateDate("forward");
				}else{
					Calendar.UI.updateDate("backward");
				}
			}else if(Calendar.UI.currentview == "oneweekview"){
				if(direction == "down"){
					Calendar.UI.updateDate("forward");
				}else{
					Calendar.UI.updateDate("backward");
				}
			}
		},
		Calendar:{
			overview:function(){
				if($('#choosecalendar_dialog').dialog('isOpen') == true){
					$('#choosecalendar_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(OC.filePath('calendar', 'ajax', 'choosecalendar.php'), function(){
						$('#choosecalendar_dialog').dialog({
							width : 600,
							close : function(event, ui) {
								$(this).dialog('destroy').remove();
							}
						});
					});
				}
			},
			activation:function(checkbox, calendarid)
			{
				$.post(OC.filePath('calendar', 'ajax', 'activation.php'), { calendarid: calendarid, active: checkbox.checked?1:0 },
				  function(data) {
					checkbox.checked = data == 1;
					Calendar.UI.loadEvents();
				  });
			},
			newCalendar:function(object){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('calendar', 'ajax', 'newcalendar.php'),
						function(){Calendar.UI.Calendar.colorPicker(this)});
				$(object).closest('tr').after(tr).hide();
			},
			edit:function(object, calendarid){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('calendar', 'ajax', 'editcalendar.php') + "?calendarid="+calendarid,
						function(){Calendar.UI.Calendar.colorPicker(this)});
				$(object).closest('tr').after(tr).hide();
			},
			colorPicker:function(container){
				// based on jquery-colorpicker at jquery.webspirited.com
				var obj = $('.colorpicker', container);
				var picker = $('<div class="calendar-colorpicker"></div>');
				//build an array of colors
				var colors = {};
				$(obj).children('option').each(function(i, elm) {
					colors[i] = {};
					colors[i].color = $(elm).val();
					colors[i].label = $(elm).text();
				});
				for (var i in colors) {
					picker.append('<span class="calendar-colorpicker-color ' + (colors[i].color == $(obj).children(":selected").val() ? ' active' : '') + '" rel="' + colors[i].label + '" style="background-color: #' + colors[i].color + ';"></span>');
				}
				picker.delegate(".calendar-colorpicker-color", "click", function() {
					$(obj).val($(this).attr('rel'));
					$(obj).change();
					picker.children('.calendar-colorpicker-color.active').removeClass('active');
					$(this).addClass('active');
				});
				$(obj).after(picker);
				$(obj).css({
					position: 'absolute',
					left: -10000
				});
			},
			submit:function(button, calendarid){
				var displayname = $("#displayname_"+calendarid).val();
				var active = $("#edit_active_"+calendarid+":checked").length;
				var description = $("#description_"+calendarid).val();
				var calendarcolor = $("#calendarcolor_"+calendarid).val();

				var url;
				if (calendarid == 'new'){
					url = "ajax/createcalendar.php";
				}else{
					url = "ajax/updatecalendar.php";
				}
				$.post(url, { id: calendarid, name: displayname, active: active, description: description, color: calendarcolor },
					function(data){
						if(data.error == "true"){
						}else{
							$(button).closest('tr').prev().html(data.data).show().next().remove();
							Calendar.UI.loadEvents();
						}
					}, 'json');
			},
			cancel:function(button, calendarid){
				$(button).closest('tr').prev().show().next().remove();
			},
		},
		OneWeek:{
			forward:function(){
				Calendar.Date.forward_week();
			},
			backward:function(){
				Calendar.Date.backward_week();
			},
			renderCal:function(){
				$("#datecontrol_date").val(Calendar.UI.cw_label + ": " + Calendar.Date.calw());
			},
			createEventLabel:function(event){
				var time = '';
				if (!event['allday']){
					time = '<strong>' + Calendar.UI.formatTime(event['startdate']) + ' - ' + Calendar.UI.formatTime(event['enddate']) + '</strong> ';
				}
				return $(document.createElement('p'))
					.html(time + event['description'])
			},
		},
		OneMonth:{
			forward:function(){
				Calendar.Date.forward_month();
			},
			backward:function(){
				Calendar.Date.backward_month();
			},
			renderCal:function(){
				$("#datecontrol_date").val(Calendar.UI.formatMonthLong() + Calendar.space + Calendar.Date.current.getFullYear());
			},
			createEventLabel:function(event){
				var time = '';
				if (!event['allday']){
					time = '<strong>' + Calendar.UI.formatTime(event['startdate']) + '</strong> ';
				}
				return $(document.createElement('p'))
					.html(time + event['description'])
			},
		},
		List:{
			removeEvents:function(){
				this.eventContainer = $('#listview #events').empty();
				this.startdate = new Date();
				this.enddate = new Date();
				this.enddate.setDate(this.enddate.getDate());
			},
			renderCal:function(){
				var today = new Date();
				$('#datecontrol_date').val(this.formatDate(Calendar.Date.current));
			},
			showEvents:function(){
				this.renderMoreBefore();
				this.renderMoreAfter();
			},
			formatDate:function(date){
				return Calendar.UI.formatDayShort(date.getDay())
					+ Calendar.space
					+ date.getDate()
					+ Calendar.space
					+ Calendar.UI.formatMonthShort(date.getMonth())
					+ Calendar.space
					+ date.getFullYear();
			},
			createDay:function(date) {
				return $(document.createElement('div'))
					.addClass('day')
					.html(this.formatDate(date));
			},
			renderMoreBefore:function(){
				var date = Calendar.UI.List.startdate;
				for(var i = 0; i <= 13; i++) {
					if (Calendar.UI.getEventsForDate(date)) {
						Calendar.UI.List.dayContainer=Calendar.UI.List.createDay(date);
						Calendar.UI.createEventsForDate(date, 0);
						Calendar.UI.List.eventContainer.prepend(Calendar.UI.List.dayContainer);
					}
					date.setDate(date.getDate()-1);
				}
				var start = Calendar.UI.List.formatDate(date);
				$('#listview #more_before').html(String(Calendar.UI.more_before).replace('{startdate}', start));
			},
			renderMoreAfter:function(){
				var date = Calendar.UI.List.enddate;
				for(var i = 0; i <= 13; i++) {
					if (Calendar.UI.getEventsForDate(date)) {
						Calendar.UI.List.dayContainer=Calendar.UI.List.createDay(date);
						Calendar.UI.createEventsForDate(date, 0);
						Calendar.UI.List.eventContainer.append(Calendar.UI.List.dayContainer);
					}
					date.setDate(date.getDate()+1);
				}
				var end = Calendar.UI.List.formatDate(date);
				$('#listview #more_after').html(String(Calendar.UI.more_after).replace('{enddate}', end));
			},
			getEventContainer:function(week, weekday, when){
				return this.dayContainer;
			},
			createEventLabel:function(event){
				var time = '';
				if (!event['allday']){
					time = Calendar.UI.formatTime(event['startdate']) + ' - ' + Calendar.UI.formatTime(event['enddate']) + ' ';
				}
				return $(document.createElement('p'))
					.html(time + event['description'])
			},
		}
	}
}
$(document).ready(function(){
	$('#listview #more_before').click(Calendar.UI.List.renderMoreBefore);
	$('#listview #more_after').click(Calendar.UI.List.renderMoreAfter);
	Calendar.UI.initscroll();
});
//event vars
Calendar.UI.loadEvents();
