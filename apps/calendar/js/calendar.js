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
 * onemonthview - four Weeks view                 *
 * onemonthview - one Month view                  *
 * listview - listview                            *
 * generate_monthview - generating month view     *
 * generate_dates - generate other days for view  *
 * load_events - load the events                  *
 * switch2today - switching to today              *
 * remove_events - remove old events in view      *
 *************************************************/
Calendar={
	Date:{
		calw:function() {
			var generate_dayofweek = oc_cal_dayofweek;
			if(generate_dayofweek == 0) {
				generate_dayofweek = 7;
			}
			var calw = Math.floor((this.doy() - generate_dayofweek) / 7) + 1;
			return calw;
		},

		doy:function() {
			if(this.checkforleapyear(oc_cal_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			var doy = 0;
			for(var i = 0; i < oc_cal_month; i++) {
				doy = doy + parseInt(cal[i]);
			}
			doy = doy + parseInt(oc_cal_dayofmonth);
			return doy;
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
			if(this.checkforleapyear(oc_cal_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(oc_cal_dayofmonth == cal[oc_cal_month]) {
				if(oc_cal_month == 11) {
					oc_cal_year++;
					oc_cal_month = 0;
					oc_cal_dayofmonth = 1;
					if(oc_cal_dayofweek == 6) {
						oc_cal_dayofweek = 0;
					} else {
						oc_cal_dayofweek++;
					}
				} else {
					oc_cal_month++;
					oc_cal_dayofmonth = 1;
					if(oc_cal_dayofweek == 6) {
						oc_cal_dayofweek = 0;
					} else {
						oc_cal_dayofweek++;
					}
				}
			} else {
				oc_cal_dayofmonth++;
				if(oc_cal_dayofweek == 6) {
					oc_cal_dayofweek = 0;
				} else {
					oc_cal_dayofweek++;
				}
			}
		},

		forward_week:function(){
			for(var i = 1; i <= 7; i++) {
				this.forward_day();
			}
		},

		forward_month:function(){
			if(this.checkforleapyear(oc_cal_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			for(var i = 1; i <= cal[oc_cal_month]; i++) {
				this.forward_day();
			}
		},

		backward_day:function(){
			if(this.checkforleapyear(oc_cal_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(oc_cal_dayofmonth == 1) {
				if(oc_cal_month == 0) {
					oc_cal_year--;
					oc_cal_month = 11;
					oc_cal_dayofmonth = 31
					if(oc_cal_dayofweek == 0) {
						oc_cal_dayofweek = 6;
					} else {
						oc_cal_dayofweek--;
					}
				} else {
					oc_cal_month--;
					oc_cal_dayofmonth = cal[oc_cal_month];
					if(oc_cal_dayofweek == 0) {
						oc_cal_dayofweek = 6;
					} else {
						oc_cal_dayofweek--;
					}
				}
			} else {
				oc_cal_dayofmonth--;
				if(oc_cal_dayofweek == 0) {
					oc_cal_dayofweek = 6;
				} else {
					oc_cal_dayofweek--;
				}
			}
		},

		backward_week:function(){
			for(var i = 1; i <= 7; i++) {
				this.backward_day();
			}
		},

		backward_month:function(){
			if(this.checkforleapyear(oc_cal_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			for(var i = cal[oc_cal_month]; i >= 1; i--) {
				this.backward_day();
			}
		},

	}
}

function oc_cal_update_view(view, task) {
	if(view == "") {
		view = oc_cal_currentview;
	}
	$("#sysbox").load(oc_webroot + "/apps/calendar/ajax/changeview.php?v="+view+"");
	//no necessary to check whether the response is true or not
	switch(view) {
		case "onedayview":
			if(task == "forward") {
				Calendar.Date.forward_day();
			}
			if(task == "backward") {
				Calendar.Date.backward_day();
			}
			oc_cal_remove_events("oneday");
			oc_cal_load_cal("oneday");
			oc_cal_load_events("oneday");
			break;
		case "oneweekview":
			if(task == "forward") {
				Calendar.Date.forward_week();
			}
			if(task == "backward") {
				Calendar.Date.backward_week();
			}
			oc_cal_remove_events("oneweek");
			oc_cal_load_cal("oneweek");
			oc_cal_load_events("oneweek");
			break;
		case "fourweeksview":
			if(task == "forward") {
				Calendar.Date.forward_week();
			}
			if(task == "backward") {
				Calendar.Date.backward_week();
			}
			oc_cal_remove_events("fourweeks");
			oc_cal_load_cal("fourweeks");
			oc_cal_load_events("fourweeks");
			break;
		case "onemonthview":
			if(task == "forward") {
				Calendar.Date.forward_month();
			}
			if(task == "backward") {
				Calendar.Date.backward_month();
			}
			oc_cal_remove_events("onemonth");
			oc_cal_load_cal("onemonth");
			oc_cal_load_events("onemonth");
			break;
		case "listview":
			if(task == "forward") {
				Calendar.Date.forward_day();
			}
			if(task == "backward") {
				Calendar.Date.backward_day();
			}
			oc_cal_remove_events("list");
			oc_cal_load_cal("list");
			oc_cal_load_events("list");
			break;
		default:
			break;
	}
	if(oc_cal_month == 0){
		oc_cal_update_eventsvar(oc_cal_year - 1);
	}
	if(oc_cal_month == 11){
		oc_cal_update_eventsvar(oc_cal_year + 1);
	}
}

function oc_cal_listview(task) {
	if(task == "forward") {
		Calendar.Date.forward_day();
	}
	if(task == "backward") {
		Calendar.Date.backward_day();
	}
	document.getElementById("datecontrol_date").value = dayshort[dayofweek] + space + dayofmonth + space + monthshort[month] + space + year;
}

function oc_cal_generate_dates(view) {
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if(view == "oneweek") {
		var generate_dayofweek = oc_cal_dayofweek;
		var generate_dayofmonth = oc_cal_dayofmonth;
		var generate_month = oc_cal_month;
		var generate_year = oc_cal_year;
		var dates = new Array();
		if(generate_dayofweek == 0) {
			generate_dayofweek = 7;
		}
		for(var i = generate_dayofweek; i > 1; i--) {
			if(Calendar.Date.checkforleapyear(generate_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(generate_dayofmonth == 1) {
				if(generate_month == 0) {
					generate_year--;
					generate_month = 11;
					generate_dayofmonth = cal[generate_month];
				} else {
					generate_month--;
					generate_dayofmonth = cal[generate_month];
				}
			} else {
				generate_dayofmonth--;
			}
			generate_dayofweek--;
		}
		dates[0] = new Array(generate_dayofmonth, generate_month, generate_year);
		for(var i = 1; i <= 6; i++) {
			if(Calendar.Date.checkforleapyear(generate_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(generate_dayofmonth == cal[generate_month]) {
				if(generate_month == 11) {
					generate_year++;
					generate_month = 0;
					generate_dayofmonth = 1;
				} else {
					generate_month++;
					generate_dayofmonth = 1;
				}
			} else {
				generate_dayofmonth++;
			}
			dates[i] = new Array(generate_dayofmonth, generate_month, generate_year);
		}
		return dates;
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if(view == "fourweeks") {
		var generate_dayofweek = oc_cal_dayofweek;
		var generate_dayofmonth = oc_cal_dayofmonth;
		var generate_month = oc_cal_month;
		var generate_year = oc_cal_year;
		var dates = new Array();
		if(generate_dayofweek == 0) {
			generate_dayofweek = 7;
		}
		for(var i = generate_dayofweek; i > 1; i--) {
			if(Calendar.Date.checkforleapyear(generate_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(generate_dayofmonth == 1) {
				if(generate_month == 0) {
					generate_year--;
					generate_month = 11;
					generate_dayofmonth = cal[generate_month];
				} else {
					generate_month--;
					generate_dayofmonth = cal[generate_month];
				}
			} else {
				generate_dayofmonth--;
			}
			generate_dayofweek--;
		}
		dates[0] = new Array(generate_dayofmonth, generate_month, generate_year);
		for(var i = 1; i <= 27; i++) {
			if(Calendar.Date.checkforleapyear(generate_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(generate_dayofmonth == cal[generate_month]) {
				if(generate_month == 11) {
					generate_year++;
					generate_month = 0;
					generate_dayofmonth = 1;
				} else {
					generate_month++;
					generate_dayofmonth = 1;
				}
			} else {
				generate_dayofmonth++;
			}
			dates[i] = new Array(generate_dayofmonth, generate_month, generate_year);
		}
		return dates;
	}
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if(view == "onemonth") {
		var generate_dayofweek = oc_cal_dayofweek;
		var generate_dayofmonth = oc_cal_dayofmonth;
		var generate_month = oc_cal_month;
		var generate_year = oc_cal_year;
		var dates = new Array();
		for(var i = generate_dayofmonth; i > 1; i--) {
			if(Calendar.Date.checkforleapyear(generate_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(generate_dayofmonth == 1) {
				if(generate_month == 0) {
					generate_year--;
					generate_month = 11;
					generate_dayofmonth = cal[generate_month];
				} else {
					generate_month--;
					generate_dayofmonth = cal[generate_month];
				}
			} else {
				generate_dayofmonth--;
			}
			if(generate_dayofweek == 0) {
				generate_dayofweek = 6;
			} else {
				generate_dayofweek--;
			}
		}
		if(generate_dayofweek == 0) {
			generate_dayofweek = 7;
			oc_cal_rows++;
		}
		for(var i = generate_dayofweek; i > 1; i--) {
			if(Calendar.Date.checkforleapyear(generate_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(generate_dayofmonth == 1) {
				if(generate_month == 0) {
					generate_year--;
					generate_month = 11;
					generate_dayofmonth = cal[generate_month];
				} else {
					generate_month--;
					generate_dayofmonth = cal[generate_month];
				}
			} else {
				generate_dayofmonth--;
			}
			generate_dayofweek--;
		}
		dates[0] = new Array(generate_dayofmonth, generate_month, generate_year);
		for(var i = 1; i <= 41; i++) {
			if(Calendar.Date.checkforleapyear(generate_year) == true) {
				var cal = oc_cal_leap_cal;
			} else {
				var cal = oc_cal_normal_cal;
			}
			if(generate_dayofmonth == cal[generate_month]) {
				if(generate_month == 11) {
					generate_year++;
					generate_month = 0;
					generate_dayofmonth = 1;
				} else {
					generate_month++;
					generate_dayofmonth = 1;
				}
			} else {
				generate_dayofmonth++;
			}
			dates[i] = new Array(generate_dayofmonth, generate_month, generate_year);
		}
		return dates;
	} else {////////////////////////////////////////////////////////////////////////////////////////////////////
		return false;
	}
}

function oc_cal_switch2today() {
	oc_cal_date = oc_cal_today;
	oc_cal_dayofweek = oc_cal_todaydayofweek;
	oc_cal_month = oc_cal_todaymonth;
	oc_cal_dayofmonth = oc_cal_todaydayofmonth;
	oc_cal_year = oc_cal_todayyear;
	oc_cal_update_view('', '');
}

function oc_cal_update_eventsvar(loadyear) {
	$.getJSON(oc_webroot + "/apps/calendar/ajax/getcal.php?year=" + loadyear, function(newevents, status) {
	if(status == "nosession") {
		alert("You are not logged in. That can happen if you don't use owncloud for a long time.");
		document.location(oc_webroot);
	}
	if(status == "parsingfail" || typeof (newevents) == "undefined") {
		$(function() {
			$( "#parsingfail_dialog" ).dialog();
		});
	} else {
		oc_cal_events[loadyear] = newevents[loadyear];
		oc_cal_update_view('', '');
	}
	});
}

function oc_cal_load_cal(loadview) {
	if(loadview == "oneday") {
		document.getElementById("datecontrol_date").value = oc_cal_dayshort[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month] + oc_cal_space + oc_cal_year;
		document.getElementById("onedayview_today").innerHTML = oc_cal_daylong[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month];
		var generate_dayofmonth = oc_cal_dayofmonth;
		var generate_month = oc_cal_month;
		var generate_year = oc_cal_year;
		if(parseInt(generate_dayofmonth) <= 9){
			generate_dayofmonth = "0" + generate_dayofmonth;
		}
		generate_month++;
		if(parseInt(generate_month) <= 9){
			generate_month = "0" + generate_month;
		}
		var generate_title = String(generate_dayofmonth) + String(generate_month) + String(generate_year);
		document.getElementById('onedayview_today').title = generate_title;
	}
	if(loadview == "oneweek") {
		document.getElementById("datecontrol_date").value = cw_label + ": " + Calendar.Date.calw();
		var dates = oc_cal_generate_dates("oneweek");
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var weekday = 1;
		for(var i = 0; i <= 6; i++){
			var generate_dayofmonth = String(dates[i][0]);
			var generate_month = String(dates[i][1]);
			document.getElementById("oneweekview_" + weekdays[i]).innerHTML = oc_cal_dayshort[weekday] + oc_cal_space + dates[i][0] + oc_cal_space + oc_cal_monthshort[dates[i][1]];
			if(parseInt(generate_dayofmonth) <= 9){
				generate_dayofmonth = "0" + generate_dayofmonth;
			}
			generate_month++;
			if(parseInt(generate_month) <= 9){
				generate_month = "0" + generate_month;
			}
			var generate_title = String(generate_dayofmonth) + String(generate_month) + String(dates[i][2]);
			document.getElementById("oneweekview_" + weekdays[i]).title = generate_title;
			if(weekday == 6){
				weekday = 0;
			}else{
				weekday++;
			}
		}
	}
	if(loadview == "fourweeks") {
		var calw1 = Calendar.Date.calw();
		if(calw1 == 52) {
			var calw2 = 1;
		} else {
			var calw2 = Calendar.Date.calw() + 1;
		}
		if(calw1 == 51) {
			var calw3 = 1;
		} else if(calw1 == 52) {
			var calw3 = 2;
		} else {
			var calw3 = Calendar.Date.calw() + 2;
		}
		if(calw1 == 50) {
			var calw4 = 1;
		} else if(calw1 == 51) {
			var calw4 = 2;
		} else if(calw1 == 52) {
			var calw4 = 3;
		} else {
			var calw4 = Calendar.Date.calw() + 3;
		}
		var calwplusfour = calw4;
		var dates = oc_cal_generate_dates("fourweeks");
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var weeknum = 1;
		var weekday = 0;
		for(var i = 0; i <= 27; i++){
			var generate_dayofmonth = String(dates[i][0]);
			var generate_month = String(dates[i][1]);
			var generate_year = dates[i][2];
			document.getElementById("dateinfo_fourweeksview_" + weekdays[weekday] + "_" + weeknum).innerHTML = generate_dayofmonth + oc_cal_space + oc_cal_monthshort[generate_month]; 
			if(parseInt(generate_dayofmonth) <= 9){
				generate_dayofmonth = "0" + generate_dayofmonth;
			}
			if(generate_dayofmonth == oc_cal_todaydayofmonth && generate_month == oc_cal_todaymonth && generate_year == oc_cal_todayyear){
				document.getElementById("fourweeksview_" + weekdays[weekday] + "_" + weeknum).className = "thisday";
			}else{
				document.getElementById("fourweeksview_" + weekdays[weekday] + "_" + weeknum).className = "fourweeksview_item";
			}
			generate_month++;
			if(parseInt(generate_month) <= 9){
				generate_month = "0" + generate_month;
			}
			var generate_title = String(generate_dayofmonth) + String(generate_month) + String(dates[i][2]);
			document.getElementById("fourweeksview_" + weekdays[weekday] + "_" + weeknum).title = generate_title;
			if(weekday == 6){
				weekday = 0;
				weeknum++;
			}else{
				weekday++;
			}
		}
		document.getElementById("fourweeksview_calw1").innerHTML = calw1;
		document.getElementById("fourweeksview_calw2").innerHTML = calw2;
		document.getElementById("fourweeksview_calw3").innerHTML = calw3;
		document.getElementById("fourweeksview_calw4").innerHTML = calw4;
		document.getElementById("datecontrol_date").value = cws_label + ": " + Calendar.Date.calw() + " - " + calwplusfour;
	}
	if(loadview == "onemonth") {
		document.getElementById("datecontrol_date").value = oc_cal_monthlong[oc_cal_month] + oc_cal_space + oc_cal_year;
		if(Calendar.Date.checkforleapyear(oc_cal_year) == true) {
			var cal = oc_cal_leap_cal;
		} else {
			var cal = oc_cal_normal_cal;
		}
		var monthview_dayofweek = oc_cal_dayofweek;
		var monthview_dayofmonth = oc_cal_dayofmonth;
		for(var i = monthview_dayofmonth; i > 1; i--) {
			if(monthview_dayofweek == 0) {
				monthview_dayofweek = 6;
			} else {
				monthview_dayofweek--;
			}
		}
		document.getElementById("onemonthview_week_5").style.display = "none";
		document.getElementById("onemonthview_week_6").style.display = "none";
		oc_cal_rows = parseInt(monthview_dayofweek) + parseInt(cal[oc_cal_month]);
		oc_cal_rows = oc_cal_rows / 7;
		oc_cal_rows = Math.ceil(oc_cal_rows);
		var dates = oc_cal_generate_dates("onemonth");
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var weeknum = 1;
		var weekday = 0;
		for(var i = 0; i <= 41; i++){
			var generate_dayofmonth = dates[i][0];
			var generate_month = dates[i][1];
			var generate_year = dates[i][2];
			document.getElementById("dateinfo_onemonthview_" + weekdays[weekday] + "_" + weeknum).innerHTML = generate_dayofmonth + oc_cal_space + oc_cal_monthshort[generate_month];
			if(parseInt(generate_dayofmonth) <= 9){
				generate_dayofmonth = "0" + generate_dayofmonth;
			}
			if(generate_dayofmonth == oc_cal_todaydayofmonth && generate_month == oc_cal_todaymonth && generate_year == oc_cal_todayyear){
				document.getElementById("onemonthview_" + weekdays[weekday] + "_" + weeknum).className = "thisday";
			}else{
				document.getElementById("onemonthview_" + weekdays[weekday] + "_" + weeknum).className = "onemonthview_item";
			}
			generate_month++;
			if(parseInt(generate_month) <= 9){
				generate_month = "0" + generate_month;
			}
			var generate_title = String(generate_dayofmonth) + String(generate_month) + String(generate_year);
			document.getElementById("onemonthview_" + weekdays[weekday] + "_" + weeknum).title = generate_title;
			if(weekday == 6){
				weekday = 0;
				weeknum++;
			}else{
				weekday++;
			}
		}
		if(oc_cal_rows == 5) {
			document.getElementById("onemonthview_week_5").style.display = "table-row";
		}
		if(oc_cal_rows == 6) {
			document.getElementById("onemonthview_week_5").style.display = "table-row";
			document.getElementById("onemonthview_week_6").style.display = "table-row";
		}
	}
	if(loadview == "list") {
		document.getElementById("datecontrol_date").value = oc_cal_dayshort[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month] + oc_cal_space + oc_cal_year;
	}
}

function oc_cal_load_events(loadview) {
	if(loadview == "oneday") {
		if( typeof (oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth]) != "undefined") {
			if( typeof (oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth]["allday"]) != "undefined") {
				var eventnumber = 1;
				var eventcontainer = document.getElementById("onedayview_wholeday");
				while( typeof (oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth]["allday"][eventnumber]) != "undefined") {
					var newp = document.createElement("p");
					newp.id = "onedayview_allday_" + eventnumber;
					newp.className = "onedayview_event";
					eventcontainer.appendChild(newp);
					newp.innerHTML = oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth]["allday"][eventnumber]["description"];
					eventnumber++;
				}
			}
			for( i = 0; i <= 23; i++) {
				if( typeof (oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth][i]) != "undefined") {
					var eventnumber = 1;
					var eventcontainer = document.getElementById("onedayview_" + i);
					while( typeof (oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth][i][eventnumber]) != "undefined") {
						var newp = document.createElement("p");
						newp.id = "onedayview_" + i + "_" + eventnumber;
						newp.className = "onedayview_event";
						eventcontainer.appendChild(newp);
						newp.innerHTML = oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth][i][eventnumber]["description"];
						eventnumber++;
					}
				}
			}
		}
	}
	if(loadview == "oneweek") {//(generate_dayofmonth, generate_month, generate_year);
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var dates = oc_cal_generate_dates("oneweek");
		for(var i = 0; i <= 6; i++) {
			var loadevents_month = dates[i][1];
			var loadevents_days = dates[i][0];
			if( typeof (oc_cal_events[oc_cal_year][loadevents_month]) != "undefined") {
				if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]) != "undefined") {
					if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"]) != "undefined") {
						var eventnumber = 1;
						var eventcontainer = document.getElementById("oneweekview_" + weekdays[i] + "_allday");
						while( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]) != "undefined") {
							var newp = document.createElement("p");
							newp.id = "oneweekview_" + weekdays[i] + "_allday_" + eventnumber;
							newp.className = "oneweekview_event";
							eventcontainer.appendChild(newp);
							newp.innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("oneweekview_" + weekdays[i] + "_" + time);
							while( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "oneweekview_" + weekdays[i] + "_" + time + "_" + eventnumber;
								newp.className = "oneweekview_event";
								eventcontainer.appendChild(newp);
								newp.innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time][eventnumber]["description"];
								eventnumber++;
							}
						}
					}
				}
			}
		}
	}
	if(loadview == "fourweeks") {
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var dates = oc_cal_generate_dates("fourweeks");
		var weekdaynum = 0;
		var weeknum = 1;
		for(var i = 0; i <= 27; i++) {
			var loadevents_month = dates[i][1];
			var loadevents_days = dates[i][0];
			if( typeof (oc_cal_events[oc_cal_year][loadevents_month]) != "undefined") {
				if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]) != "undefined") {
					var pnum = 0;
					if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"]) != "undefined") {
						var eventnumber = 1;
						var eventcontainer = document.getElementById("events_fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum);
						while( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]) != "undefined") {
							var newp = document.createElement("p");
							newp.id = "fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum;
							newp.className = "fourweeksview_event";
							eventcontainer.appendChild(newp);
							newp.innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
							pnum++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("events_fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum);
							while( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "fourweeksview_" + weekdays[i] + "_" + i + "_" + eventnumber;
								newp.className = "fourweeksview_event";
								eventcontainer.appendChild(newp);
								newp.innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time][eventnumber]["description"];
								eventnumber++;
								pnum++;
							}
						}
					}
				}
			}
			if(weekdaynum == 6){
				weekdaynum = 0;
				weeknum++;
			}else{
				weekdaynum++;
			}
		}
	}
	if(loadview == "onemonth") {
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var dates = oc_cal_generate_dates("onemonth");
		var weekdaynum = 0;
		var weeknum = 1;
		for(var i = 0; i <= 41; i++) {
			var loadevents_month = dates[i][1];
			var loadevents_days = dates[i][0];
			if( typeof (oc_cal_events[oc_cal_year][loadevents_month]) != "undefined") {
				if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]) != "undefined") {
					var pnum = 0;
					if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"]) != "undefined") {
						var eventnumber = 1;
						var eventcontainer = document.getElementById("events_onemonthview_" + weekdays[weekdaynum] + "_" + weeknum);
						while( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]) != "undefined") {
							var newp = document.createElement("p");
							newp.id = "onemonthview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum;
							newp.className = "onemonthview_event";
							eventcontainer.appendChild(newp);
							newp.innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
							pnum++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("events_onemonthview_" + weekdays[weekdaynum] + "_" + weeknum);
							while( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "onemonthview_" + weekdays[i] + "_" + time + "_" + eventnumber;
								newp.className = "onemonthview_event";
								eventcontainer.appendChild(newp);
								newp.innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time][eventnumber]["description"];
								eventnumber++;
								pnum++;
							}
						}
					}
				}
			}
			if(weekdaynum == 6){
				weekdaynum = 0;
				weeknum++;
			}else{
				weekdaynum++;
			}
		}
	}
	if(loadview == "list") {
		//
	}
}

