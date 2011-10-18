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
	UI:{
		refetchEvents:function() {
			$('#calendar_holder').fullCalendar('refetchEvents');
		},
		drageventid: '',
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
		newEvent:function(date, allDay, jsEvent, view){
			var dayofmonth = date.getDate();
			var month = date.getMonth();
			var year = date.getFullYear();
			var hour = date.getHours();
			var min = date.getMinutes();
			if(dayofmonth <= 9){
				dayofmonth = '0' + dayofmonth;
			}
			month++;
			if(month <= 9){
				month = '0' + month;
			}
			if(hour <= 9){
				hour = '0' + hour;
			}
			if(min <= 9){
				min = '0' + min;
			}
			var date = String(dayofmonth) + String(month) + String(year);
			if (allDay){
				var time = 'allday';
			}else{
				var time = String(hour) + String(min);
			}
			if($('#event').dialog('isOpen') == true){
				// TODO: save event
				$('#event').dialog('destroy').remove();
			}else{
				$('#dialog_holder').load(OC.filePath('calendar', 'ajax', 'neweventform.php') + '?d=' + date + '&t=' + time, Calendar.UI.startEventDialog);
			}
		},
		editEvent:function(calEvent, jsEvent, view){
			var id = calEvent.id;
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
						Calendar.UI.refetchEvents();
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
						Calendar.UI.refetchEvents();
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
					Calendar.UI.refetchEvents();
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
					Calendar.UI.refetchEvents();
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
							Calendar.UI.refetchEvents();
						}
					}, 'json');
			},
			cancel:function(button, calendarid){
				$(button).closest('tr').prev().show().next().remove();
			},
		},
		OneWeek:{
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
	$('#calendar_holder').fullCalendar({
		header: false,
		firstDay: 1,
		editable: true,
		defaultView: defaultView,
		timeFormat: {
			agenda: 'HH:mm{ - HH:mm}',
			'': 'HH:mm'
			},
		axisFormat: 'HH:mm',
		monthNames: monthNames,
		monthNamesShort: monthNamesShort,
		dayNames: dayNames,
		dayNamesShort: dayNamesShort,
		allDayText: allDayText,
		eventSources: eventSources,
		viewDisplay: function(view) {
			$('#datecontrol_date').html(view.title);
			$.get(OC.filePath('calendar', 'ajax', 'changeview.php') + "?v="+view.name);
		},
		dayClick: Calendar.UI.newEvent,
		eventClick: Calendar.UI.editEvent
	});
	$('#oneweekview_radio').click(function(){
		$('#calendar_holder').fullCalendar('changeView', 'agendaWeek');
	});
	$('#onemonthview_radio').click(function(){
		$('#calendar_holder').fullCalendar('changeView', 'month');
	});
	//$('#listview_radio').click();
	$('#today_input').click(function(){
		$('#calendar_holder').fullCalendar('today');
	});
	$('#datecontrol_left').click(function(){
		$('#calendar_holder').fullCalendar('prev');
	});
	$('#datecontrol_right').click(function(){
		$('#calendar_holder').fullCalendar('next');
	});
});
