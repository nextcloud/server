/*************************************************
 * ownCloud - Calendar Plugin                     *
 *                                                *
 * (c) Copyright 2011 Georg Ehrke                 *
 * (c) Copyright 2011 Bart Visscher               *
 * author: Georg Ehrke                            *
 * email: ownclouddev at georgswebsite dot de     *
 * homepage: ownclouddev.georgswebsite.de         *
 * manual: ownclouddev.georgswebsite.de/manual    *
 * License: GNU AFFERO GENERAL PUBLIC LICENSE     *
 *                                                *
 * <http://www.gnu.org/licenses/>                 *
 * If you are not able to view the License,       *
 * <http://www.gnu.org/licenses/>                 *
 * <http://ownclouddev.georgswebsite.de/license/> *
 * please write to the Free Software Foundation.  *
 * Address:                                       *
 * 59 Temple Place, Suite 330, Boston,            *
 * MA 02111-1307  USA                             *
 **************************************************
 *               list of all fx                   *
 * calw - Calendarweek                            *
 * doy - Day of the year                          *
 * checkforleapyear - check for a leap year       *
 * forward_day - switching one day forward        *
 * forward_week - switching one week forward      *
 * forward_month - switching one month forward    *
 * backward_day - switching one day backward      *
 * backward_week - switching one week backward    *
 * backward_month - switching one month backward  *
 * update_view - update the view of the calendar  *
 * onedayview - one day view                      *
 * oneweekview - one week view                    *
 * fourweekview - four Weeks view                 *
 * onemonthview - one Month view                  *
 * listview - listview                            *
 * generateDates - generate other days for view  *
 * switch2today - switching to today              *
 * removeEvents - remove old events in view       *
 * loadEvents - load the events                   *
 *************************************************/