function oc_cal_remove_events(removeview) {
	if(removeview == "oneday") {
		document.getElementById("onedayview_wholeday").innerHTML = "";
		for(var i = 0; i <= 23; i++) {
			document.getElementById("onedayview_" + i).innerHTML = "";
		}
	}
	if(removeview == "oneweek") {
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		for( i = 0; i <= 6; i++) {
			document.getElementById("oneweekview_" + weekdays[i] + "_allday").innerHTML = "";
			for(var time = 0; time <= 23; time++) {
				document.getElementById("oneweekview_" + weekdays[i] + "_" + time).innerHTML = "";
			}
		}
	}
	if(removeview == "fourweeks") {
		document.getElementById("events_fourweeksview_monday_1").innerHTML = "";
		document.getElementById("events_fourweeksview_tuesday_1").innerHTML = "";
		document.getElementById("events_fourweeksview_wednesday_1").innerHTML = "";
		document.getElementById("events_fourweeksview_thursday_1").innerHTML = "";
		document.getElementById("events_fourweeksview_friday_1").innerHTML = "";
		document.getElementById("events_fourweeksview_saturday_1").innerHTML = "";
		document.getElementById("events_fourweeksview_sunday_1").innerHTML = "";
		document.getElementById("events_fourweeksview_monday_2").innerHTML = "";
		document.getElementById("events_fourweeksview_tuesday_2").innerHTML = "";
		document.getElementById("events_fourweeksview_wednesday_2").innerHTML = "";
		document.getElementById("events_fourweeksview_thursday_2").innerHTML = "";
		document.getElementById("events_fourweeksview_friday_2").innerHTML = "";
		document.getElementById("events_fourweeksview_saturday_2").innerHTML = "";
		document.getElementById("events_fourweeksview_sunday_2").innerHTML = "";
		document.getElementById("events_fourweeksview_monday_3").innerHTML = "";
		document.getElementById("events_fourweeksview_tuesday_3").innerHTML = "";
		document.getElementById("events_fourweeksview_wednesday_3").innerHTML = "";
		document.getElementById("events_fourweeksview_thursday_3").innerHTML = "";
		document.getElementById("events_fourweeksview_friday_3").innerHTML = "";
		document.getElementById("events_fourweeksview_saturday_3").innerHTML = "";
		document.getElementById("events_fourweeksview_sunday_3").innerHTML = "";
		document.getElementById("events_fourweeksview_monday_4").innerHTML = "";
		document.getElementById("events_fourweeksview_tuesday_4").innerHTML = "";
		document.getElementById("events_fourweeksview_wednesday_4").innerHTML = "";
		document.getElementById("events_fourweeksview_thursday_4").innerHTML = "";
		document.getElementById("events_fourweeksview_friday_4").innerHTML = "";
		document.getElementById("events_fourweeksview_saturday_4").innerHTML = "";
		document.getElementById("events_fourweeksview_sunday_4").innerHTML = "";
	}
	if(removeview == "onemonth") {
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var weeknum = 1;
		var weekday = 0;
		for(var i = 0; i <= 41; i++){//events_onemonthview_saturday_6
			document.getElementById("events_onemonthview_" + weekdays[weekday] + "_" + weeknum).innerHTML = "";
			document.getElementById("onemonthview_" + weekdays[weekday] + "_" + weeknum).className = "onemonthview_item";
			if(weekday == 6){
				weekday = 0;
				weeknum++;
			}else{
				weekday++;
			}
		}
	}
	if(removeview == "list") {
		document.getElementById("listview").innerHTML = "";
	}
}
function oc_cal_newevent(date, time){
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
