/*************************************************
 * ownCloud - Calendar Plugin                     *
 *                                                *
 * (c) Copyright 2011 Georg Ehrke                 *
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
	Date:{
		normal_year_cal: [31, 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
		leap_year_cal: [31, 29, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31],
		calw:function() {
			var dayofweek = oc_cal_dayofweek;
			if(dayofweek == 0) {
				dayofweek = 7;
			}
			var calw = Math.floor((this.doy() - dayofweek) / 7) + 1;
			return calw;
		},

		doy:function() {
			var cal = this.getnumberofdays(oc_cal_year);
			var doy = 0;
			for(var i = 0; i < oc_cal_month; i++) {
				doy = doy + parseInt(cal[i]);
			}
			doy = doy + parseInt(oc_cal_dayofmonth);
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

		forward_day:function(){
			var cal = this.getnumberofdays(oc_cal_year);
			if(oc_cal_dayofmonth == cal[oc_cal_month]) {
				if(oc_cal_month == 11) {
					oc_cal_year++;
					oc_cal_month = 0;
					oc_cal_dayofmonth = 1;
				} else {
					oc_cal_month++;
					oc_cal_dayofmonth = 1;
				}
			} else {
				oc_cal_dayofmonth++;
			}
			if(oc_cal_dayofweek == 6) {
				oc_cal_dayofweek = 0;
			} else {
				oc_cal_dayofweek++;
			}
		},

		forward_week:function(){
			for(var i = 1; i <= 7; i++) {
				this.forward_day();
			}
		},

		forward_month:function(){
			var cal = this.getnumberofdays(oc_cal_year);
			for(var i = 1; i <= cal[oc_cal_month]; i++) {
				this.forward_day();
			}
		},

		backward_day:function(){
			var cal = this.getnumberofdays(oc_cal_year);
			if(oc_cal_dayofmonth == 1) {
				if(oc_cal_month == 0) {
					oc_cal_year--;
					oc_cal_month = 11;
					oc_cal_dayofmonth = 31
				} else {
					oc_cal_month--;
					oc_cal_dayofmonth = cal[oc_cal_month];
				}
			} else {
				oc_cal_dayofmonth--;
			}
			if(oc_cal_dayofweek == 0) {
				oc_cal_dayofweek = 6;
			} else {
				oc_cal_dayofweek--;
			}
		},

		backward_week:function(){
			for(var i = 1; i <= 7; i++) {
				this.backward_day();
			}
		},

		backward_month:function(){
			var cal = this.getnumberofdays(oc_cal_year);
			for(var i = cal[oc_cal_month]; i >= 1; i--) {
				this.backward_day();
			}
		},

	},
	UI:{
		weekdays: ["monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday"],
		updateView:function(task) {
			this.current.removeEvents();
			this.current.renderCal();
			this.current.showEvents();
		},
		setCurrentView:function(view){
			if (view == oc_cal_currentview){
				return;
			}
			$('#'+oc_cal_currentview).hide();
			$('#'+oc_cal_currentview + "_radio").removeClass('active');
			oc_cal_currentview = view;
			//sending ajax request on every change view
			$("#sysbox").load(oc_webroot + "/apps/calendar/ajax/changeview.php?v="+view);
			//not necessary to check whether the response is true or not
			switch(view) {
				case "onedayview":
					this.current = Calendar.UI.OneDay;
					break;
				case "oneweekview":
					this.current = Calendar.UI.OneWeek;
					break;
				case "fourweeksview":
					this.current = Calendar.UI.FourWeeks;
					break;
				case "onemonthview":
					this.current = Calendar.UI.OneMonth;
					break;
				case "listview":
					this.current = Calendar.UI.List;
					break;
				default:
					break;
			}
			$('#'+oc_cal_currentview).show();
			$('#'+oc_cal_currentview + "_radio").addClass('active');
			this.updateView();
		},
		updateDate:function(direction){
			if(direction == "forward") {
				this.current.forward();
				if(oc_cal_month == 11){
					this.loadEvents(oc_cal_year + 1);
				}
				Calendar.UI.updateView();
			}
			if(direction == "backward") {
				this.current.backward();
				if(oc_cal_month == 0){
					this.loadEvents(oc_cal_year - 1);
				}
				Calendar.UI.updateView();
			}
		},
		loadEvents:function(year){
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
					oc_cal_events[year] = newevents[year];
					//$.ready(function() {
					Calendar.UI.updateView();
					//});
				}
			});
		},
		createEventsForDate:function(date, week, weekday){
			var day = date[0];
			var month = date[1];
			var year = date[2];
			if( typeof (oc_cal_events[year]) == "undefined") {
				return;
			}
			if( typeof (oc_cal_events[year][month]) == "undefined") {
				return;
			}
			if( typeof (oc_cal_events[year][month][day]) == "undefined") {
				return;
			}
			events = oc_cal_events[year][month][day];
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
				       this.hideEventPopup);
			eventcontainer.append(event_holder);
		},
		createEventPopup:function(e){
			var event = $(this).data('event_info');
			var popup = $(this).data('popup');
			if (!popup){
				popup = $(document.createElement('div'));
				$(this).data('popup', popup).append(popup);
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
		formatDate:function(date){
			return date[0] + '-' + date[1] + '-' + date[2];
		},
		formatTime:function(date){
			return date[3] + ':' + date[4];
		},
		addDateInfo:function(selector, date){
			var dayofmonth = date[0];
			var month = date[1];
			var year = date[2];
			if(parseInt(dayofmonth) <= 9){
				dayofmonth = "0" + dayofmonth;
			}
			month++;
			if(parseInt(month) <= 9){
				month = "0" + month;
			}
			var title = String(dayofmonth) + String(month) + String(year);
			$(selector).data('date_info', title);
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
				$("#datecontrol_date").val(oc_cal_dayshort[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month] + oc_cal_space + oc_cal_year);
				$("#onedayview_today").html(oc_cal_daylong[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month]);
				Calendar.UI.addDateInfo('#onedayview_today', [oc_cal_dayofmonth, oc_cal_month, oc_cal_year]);
			},
			showEvents:function(){
				Calendar.UI.createEventsForDate([oc_cal_dayofmonth, oc_cal_month, oc_cal_year], 0, 0);
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
				$("#datecontrol_date").val(cw_label + ": " + Calendar.Date.calw());
				var dates = this.generateDates();
				for(var i = 0; i <= 6; i++){
					$("#oneweekview th." + Calendar.UI.weekdays[i]).html(oc_cal_dayshort[(i+1)%7] + oc_cal_space + dates[i][0] + oc_cal_space + oc_cal_monthshort[dates[i][1]]);
					if(dates[i][0] == oc_cal_todaydayofmonth && dates[i][1] == oc_cal_todaymonth && dates[i][2] == oc_cal_todayyear){
						$("#oneweekview ." + Calendar.UI.weekdays[i]).addClass("thisday");
					}
					Calendar.UI.addDateInfo('#oneweekview th.' + Calendar.UI.weekdays[i], dates[i]);
				}
			},
			showEvents:function(){
				var dates = this.generateDates();
				for(var weekday = 0; weekday <= 6; weekday++) {
					Calendar.UI.createEventsForDate(dates[weekday], 0, weekday);
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
				var date = new Date(oc_cal_year, oc_cal_month, oc_cal_dayofmonth);
				var dayofweek = date.getDay();
				if(dayofweek == 0) {
					dayofweek = 7;
				}
				date.setDate(date.getDate() - dayofweek + 1);
				for(var i = 0; i <= 6; i++) {
					dates[i] = new Array(date.getDate(), date.getMonth(), date.getFullYear());
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
				for(var i = 0; i <= 27; i++){
					var dayofmonth = dates[i][0];
					var month = dates[i][1];
					var year = dates[i][2];
					$("#fourweeksview .week_" + week + " ." + Calendar.UI.weekdays[weekday] + " .dateinfo").html(dayofmonth + oc_cal_space + oc_cal_monthshort[month]);
					if(dayofmonth == oc_cal_todaydayofmonth && month == oc_cal_todaymonth && year == oc_cal_todayyear){
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
				$("#datecontrol_date").val(cws_label + ": " + Calendar.Date.calw() + " - " + calwplusfour);
			},
			showEvents:function(){
				var dates = this.generateDates();
				var weekdaynum = 0;
				var weeknum = 1;
				for(var i = 0; i <= 27; i++) {
					Calendar.UI.createEventsForDate(dates[i], weeknum, weekdaynum);
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
				var date = new Date(oc_cal_year, oc_cal_month, oc_cal_dayofmonth);
				var dayofweek = date.getDay();
				if(dayofweek == 0) {
					dayofweek = 7;
				}
				date.setDate(date.getDate() - dayofweek + 1);
				for(var i = 0; i <= 27; i++) {
					dates[i] = new Array(date.getDate(), date.getMonth(), date.getFullYear());
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
				$("#datecontrol_date").val(oc_cal_monthlong[oc_cal_month] + oc_cal_space + oc_cal_year);
				var cal = Calendar.Date.getnumberofdays(oc_cal_year);
				var monthview_dayofweek = oc_cal_dayofweek;
				var monthview_dayofmonth = oc_cal_dayofmonth;
				for(var i = monthview_dayofmonth; i > 1; i--) {
					if(monthview_dayofweek == 0) {
						monthview_dayofweek = 6;
					} else {
						monthview_dayofweek--;
					}
				}
				$("#onemonthview .week_5").hide();
				$("#onemonthview .week_6").hide();
				oc_cal_rows = parseInt(monthview_dayofweek) + parseInt(cal[oc_cal_month]);
				oc_cal_rows = oc_cal_rows / 7;
				oc_cal_rows = Math.ceil(oc_cal_rows);
				var dates = this.generateDates();
				var week = 1;
				var weekday = 0;
				for(var i = 0; i <= 41; i++){
					var dayofmonth = dates[i][0];
					var month = dates[i][1];
					var year = dates[i][2];
					$("#onemonthview .week_" + week + " ." + Calendar.UI.weekdays[weekday] + " .dateinfo").html(dayofmonth + oc_cal_space + oc_cal_monthshort[month]);
					if(dayofmonth == oc_cal_todaydayofmonth && month == oc_cal_todaymonth && year == oc_cal_todayyear){
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
				if(oc_cal_rows == 4){
					for(var i = 1;i <= 6;i++){
						$("#onemonthview .week_" + String(i)).height("23%");
					}
				}
				if(oc_cal_rows == 5) {
					$("#onemonthview .week_5").show();
					for(var i = 1;i <= 6;i++){
						$("#onemonthview .week_" + String(i)).height("18%");
					}
				}
				if(oc_cal_rows == 6) {
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
					Calendar.UI.createEventsForDate(dates[i], weeknum, weekdaynum);
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
				var date = new Date(oc_cal_year, oc_cal_month, oc_cal_dayofmonth);
				date.setDate(1);
				var dayofweek = date.getDay();
				if(dayofweek == 0) {
					dayofweek = 7;
					oc_cal_rows++;
				}
				date.setDate(date.getDate() - dayofweek + 1);
				for(var i = 0; i <= 41; i++) {
					dates[i] = new Array(date.getDate(), date.getMonth(), date.getFullYear());
					date.setDate(date.getDate() + 1);
				}
				return dates;
			},
		},
		List:{
			forward:function(){
				Calendar.Date.forward_day();
			},
			backward:function(){
				Calendar.Date.backward_day();
			},
			removeEvents:function(){
				$("#listview").html("");
			},
			renderCal:function(){
				$("#datecontrol_date").val(oc_cal_dayshort[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month] + oc_cal_space + oc_cal_year);
			},
			showEvents:function(){
			},
			getEventContainer:function(week, weekday, when){
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

function oc_cal_switch2today() {
	oc_cal_date = oc_cal_today;
	oc_cal_dayofweek = oc_cal_todaydayofweek;
	oc_cal_month = oc_cal_todaymonth;
	oc_cal_dayofmonth = oc_cal_todaydayofmonth;
	oc_cal_year = oc_cal_todayyear;
	Calendar.UI.updateView();
}

function oc_cal_newevent(selector, time){
	var date = $(selector).data('date_info');
	if(oc_cal_opendialog == 0){
		$("#dialog_holder").load(oc_webroot + "/apps/calendar/ajax/neweventform.php?d=" + date + "&t=" + time);
		oc_cal_opendialog = 1;
	}else{
		alert(t("calendar", "You can't open more than one dialog per site!"));
	}
}
function oc_cal_choosecalendar(){
	if(oc_cal_opendialog == 0){
		$("#dialog_holder").load(oc_webroot + "/apps/calendar/ajax/choosecalendar.php");
		oc_cal_opendialog = 1;
	}else{
		alert(t("calendar", "You can't open more than one dialog per site!"));
	}
}
function oc_cal_calender_activation(checkbox, calendarid)
{
	$.post(oc_webroot + "/apps/calendar/ajax/activation.php", { calendarid: calendarid, active: checkbox.checked?1:0 },
	  function(data) {
		checkbox.checked = data == 1;
		Calendar.UI.loadEvents(oc_cal_year);
	  });
}
function oc_cal_editcalendar(object, calendarid){
	$(object).closest('tr').load(oc_webroot + "/apps/calendar/ajax/editcalendar.php?calendarid="+calendarid);
}
function oc_cal_editcalendar_submit(button, calendarid){
	var displayname = $("#displayname_"+calendarid).val();
	var active = $("#active_"+calendarid+":checked").length;
	var description = $("#description_"+calendarid).val();
	var calendarcolor = $("#calendarcolor_"+calendarid).val();

	$.post("ajax/updatecalendar.php", { id: calendarid, name: displayname, active: active, description: description, color: calendarcolor },
		function(data){
			if(data.error == "true"){
			}else{
				$(button).closest('tr').html(data.data)
			}
		}, 'json');
}