Calendar={
	space:' ',
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
		weekdays: ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"],
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
		updateView:function(task) {
			this.current.removeEvents();
			this.current.renderCal();
			this.current.showEvents();
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
			$("#sysbox").load(oc_webroot + "/apps/calendar/ajax/changeview.php?v="+view);
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
			if( typeof (year) == 'undefined') {
				this.events = [];
				year = Calendar.Date.current.getFullYear();
			}
			if( typeof (this.events[year]) == "undefined") {
				this.events[year] = []
			}
			$.getJSON(oc_webroot + "/apps/calendar/ajax/getcal.php?year=" + year, function(newevents, status) {
				if(status == "nosession") {
					alert("You are not logged in. That can happen if you don't use owncloud for a long time.");
					document.location(oc_webroot);
				}
				if(status == "parsingfail" || typeof (newevents) == "undefined") {
					$.ready(function() {
						$( "#parsingfail_dialog" ).dialog();
					});
				} else {
					if (typeof(newevents[year]) != 'undefined'){
						Calendar.UI.events[year] = newevents[year];
					}
					$(document).ready(function() {
						Calendar.UI.updateView();
					});
				}
			});
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
			var weekday = (date.getDay()+6)%7;
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
				.click(this.editEvent);
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
				$('#dialog_holder').load(oc_webroot + '/apps/calendar/ajax/neweventform.php?d=' + date + '&t=' + time, Calendar.UI.startEventDialog);
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
				$('#dialog_holder').load(oc_webroot + '/apps/calendar/ajax/editeventform.php?id=' + id, Calendar.UI.startEventDialog);
			}
		},
		validateEventForm:function(url){
			var post = $( "#event_form" ).serialize();
			$("#errorbox").html("");
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
		Calendar:{
			overview:function(){
				if($('#choosecalendar_dialog').dialog('isOpen') == true){
					$('#choosecalendar_dialog').dialog('moveToTop');
				}else{
					$('#dialog_holder').load(oc_webroot + '/apps/calendar/ajax/choosecalendar.php', function(){
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
				$.post(oc_webroot + "/apps/calendar/ajax/activation.php", { calendarid: calendarid, active: checkbox.checked?1:0 },
				  function(data) {
					checkbox.checked = data == 1;
					Calendar.UI.loadEvents();
				  });
			},
			new:function(object){
				var tr = $(document.createElement('tr'))
					.load(oc_webroot + "/apps/calendar/ajax/newcalendar.php");
				$(object).closest('tr').after(tr).hide();
			},
			edit:function(object, calendarid){
				var tr = $(document.createElement('tr'))
					.load(oc_webroot + "/apps/calendar/ajax/editcalendar.php?calendarid="+calendarid);
				$(object).closest('tr').after(tr).hide();
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
		OneDay:{
			forward:function(){
				Calendar.Date.forward_day();
			},
			backward:function(){
				Calendar.Date.backward_day();
			},
			removeEvents:function(){
				$("#onedayview .calendar_row").html("");
			},
			renderCal:function(){
				$("#datecontrol_date").val(Calendar.UI.formatDayShort() + Calendar.space + Calendar.Date.current.getDate() + Calendar.space + Calendar.UI.formatMonthShort() + Calendar.space + Calendar.Date.current.getFullYear());
				$("#onedayview_today").html(Calendar.UI.formatDayLong() + Calendar.space + Calendar.Date.current.getDate() + Calendar.space + Calendar.UI.formatMonthShort());
				Calendar.UI.addDateInfo('#onedayview_today', new Date(Calendar.Date.current));
			},
			showEvents:function(){
				Calendar.UI.createEventsForDate(Calendar.Date.current, 0);
			},
			getEventContainer:function(week, weekday, when){
				return $("#onedayview ." + when);
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
		OneWeek:{
			forward:function(){
				Calendar.Date.forward_week();
			},
			backward:function(){
				Calendar.Date.backward_week();
			},
			removeEvents:function(){
				for( i = 0; i <= 6; i++) {
					$("#oneweekview ." + Calendar.UI.weekdays[i]).html("");
				}
				$("#oneweekview .thisday").removeClass("thisday");
			},
			renderCal:function(){
				$("#datecontrol_date").val(Calendar.UI.cw_label + ": " + Calendar.Date.calw());
				var dates = this.generateDates();
				var today = new Date();
				for(var i = 0; i <= 6; i++){
					$("#oneweekview th." + Calendar.UI.weekdays[i]).html(Calendar.UI.formatDayShort((i+1)%7) + Calendar.space + dates[i].getDate() + Calendar.space + Calendar.UI.formatMonthShort(dates[i].getMonth()));
					if(dates[i].getDate() == today.getDate() && dates[i].getMonth() == today.getMonth() && dates[i].getFullYear() == today.getFullYear()){
						$("#oneweekview ." + Calendar.UI.weekdays[i]).addClass("thisday");
					}
					Calendar.UI.addDateInfo('#oneweekview th.' + Calendar.UI.weekdays[i], dates[i]);
				}
			},
			showEvents:function(){
				var dates = this.generateDates();
				for(var weekday = 0; weekday <= 6; weekday++) {
					Calendar.UI.createEventsForDate(dates[weekday], 0);
				}
			},
			getEventContainer:function(week, weekday, when){
				return $("#oneweekview ." + Calendar.UI.weekdays[weekday] + "." + when);
			},
			createEventLabel:function(event){
				var time = '';
				if (!event['allday']){
					time = '<strong>' + Calendar.UI.formatTime(event['startdate']) + ' - ' + Calendar.UI.formatTime(event['enddate']) + '</strong> ';
				}
				return $(document.createElement('p'))
					.html(time + event['description'])
			},
			generateDates:function(){
				var dates = new Array();
				var date = new Date(Calendar.Date.current)
				var dayofweek = date.getDay();
				if(dayofweek == 0) {
					dayofweek = 7;
				}
				date.setDate(date.getDate() - dayofweek + 1);
				for(var i = 0; i <= 6; i++) {
					dates[i] = new Date(date)
					date.setDate(date.getDate() + 1);
				}
				return dates;
			},
		},
		FourWeeks:{
			forward:function(){
				Calendar.Date.forward_week();
			},
			backward:function(){
				Calendar.Date.backward_week();
			},
			removeEvents:function(){
				$('#fourweeksview .day.thisday').removeClass('thisday');
				$('#fourweeksview .day .events').html('');
			},
			renderCal:function(){
				var calw1 = Calendar.Date.calw();
				var calw2 = calw1 + 1;
				var calw3 = calw1 + 2;
				var calw4 = calw1 + 3;
				switch(calw1) {
					case 50:
						calw4 = 1;
						break;
					case 51:
						calw3 = 1;
						calw4 = 2;
						break;
					case 52:
						calw2 = 1;
						calw3 = 2;
						calw4 = 3;
						break;
				}
				var calwplusfour = calw4;
				var dates = this.generateDates();
				var week = 1;
				var weekday = 0;
				var today = new Date();
				for(var i = 0; i <= 27; i++){
					var dayofmonth = dates[i].getDate();
					var month = dates[i].getMonth();
					var year = dates[i].getFullYear();
					$("#fourweeksview .week_" + week + " ." + Calendar.UI.weekdays[weekday] + " .dateinfo").html(dayofmonth + Calendar.space + Calendar.UI.formatMonthShort(month));
					if(dayofmonth == today.getDate() && month == today.getMonth() && year == today.getFullYear()){
						$("#fourweeksview .week_" + week + " ." + Calendar.UI.weekdays[weekday]).addClass('thisday');
					}
					Calendar.UI.addDateInfo('#fourweeksview .week_' + week + ' .' + Calendar.UI.weekdays[weekday], dates[i]);
					if(weekday == 6){
						weekday = 0;
						week++;
					}else{
						weekday++;
					}
				}
				$("#fourweeksview .week_1 .calw").html(calw1);
				$("#fourweeksview .week_2 .calw").html(calw2);
				$("#fourweeksview .week_3 .calw").html(calw3);
				$("#fourweeksview .week_4 .calw").html(calw4);
				$("#datecontrol_date").val(Calendar.UI.cws_label + ": " + Calendar.Date.calw() + " - " + calwplusfour);
			},
			showEvents:function(){
				var dates = this.generateDates();
				var weekdaynum = 0;
				var weeknum = 1;
				for(var i = 0; i <= 27; i++) {
					Calendar.UI.createEventsForDate(dates[i], weeknum);
					if(weekdaynum == 6){
						weekdaynum = 0;
						weeknum++;
					}else{
						weekdaynum++;
					}
				}
			},
			getEventContainer:function(week, weekday, when){
				return $("#fourweeksview .week_" + week + " .day." + Calendar.UI.weekdays[weekday] + " .events");
			},
			createEventLabel:function(event){
				var time = '';
				if (!event['allday']){
					time = '<strong>' + Calendar.UI.formatTime(event['startdate']) + '</strong> ';
				}
				return $(document.createElement('p'))
					.html(time + event['description'])
			},
			generateDates:function(){
				var dates = new Array();
				var date = new Date(Calendar.Date.current)
				var dayofweek = date.getDay();
				if(dayofweek == 0) {
					dayofweek = 7;
				}
				date.setDate(date.getDate() - dayofweek + 1);
				for(var i = 0; i <= 27; i++) {
					dates[i] = new Date(date)
					date.setDate(date.getDate() + 1);
				}
				return dates;
			},
		},
		OneMonth:{
			forward:function(){
				Calendar.Date.forward_month();
			},
			backward:function(){
				Calendar.Date.backward_month();
			},
			removeEvents:function(){
				$('#onemonthview .day.thisday').removeClass('thisday');
				$('#onemonthview .day .events').html('');
			},
			renderCal:function(){
				$("#datecontrol_date").val(Calendar.UI.formatMonthLong() + Calendar.space + Calendar.Date.current.getFullYear());
				var cal = Calendar.Date.getnumberofdays(Calendar.Date.current.getFullYear());
				var monthview_dayofweek = Calendar.Date.current.getDay();
				var monthview_dayofmonth = Calendar.Date.current.getDate();
				for(var i = monthview_dayofmonth; i > 1; i--) {
					if(monthview_dayofweek == 0) {
						monthview_dayofweek = 6;
					} else {
						monthview_dayofweek--;
					}
				}
				$("#onemonthview .week_5").hide();
				$("#onemonthview .week_6").hide();
				this.rows = monthview_dayofweek + cal[Calendar.Date.current.getMonth()];
				this.rows = this.rows / 7;
				this.rows = Math.ceil(this.rows);
				var dates = this.generateDates();
				var week = 1;
				var weekday = 0;
				var today = new Date();
				for(var i = 0; i <= 41; i++){
					var dayofmonth = dates[i].getDate();
					var month = dates[i].getMonth();
					var year = dates[i].getFullYear();
					$("#onemonthview .week_" + week + " ." + Calendar.UI.weekdays[weekday] + " .dateinfo").html(dayofmonth + Calendar.space + Calendar.UI.formatMonthShort(month));
					if(dayofmonth == today.getDate() && month == today.getMonth() && year == today.getFullYear()){
						$("#onemonthview .week_" + week + " ." + Calendar.UI.weekdays[weekday]).addClass('thisday');
					}
					Calendar.UI.addDateInfo('#onemonthview .week_' + week + ' .' + Calendar.UI.weekdays[weekday], dates[i]);
					if(weekday == 6){
						weekday = 0;
						week++;
					}else{
						weekday++;
					}
				}
				if(this.rows == 4){
					for(var i = 1;i <= 6;i++){
						$("#onemonthview .week_" + String(i)).height("23%");
					}
				}
				if(this.rows == 5) {
					$("#onemonthview .week_5").show();
					for(var i = 1;i <= 6;i++){
						$("#onemonthview .week_" + String(i)).height("18%");
					}
				}
				if(this.rows == 6) {
					$("#onemonthview .week_5").show();
					$("#onemonthview .week_6").show();
					for(var i = 1;i <= 6;i++){
						$("#onemonthview .week_" + String(i)).height("14%");
					}
				}
			},
			showEvents:function(){
				var dates = this.generateDates();
				var weekdaynum = 0;
				var weeknum = 1;
				for(var i = 0; i <= 41; i++) {
					Calendar.UI.createEventsForDate(dates[i], weeknum);
					if(weekdaynum == 6){
						weekdaynum = 0;
						weeknum++;
					}else{
						weekdaynum++;
					}
				}
			},
			getEventContainer:function(week, weekday, when){
				return $("#onemonthview .week_" + week + " .day." + Calendar.UI.weekdays[weekday] + " .events");
			},
			createEventLabel:function(event){
				var time = '';
				if (!event['allday']){
					time = '<strong>' + Calendar.UI.formatTime(event['startdate']) + '</strong> ';
				}
				return $(document.createElement('p'))
					.html(time + event['description'])
			},
			generateDates:function(){
				var dates = new Array();
				var date = new Date(Calendar.Date.current)
				date.setDate(1);
				var dayofweek = date.getDay();
				if(dayofweek == 0) {
					dayofweek = 7;
					this.rows++;
				}
				date.setDate(date.getDate() - dayofweek + 1);
				for(var i = 0; i <= 41; i++) {
					dates[i] = new Date(date)
					date.setDate(date.getDate() + 1);
				}
				return dates;
			},
		},
		List:{
			removeEvents:function(){
				this.eventContainer = $('#listview #events').html('');
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
});
//event vars
Calendar.UI.loadEvents();
