/**
 * Copyright (c) 2012 Georg Ehrke <ownclouddev at georgswebsite dot de>
 * Copyright (c) 2011 Bart Visscher <bartv@thisnet.nl>
 * This file is licensed under the Affero General Public License version 3 or
 * later.
 * See the COPYING-README file.
 */

Calendar={
	UI:{
		loading: function(isLoading){
			if (isLoading){
				$('#loading').show();
			}else{
				$('#loading').hide();
			}
		},
		startEventDialog:function(){
			Calendar.UI.loading(false);
			$('.tipsy').remove();
			$('#calendar_holder').fullCalendar('unselect');
			Calendar.UI.lockTime();
			$( "#from" ).datepicker({
				dateFormat : 'dd-mm-yy'
			});
			$( "#to" ).datepicker({
				dateFormat : 'dd-mm-yy'
			});
			$('#fromtime').timepicker({
			    showPeriodLabels: false
			});
			$('#totime').timepicker({
			    showPeriodLabels: false
			});
			$('#category').multiple_autocomplete({source: categories});
			Calendar.UI.repeat('init');
			$('#end').change(function(){
				Calendar.UI.repeat('end');
			});
			$('#repeat').change(function(){
				Calendar.UI.repeat('repeat');
			});
			$('#advanced_year').change(function(){
				Calendar.UI.repeat('year');
			});
			$('#advanced_month').change(function(){
				Calendar.UI.repeat('month');
			});
			$( "#event" ).tabs({ selected: 0});
			$('#event').dialog({
				width : 500,
				height: 600,
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
				Calendar.UI.loading(true);
				$('#dialog_holder').load(OC.filePath('calendar', 'ajax/event', 'new.form.php'), {start:start, end:end, allday:allday?1:0}, Calendar.UI.startEventDialog);
			}
		},
		editEvent:function(calEvent, jsEvent, view){
			if (calEvent.editable == false || calEvent.source.editable == false) {
				return;
			}
			var id = calEvent.id;
			if($('#event').dialog('isOpen') == true){
				// TODO: save event
				$('#event').dialog('destroy').remove();
			}else{
				Calendar.UI.loading(true);
				$('#dialog_holder').load(OC.filePath('calendar', 'ajax/event', 'edit.form.php') + '?id=' + id, Calendar.UI.startEventDialog);
			}
		},
		submitDeleteEventForm:function(url){
			var post = $( '#event_form' ).serialize();
			$('#errorbox').empty();
			Calendar.UI.loading(true);
			$.post(url, post, function(data){
					Calendar.UI.loading(false);
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
			Calendar.UI.loading(true);
			$.post(url, post,
				function(data){
					Calendar.UI.loading(false);
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
			$('.tipsy').remove();
			Calendar.UI.loading(true);
			$.post(OC.filePath('calendar', 'ajax/event', 'move.php'), { id: event.id, dayDelta: dayDelta, minuteDelta: minuteDelta, allDay: allDay?1:0, lastmodified: event.lastmodified},
			function(data) {
				Calendar.UI.loading(false);
				if (data.status == 'success'){
					event.lastmodified = data.lastmodified;
					console.log("Event moved successfully");
				}else{
					revertFunc();
					$('#calendar_holder').fullCalendar('refetchEvents');
				}
			});
		},
		resizeEvent:function(event, dayDelta, minuteDelta, revertFunc){
			$('.tipsy').remove();
			Calendar.UI.loading(true);
			$.post(OC.filePath('calendar', 'ajax/event', 'resize.php'), { id: event.id, dayDelta: dayDelta, minuteDelta: minuteDelta, lastmodified: event.lastmodified},
			function(data) {
				Calendar.UI.loading(false);
				if (data.status == 'success'){
					event.lastmodified = data.lastmodified;
					console.log("Event resized successfully");
				}else{
					revertFunc();
					$('#calendar_holder').fullCalendar('refetchEvents');
				}
			});
		},
		showadvancedoptions:function(){
			$("#advanced_options").slideDown('slow');
			$("#advanced_options_button").css("display", "none");
		},
		showadvancedoptionsforrepeating:function(){
			if($("#advanced_options_repeating").is(":hidden")){
				$('#advanced_options_repeating').slideDown('slow');
			}else{
				$('#advanced_options_repeating').slideUp('slow');
			}
		},
		getEventPopupText:function(event){
			if (event.allDay){
				var timespan = $.fullCalendar.formatDates(event.start, event.end, 'ddd d MMMM[ yyyy]{ -[ddd d] MMMM yyyy}', {monthNamesShort: monthNamesShort, monthNames: monthNames, dayNames: dayNames, dayNamesShort: dayNamesShort}); //t('calendar', "ddd d MMMM[ yyyy]{ -[ddd d] MMMM yyyy}")
			}else{
				var timespan = $.fullCalendar.formatDates(event.start, event.end, 'ddd d MMMM[ yyyy] ' + defaulttime + '{ -[ ddd d MMMM yyyy]' + defaulttime + '}', {monthNamesShort: monthNamesShort, monthNames: monthNames, dayNames: dayNames, dayNamesShort: dayNamesShort}); //t('calendar', "ddd d MMMM[ yyyy] HH:mm{ -[ ddd d MMMM yyyy] HH:mm}")
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
			$('#caldav_url').val(encodeURI($('#caldav_url').val()));
			$('#caldav_url').show();
			$("#caldav_url_close").show();
		},
		initScroll:function(){
			if(window.addEventListener)
				document.addEventListener('DOMMouseScroll', Calendar.UI.scrollCalendar, false);
			//}else{
				document.onmousewheel = Calendar.UI.scrollCalendar;
			//}
		},
		scrollCalendar:function(event){
			$('.tipsy').remove();
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
				$(document).scrollTop(0);
				event.preventDefault();
			}else if (direction == 'top' && scroll == 0) {
				$('#calendar_holder').fullCalendar('prev');
				$(document).scrollTop(win_height);
				event.preventDefault();
			}
		},
		repeat:function(task){
			if(task=='init'){
				$('#byweekno').multiselect({
					header: false,
					noneSelectedText: $('#advanced_byweekno').attr('title'),
					selectedList: 2,
					minWidth:'auto'
				});
				$('#weeklyoptions').multiselect({
					header: false,
					noneSelectedText: $('#weeklyoptions').attr('title'),
					selectedList: 2,
					minWidth:'auto'
				});
				$('input[name="bydate"]').datepicker({
					dateFormat : 'dd-mm-yy'
				});
				$('#byyearday').multiselect({
					header: false,
					noneSelectedText: $('#byyearday').attr('title'),
					selectedList: 2,
					minWidth:'auto'
				});
				$('#bymonth').multiselect({
					header: false,
					noneSelectedText: $('#bymonth').attr('title'),
					selectedList: 2,
					minWidth:'auto'
				});
				$('#bymonthday').multiselect({
					header: false,
					noneSelectedText: $('#bymonthday').attr('title'),
					selectedList: 2,
					minWidth:'auto'
				});
				Calendar.UI.repeat('end');
				Calendar.UI.repeat('month');
				Calendar.UI.repeat('year');
				Calendar.UI.repeat('repeat');
			}
			if(task == 'end'){
				$('#byoccurrences').css('display', 'none');
				$('#bydate').css('display', 'none');
				if($('#end option:selected').val() == 'count'){
					$('#byoccurrences').css('display', 'block');
				}
				if($('#end option:selected').val() == 'date'){
					$('#bydate').css('display', 'block');
				}
			}
			if(task == 'repeat'){
				$('#advanced_month').css('display', 'none');
				$('#advanced_weekday').css('display', 'none');
				$('#advanced_weekofmonth').css('display', 'none');
				$('#advanced_byyearday').css('display', 'none');
				$('#advanced_bymonth').css('display', 'none');
				$('#advanced_byweekno').css('display', 'none');
				$('#advanced_year').css('display', 'none');
				$('#advanced_bymonthday').css('display', 'none');
				if($('#repeat option:selected').val() == 'monthly'){
					$('#advanced_month').css('display', 'block');
					Calendar.UI.repeat('month');
				}
				if($('#repeat option:selected').val() == 'weekly'){
					$('#advanced_weekday').css('display', 'block');
				}
				if($('#repeat option:selected').val() == 'yearly'){
					$('#advanced_year').css('display', 'block');
					Calendar.UI.repeat('year');
				}
				if($('#repeat option:selected').val() == 'doesnotrepeat'){
					$('#advanced_options_repeating').slideUp('slow');
				}
			}
			if(task == 'month'){
				$('#advanced_weekday').css('display', 'none');
				$('#advanced_weekofmonth').css('display', 'none');
				if($('#advanced_month_select option:selected').val() == 'weekday'){
					$('#advanced_weekday').css('display', 'block');
					$('#advanced_weekofmonth').css('display', 'block');
				}
			}
			if(task == 'year'){
				$('#advanced_weekday').css('display', 'none');
				$('#advanced_byyearday').css('display', 'none');
				$('#advanced_bymonth').css('display', 'none');
				$('#advanced_byweekno').css('display', 'none');
				$('#advanced_bymonthday').css('display', 'none');
				if($('#advanced_year_select option:selected').val() == 'byyearday'){
					//$('#advanced_byyearday').css('display', 'block');
				}
				if($('#advanced_year_select option:selected').val() == 'byweekno'){
					$('#advanced_byweekno').css('display', 'block');
				}
				if($('#advanced_year_select option:selected').val() == 'bydaymonth'){
					$('#advanced_bymonth').css('display', 'block');
					$('#advanced_bymonthday').css('display', 'block');
					$('#advanced_weekday').css('display', 'block');
				}
			}
			
		},
		setViewActive: function(view){
			$('#view input[type="button"]').removeClass('active');
			var id;
			switch (view) {
				case 'agendaWeek':
					id = 'oneweekview_radio';
					break;
				case 'month':
					id = 'onemonthview_radio';
					break;
				case 'list':
					id = 'listview_radio';
					break;
			}
			$('#'+id).addClass('active');
		},
		categoriesChanged:function(newcategories){
			categories = $.map(newcategories, function(v) {return v;});
			console.log('Calendar categories changed to: ' + categories);
			$('#category').multiple_autocomplete('option', 'source', categories);
		},
		Calendar:{
			overview:function(){
				if($('#choosecalendar_dialog').dialog('isOpen') == true){
					$('#choosecalendar_dialog').dialog('moveToTop');
				}else{
					Calendar.UI.loading(true);
					$('#dialog_holder').load(OC.filePath('calendar', 'ajax/calendar', 'overview.php'), function(){
						$('#choosecalendar_dialog').dialog({
							width : 600,
							height: 400,
							close : function(event, ui) {
								$(this).dialog('destroy').remove();
							}
						});
						Calendar.UI.loading(false);
					});
				}
			},
			activation:function(checkbox, calendarid)
			{
				Calendar.UI.loading(true);
				$.post(OC.filePath('calendar', 'ajax/calendar', 'activation.php'), { calendarid: calendarid, active: checkbox.checked?1:0 },
				  function(data) {
					Calendar.UI.loading(false);
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
					.load(OC.filePath('calendar', 'ajax/calendar', 'new.form.php'),
						function(){Calendar.UI.Calendar.colorPicker(this)});
				$(object).closest('tr').after(tr).hide();
			},
			edit:function(object, calendarid){
				var tr = $(document.createElement('tr'))
					.load(OC.filePath('calendar', 'ajax/calendar', 'edit.form.php') + "?calendarid="+calendarid,
						function(){Calendar.UI.Calendar.colorPicker(this)});
				$(object).closest('tr').after(tr).hide();
			},
			deleteCalendar:function(calid){
				var check = confirm("Do you really want to delete this calendar?");
				if(check == false){
					return false;
				}else{
					$.post(OC.filePath('calendar', 'ajax/calendar', 'delete.php'), { calendarid: calid},
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
				var displayname = $.trim($("#displayname_"+calendarid).val());
				var active = $("#edit_active_"+calendarid+":checked").length;
				var description = $("#description_"+calendarid).val();
				var calendarcolor = $("#calendarcolor_"+calendarid).val();
				if(displayname == ''){
					$("#displayname_"+calendarid).css('background-color', '#FF2626');
					$("#displayname_"+calendarid).focus(function(){
						$("#displayname_"+calendarid).css('background-color', '#F8F8F8');
					});
				}
				
				var url;
				if (calendarid == 'new'){
					url = OC.filePath('calendar', 'ajax/calendar', 'new.php');
				}else{
					url = OC.filePath('calendar', 'ajax/calendar', 'update.php');
				}
				$.post(url, { id: calendarid, name: displayname, active: active, description: description, color: calendarcolor },
					function(data){
						if(data.status == 'success'){
							$(button).closest('tr').prev().html(data.page).show().next().remove();
							$('#calendar_holder').fullCalendar('removeEventSource', data.eventSource.url);
							$('#calendar_holder').fullCalendar('addEventSource', data.eventSource);
							if (calendarid == 'new'){
								$('#choosecalendar_dialog > table:first').append('<tr><td colspan="6"><a href="#" onclick="Calendar.UI.Calendar.newCalendar(this);"><input type="button" value="' + newcalendar + '"></a></td></tr>');
							}
						}else{
							$("#displayname_"+calendarid).css('background-color', '#FF2626');
							$("#displayname_"+calendarid).focus(function(){
								$("#displayname_"+calendarid).css('background-color', '#F8F8F8');
							});
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
					picker.append('<span class="calendar-colorpicker-color ' + (colors[i].color == $(obj).children(":selected").val() ? ' active' : '') + '" rel="' + colors[i].label + '" style="background-color: ' + colors[i].color + ';"></span>');
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
		},
		Share:{
			currentid: 'false',
			idtype: '',
			activation:function(object,owner,id){
				$.getJSON(OC.filePath('calendar', 'ajax/share', 'activation.php'),{id:id, idtype:'calendar', activation:object.checked?1:0});
				$('#calendar_holder').fullCalendar('refetchEvents');
			},
			dropdown:function(userid, calid){
				$('.calendar_share_dropdown').remove();
				var element = document.getElementById(userid+'_'+calid);
				$('<div class="calendar_share_dropdown"></div>').appendTo(element);
				$.get(OC.filePath('calendar', 'ajax/share', 'dropdown.php') + '?calid=' + calid, function(data){
					$('.calendar_share_dropdown').html(data);
					$('.calendar_share_dropdown').show('blind');
					$('#share_user').chosen();
					$('#share_group').chosen();
				});
				Calendar.UI.Share.currentid = calid;
				Calendar.UI.Share.idtype = 'calendar';
			},
			share:function(id, idtype, sharewith, sharetype){
				$.getJSON(OC.filePath('calendar', 'ajax/share', 'share.php'),{id:id, idtype:idtype, sharewith:sharewith, sharetype:sharetype}, function(data){
					if(sharetype == 'public'){
						$('#public_token').val(parent.location.protocol+'//'+location.host+OC.linkTo('', 'public.php')+'?service=calendar&t='+data.message);
						$('#public_token').css('display', 'block');
					}
				});
			},
			unshare:function(id, idtype, sharewith, sharetype){
				$.getJSON(OC.filePath('calendar', 'ajax/share', 'unshare.php'),{id:id, idtype:idtype, sharewith:sharewith, sharetype:sharetype}, function(){
					if(sharetype == 'public'){
						$('#public_token').val('');
						$('#public_token').css('display', 'none');
					}
				});
			},
			changepermission:function(id, idtype, sharewith, sharetype, permission){
				$.getJSON(OC.filePath('calendar', 'ajax/share', 'changepermission.php'),{id:id, idtype:idtype, sharewith: sharewith, sharetype:sharetype, permission: (permission?1:0)});
			},
			init:function(){
				$('.calendar_share_dropdown').live('mouseleave', function(){
					$('.calendar_share_dropdown').hide('blind', function(){
						$('.calendar_share_dropdown').remove();
					});
				});
				$('#share_user').live('change', function(){
					if($('#sharewithuser_' + $('#share_user option:selected').text()).length == 0){
						Calendar.UI.Share.share(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, $('#share_user option:selected').text(), 'user');
						var newitem = '<li id="sharewithuser_' + $('#share_user option:selected').text() +'"><input type="checkbox" width="12px" style="visibility:hidden;" title="' + $('#share_user option:selected').text() + '">' + $('#share_user option:selected').text() + '<img src="' + oc_webroot + '/core/img/actions/delete.svg" class="svg action" style="display:none;float:right;"></li>';
						$('#sharewithuser_list').append(newitem);
						$('#sharewithuser_' + $('#share_user option:selected').text() + ' > img').click(function(){
							$('#share_user option[value="' + $(this).parent().text() + '"]').removeAttr('disabled');
							Calendar.UI.Share.unshare(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, $(this).parent().text(), 'user' );
							$("#share_user").trigger("liszt:updated");
							$(this).parent().remove();
						});
						$('#share_user option:selected').attr('disabled', 'disabled');
						$("#share_user").trigger("liszt:updated");
					}
				});
				$('#share_group').live('change', function(){
					if($('#sharewithgroup_' + $('#share_group option:selected').text()).length == 0){
						Calendar.UI.Share.share(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, $('#share_group option:selected').text(), 'group');
						var newitem = '<li id="sharewithgroup_' + $('#share_group option:selected').text() +'"><input type="checkbox" width="12px" style="visibility:hidden;" title="' + $('#share_group option:selected').text() + '">' + $('#share_group option:selected').text() + '<img src="' + oc_webroot + '/core/img/actions/delete.svg" class="svg action" style="display:none;float:right;"></li>';
						$('#sharewithgroup_list').append(newitem);
						$('#sharewithgroup_' + $('#share_group option:selected').text() + ' > img').click(function(){
							$('#share_group option[value="' + $(this).parent().text() + '"]').removeAttr('disabled');
							Calendar.UI.Share.unshare(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, $(this).parent().text(), 'group');
							$("#share_group").trigger("liszt:updated");
							$(this).parent().remove();
						});
						$('#share_group option:selected').attr('disabled', 'disabled');
						$("#share_group").trigger("liszt:updated");
					}
				});
				$('#sharewithuser_list > li > input:checkbox').live('change', function(){
					Calendar.UI.Share.changepermission(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, $(this).parent().text(), 'user', this.checked);
				});
				$('#sharewithgroup_list > li > input:checkbox').live('change', function(){
					Calendar.UI.Share.changepermission(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, $(this).parent().text(), 'group', this.checked);
				});
				$('#publish').live('change', function(){
					if(this.checked == 1){
						Calendar.UI.Share.share(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, '', 'public');
					}else{
						Calendar.UI.Share.unshare(Calendar.UI.Share.currentid, Calendar.UI.Share.idtype, '', 'public');
					}
				});
				$('#sharewithuser_list').live('mouseenter', function(){
					$('#sharewithuser_list > li > img').css('display', 'block');
					$('#sharewithuser_list > li > input').css('visibility', 'visible');
				});
				$('#sharewithuser_list').live('mouseleave', function(){
					$('#sharewithuser_list > li > img').css('display', 'none');
					$('#sharewithuser_list > li > input').css('visibility', 'hidden');
				});
				$('#sharewithgroup_list').live('mouseenter', function(){
					$('#sharewithgroup_list > li > img').css('display', 'block');
					$('#sharewithgroup_list > li > input').css('visibility', 'visible');
				});
				$('#sharewithgroup_list').live('mouseleave', function(){
					$('#sharewithgroup_list > li > img').css('display', 'none');
					$('#sharewithgroup_list > li > input').css('visibility', 'hidden');
				});
				/*var permissions = (this.checked) ? 1 : 0;*/
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
				addDays(t.end, -7);
				if (!opt('weekends')) {
					skipWeekend(t.start, delta < 0 ? -1 : 1);
				}
			}else{
				addDays(t.start, 7);
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
		firstDay: firstDay,
		editable: true,
		defaultView: defaultView,
		timeFormat: {
			agenda: agendatime,
			'': defaulttime
			},
		columnFormat: {
			month: t('calendar', 'ddd'),    // Mon
			week: t('calendar', 'ddd M/d'), // Mon 9/7
			day: t('calendar', 'dddd M/d')  // Monday 9/7
			},
		titleFormat: {
			month: t('calendar', 'MMMM yyyy'),
					// September 2009
			week: t('calendar', "MMM d[ yyyy]{ '&#8212;'[ MMM] d yyyy}"),
					// Sep 7 - 13 2009
			day: t('calendar', 'dddd, MMM d, yyyy'),
					// Tuesday, Sep 8, 2009
			},
		axisFormat: defaulttime,
		monthNames: monthNames,
		monthNamesShort: monthNamesShort,
		dayNames: dayNames,
		dayNamesShort: dayNamesShort,
		allDayText: allDayText,
		viewDisplay: function(view) {
			$('#datecontrol_date').html(view.title);
			if (view.name != defaultView) {
				$.get(OC.filePath('calendar', 'ajax', 'changeview.php') + "?v="+view.name);
				defaultView = view.name;
			}
			Calendar.UI.setViewActive(view.name);
			if (view.name == 'agendaWeek') {
				$('#calendar_holder').fullCalendar('option', 'aspectRatio', 0.1);
			}
			else {
				$('#calendar_holder').fullCalendar('option', 'aspectRatio', 1.35);
			}
		},
		columnFormat: {
		    week: 'ddd d. MMM'
		},
		selectable: true,
		selectHelper: true,
		select: Calendar.UI.newEvent,
		eventClick: Calendar.UI.editEvent,
		eventDrop: Calendar.UI.moveEvent,
		eventResize: Calendar.UI.resizeEvent,
		eventRender: function(event, element) {
			element.find('.fc-event-title').html(element.find('.fc-event-title').text());
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
		loading: Calendar.UI.loading,
		eventSources: eventSources
	});
	fillWindow($('#content'));
	OCCategories.changed = Calendar.UI.categoriesChanged;
	OCCategories.app = 'calendar';
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
	Calendar.UI.Share.init();
});
