/**
 * Copyright (c) 2011 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

Calendar={
	space:' ',
	UI:{
		startEventDialog:function(){
			$('.tipsy').remove();
			$('#calendar_holder').fullCalendar('unselect');
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
		newEvent:function(start, end, allday){
			start = Math.round(start.getTime()/1000);
			if (end){
				end = Math.round(end.getTime()/1000);
			}
			if($('#event').dialog('isOpen') == true){
				// TODO: save event
				$('#event').dialog('destroy').remove();
			}else{
				$('#dialog_holder').load(OC.filePath('calendar', 'ajax', 'neweventform.php'), {start:start, end:end, allday:allday?1:0}, Calendar.UI.startEventDialog);
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
			var post = $( '#event_form' ).serialize();
			$('#errorbox').empty();
			$.post(url, post, function(data){
					if(data.status == 'success'){
						$('#calendar_holder').fullCalendar('removeEvents', $('#event_form input[name=id]').val());
						$('#event').dialog('destroy').remove();
					} else {
						$('#errorbox').html(t('calendar', 'Deletion failed'));
					}

			}, "json");
		},
		validateEventForm:function(url){
			var post = $( "#event_form" ).serialize();
			$("#errorbox").empty();
			$.post(url, post,
				function(data){
					if(data.status == "error"){
						var output = missing_field + ": <br />";
						if(data.title == "true"){
							output = output + missing_field_title + "<br />";
						}
						if(data.cal == "true"){
							output = output + missing_field_calendar + "<br />";
						}
						if(data.from == "true"){
							output = output + missing_field_fromdate + "<br />";
						}
						if(data.fromtime == "true"){
							output = output + missing_field_fromtime + "<br />";
						}
						if(data.to == "true"){
							output = output + missing_field_todate + "<br />";
						}
						if(data.totime == "true"){
							output = output + missing_field_totime + "<br />";
						}
						if(data.endbeforestart == "true"){
							output = output + missing_field_startsbeforeends + "!<br/>";
						}
						if(data.dberror == "true"){
							output = "There was a database fail!";
						}
						$("#errorbox").html(output);
					} else
					if(data.status == 'success'){
						$('#event').dialog('destroy').remove();
						$('#calendar_holder').fullCalendar('refetchEvents');
					}
				},"json");
		},
		moveEvent:function(event, dayDelta, minuteDelta, allDay, revertFunc){
			$.post(OC.filePath('calendar', 'ajax', 'moveevent.php'), { id: event.id, dayDelta: dayDelta, minuteDelta: minuteDelta, allDay: allDay?1:0},
			function(data) {
				if (data.status == 'success'){
					console.log("Event moved successfully");
				}else{
					revertFunc();
				}
			});
		},
		resizeEvent:function(event, dayDelta, minuteDelta, revertFunc){
			$.post(OC.filePath('calendar', 'ajax', 'resizeevent.php'), { id: event.id, dayDelta: dayDelta, minuteDelta: minuteDelta},
			function(data) {
				if (data.status == 'success'){
					console.log("Event resized successfully");
				}else{
					revertFunc();
				}
			});
		},
		showadvancedoptions:function(){
			$("#advanced_options").css("display", "block");
			$("#advanced_options_button").css("display", "none");
		},
		getEventPopupText:function(event){
			if (event.allDay){
				var timespan = $.fullCalendar.formatDates(event.start, event.end, t('calendar', "ddd d MMMM[ yyyy]{ -[ddd d] MMMM yyyy}"));
			}else{
				var timespan = $.fullCalendar.formatDates(event.start, event.end, t('calendar', "ddd d MMMM[ yyyy] HH:mm{ -[ ddd d MMMM yyyy] HH:mm}"));
				// Tue 18 October 2011 08:00 - 16:00
			}
			var html =
				'<div class="summary">' + event.title + '</div>' +
				'<div class="timespan">' + timespan + '</div>';
			if (event.description){
				html += '<div class="description">' + event.description + '</div>';
			}
			return html;
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
		initScroll:function(){
			if(window.addEventListener)
				document.addEventListener('DOMMouseScroll', Calendar.UI.scrollCalendar);
			//}else{
				document.onmousewheel = Calendar.UI.scrollCalendar;
			//}
		},
		scrollCalendar:function(event){
			var direction;
			if(event.detail){
				if(event.detail < 0){
					direction = 'top';
				}else{
					direction = 'down';
				}
			}
			if (event.wheelDelta){
				if(event.wheelDelta > 0){
					direction = 'top';
				}else{
					direction = 'down';
				}
			}
			var scroll = $(document).scrollTop(),
				doc_height = $(document).height(),
				win_height = $(window).height();
			if(direction == 'down' && win_height == (doc_height - scroll)){
				$('#calendar_holder').fullCalendar('next');
			}else if (direction == 'top' && scroll == 0) {
				$('#calendar_holder').fullCalendar('prev');
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
					if (data.status == 'success'){
						checkbox.checked = data.active == 1;
						if (data.active == 1){
							$('#calendar_holder').fullCalendar('addEventSource', data.eventSource);
						}else{
							$('#calendar_holder').fullCalendar('removeEventSource', data.eventSource.url);
						}
					}
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
			deleteCalendar:function(calid){
				var check = confirm("Do you really want to delete this calendar?");
				if(check == false){
					return false;
				}else{
					$.post(OC.filePath('calendar', 'ajax', 'deletecalendar.php'), { calendarid: calid},
					  function(data) {
						if (data.status == 'success'){
							var url = 'ajax/events.php?calendar_id='+calid;
							$('#calendar_holder').fullCalendar('removeEventSource', url);
							$('#choosecalendar_dialog').dialog('destroy').remove();
							Calendar.UI.Calendar.overview();
						}
					  });
				}
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
						if(data.status == 'success'){
							$(button).closest('tr').prev().html(data.page).show().next().remove();
							$('#calendar_holder').fullCalendar('removeEventSource', data.eventSource.url);
							$('#calendar_holder').fullCalendar('addEventSource', data.eventSource);
						}
					}, 'json');
			},
			cancel:function(button, calendarid){
				$(button).closest('tr').prev().show().next().remove();
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
			}
		}
	}
}
$.fullCalendar.views.list = ListView;
function ListView(element, calendar) {
	var t = this;

	// imports
	jQuery.fullCalendar.views.month.call(t, element, calendar);
	var opt = t.opt;
	var trigger = t.trigger;
	var eventElementHandlers = t.eventElementHandlers;
	var reportEventElement = t.reportEventElement;
	var formatDate = calendar.formatDate;
	var formatDates = calendar.formatDates;
	var addDays = $.fullCalendar.addDays;
	var cloneDate = $.fullCalendar.cloneDate;
	function skipWeekend(date, inc, excl) {
		inc = inc || 1;
		while (!date.getDay() || (excl && date.getDay()==1 || !excl && date.getDay()==6)) {
			addDays(date, inc);
		}
		return date;
	}

	// overrides
	t.name='list';
	t.render=render;
	t.renderEvents=renderEvents;
	t.setHeight=setHeight;
	t.setWidth=setWidth;
	t.clearEvents=clearEvents;

	function setHeight(height, dateChanged) {
	}

	function setWidth(width) {
	}

	function clearEvents() {
		this.reportEventClear();
	}

	// main
	function sortEvent(a, b) {
		return a.start - b.start;
	}

	function render(date, delta) {
		if (!t.start){
			t.start = addDays(cloneDate(date, true), -7);
			t.end = addDays(cloneDate(date, true), 7);
		}
		if (delta) {
			if (delta < 0){
				addDays(t.start, -7);
				if (!opt('weekends')) {
					skipWeekend(t.start, delta < 0 ? -1 : 1);
				}
			}else{
				addDays(t.end, 7);
				if (!opt('weekends')) {
					skipWeekend(t.end, delta < 0 ? -1 : 1);
				}
			}
		}
		t.title = formatDates(
			t.start,
			t.end,
			opt('titleFormat', 'week')
		);
		t.visStart = cloneDate(t.start);
		t.visEnd = cloneDate(t.end);
	}

	function eventsOfThisDay(events, theDate) {
		var start = cloneDate(theDate, true);
		var end = addDays(cloneDate(start), 1);
		var retArr = new Array();
		for (i in events) {
			var event_end = t.eventEnd(events[i]);
			if (events[i].start < end && event_end >= start) {
				retArr.push(events[i]);
			}
		}
		return retArr;
	}

	function renderEvent(event) {
		if (event.allDay) { //all day event
			var time = opt('allDayText');
		}
		else {
			var time = formatDates(event.start, event.end, opt('timeFormat', 'agenda'));
		}
		var classes = ['fc-event', 'fc-list-event'];
		classes = classes.concat(event.className);
		if (event.source) {
			classes = classes.concat(event.source.className || []);
		}
		var html = '<tr>' +
			'<td>&nbsp;</td>' +
			'<td class="fc-list-time">' +
			time +
			'</td>' +
			'<td>&nbsp;</td>' +
			'<td class="fc-list-event">' +
			'<span id="list' + event.id + '"' +
			' class="' + classes.join(' ') + '"' +
			'>' +
			'<span class="fc-event-title">' +
			event.title +
			'</span>' +
			'</span>' +
			'</td>' +
			'</tr>';
		return html;
	}

	function renderDay(date, events) {
		var dayRows = $('<tr>' +
			'<td colspan="4" class="fc-list-date">' +
			'<span>' +
			formatDate(date, opt('titleFormat', 'day')) +
			'</span>' +
			'</td>' +
			'</tr>');
		for (i in events) {
			var event = events[i];
			var eventElement = $(renderEvent(event));
			triggerRes = trigger('eventRender', event, event, eventElement);
			if (triggerRes === false) {
				eventElement.remove();
			}else{
				if (triggerRes && triggerRes !== true) {
					eventElement.remove();
					eventElement = $(triggerRes);
				}
				$.merge(dayRows, eventElement);
				eventElementHandlers(event, eventElement);
				reportEventElement(event, eventElement);
			}
		}
		return dayRows;
	}

	function renderEvents(events, modifiedEventId) {
		events = events.sort(sortEvent);

		var table = $('<table class="fc-list-table"></table>');
		var total = events.length;
		if (total > 0) {
			var date = cloneDate(t.visStart);
			while (date <= t.visEnd) {
				var dayEvents = eventsOfThisDay(events, date);
				if (dayEvents.length > 0) {
					table.append(renderDay(date, dayEvents));
				}
				date=addDays(date, 1);
			}
		}

		this.element.html(table);
	}
}
$(document).ready(function(){
	Calendar.UI.initScroll();
	$('#calendar_holder').fullCalendar({
		header: false,
		firstDay: 1,
		editable: true,
		defaultView: defaultView,
		timeFormat: {
			agenda: 'HH:mm{ - HH:mm}',
			'': 'HH:mm'
			},
		titleFormat: {
			list: 'yyyy/MMM/d dddd'
			},
		axisFormat: 'HH:mm',
		monthNames: monthNames,
		monthNamesShort: monthNamesShort,
		dayNames: dayNames,
		dayNamesShort: dayNamesShort,
		allDayText: allDayText,
		viewDisplay: function(view) {
			$('#datecontrol_date').html(view.title);
			$.get(OC.filePath('calendar', 'ajax', 'changeview.php') + "?v="+view.name);
		},
		selectable: true,
		selectHelper: true,
		select: Calendar.UI.newEvent,
		eventClick: Calendar.UI.editEvent,
		eventDrop: Calendar.UI.moveEvent,
		eventResize: Calendar.UI.resizeEvent,
		eventRender: function(event, element) {
			element.tipsy({
				className: 'tipsy-event',
				opacity: 0.9,
				gravity:$.fn.tipsy.autoBounds(150, 's'),
				fade:true,
				delayIn: 400,
				html:true,
				title:function() {
					return Calendar.UI.getEventPopupText(event);
				}
			});
		},
		eventSources: eventSources
	});
	$('#oneweekview_radio').click(function(){
		$('#calendar_holder').fullCalendar('changeView', 'agendaWeek');
	});
	$('#onemonthview_radio').click(function(){
		$('#calendar_holder').fullCalendar('changeView', 'month');
	});
	$('#listview_radio').click(function(){
		$('#calendar_holder').fullCalendar('changeView', 'list');
	});
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
