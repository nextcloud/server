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
 * update_view - update the view of the calendar  *
 * onedayview - one day view                      *
 * oneweekview - one week view                    *
 * onemonthview - four Weeks view                 *
 * onemonthview - one Month view                  *
 * listview - listview                            *
 * forward_day - switching one day forward        *
 * forward_week - switching one week forward      *
 * forward_month - switching one month forward    *
 * backward_day - switching one day backward      *
 * backward_week - switching one week backward    *
 * backward_month - switching one month backward  *
 * generate_monthview - generating month view     *
 * generate_dates - generate other days for view  *
 * load_events - load the events                  *
 * switch2today - switching to today              *
 * remove_events - remove old events in view      *
 *************************************************/
function oc_cal_calw() {
	var generate_dayofweek = oc_cal_dayofweek;
	if(generate_dayofweek == 0) {
		generate_dayofweek = 7;
	}
	var calw = Math.floor((oc_cal_doy() - generate_dayofweek) / 7) + 1;
	return calw;
}

function oc_cal_doy() {
	if(oc_cal_checkforleapyear(oc_cal_year) == true) {
		var cal = oc_cal_leap_cal;
	} else {
		var cal = oc_cal_normal_cal;
	}
	var doy = 0;
	for(var i = 0; i < oc_cal_month; i++) {
		doy = parseInt(doy) + parseInt(cal[i]);
	}
	doy = parseInt(doy) + oc_cal_dayofmonth;
	return doy;
}

function oc_cal_checkforleapyear(year2check) {
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
				oc_cal_forward_day();
			}
			if(task == "backward") {
				oc_cal_backward_day();
			}
			oc_cal_remove_events("oneday");
			oc_cal_load_cal("oneday");
			oc_cal_load_events("oneday");
			break;
		case "oneweekview":
			if(task == "forward") {
				oc_cal_forward_week();
			}
			if(task == "backward") {
				oc_cal_backward_week();
			}
			oc_cal_remove_events("oneweek");
			oc_cal_load_cal("oneweek");
			oc_cal_load_events("oneweek");
			break;
		case "fourweeksview":
			if(task == "forward") {
				oc_cal_forward_week();
			}
			if(task == "backward") {
				oc_cal_backward_week();
			}
			oc_cal_remove_events("fourweeks");
			oc_cal_load_cal("fourweeks");
			oc_cal_load_events("fourweeks");
			break;
		case "onemonthview":
			if(task == "forward") {
				oc_cal_forward_month();
			}
			if(task == "backward") {
				oc_cal_backward_month();
			}
			oc_cal_remove_events("onemonth");
			oc_cal_load_cal("onemonth");
			oc_cal_load_events("onemonth");
			break;
		case "listview":
			if(task == "forward") {
				oc_cal_forward_day();
			}
			if(task == "backward") {
				oc_cal_backward_day();
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
		oc_cal_forward_day();
	}
	if(task == "backward") {
		oc_cal_backward_day();
	}
	document.getElementById("datecontrol_date").value = dayshort[dayofweek] + space + dayofmonth + space + monthshort[month] + space + year;
}

function oc_cal_forward_day() {
	if(oc_cal_checkforleapyear(oc_cal_year) == true) {
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
}

function oc_cal_forward_week() {
	for(var i = 1; i <= 7; i++) {
		oc_cal_forward_day();
	}
}

function oc_cal_forward_month() {
	if(oc_cal_checkforleapyear(oc_cal_year) == true) {
		var cal = oc_cal_leap_cal;
	} else {
		var cal = oc_cal_normal_cal;
	}
	for(var i = 1; i <= cal[oc_cal_month]; i++) {
		oc_cal_forward_day();
	}
}

function oc_cal_backward_day() {
	if(oc_cal_checkforleapyear(oc_cal_year) == true) {
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
}

function oc_cal_backward_week() {
	for(var i = 1; i <= 7; i++) {
		oc_cal_backward_day();
	}
}

function oc_cal_backward_month() {
	if(oc_cal_checkforleapyear(oc_cal_year) == true) {
		var cal = oc_cal_leap_cal;
	} else {
		var cal = oc_cal_normal_cal;
	}
	for(var i = cal[oc_cal_month]; i >= 1; i--) {
		oc_cal_backward_day();
	}
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
			if(oc_cal_checkforleapyear(generate_year) == true) {
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
			if(oc_cal_checkforleapyear(generate_year) == true) {
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
			if(oc_cal_checkforleapyear(generate_year) == true) {
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
			if(oc_cal_checkforleapyear(generate_year) == true) {
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
			if(oc_cal_checkforleapyear(generate_year) == true) {
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
			if(oc_cal_checkforleapyear(generate_year) == true) {
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
			if(oc_cal_checkforleapyear(generate_year) == true) {
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
	$("#js_events").load(oc_webroot + "/apps/calendar/ajax/ajax.php?task=load_events&year=" + loadyear);
	if(document.getElementById("js_events").innerHTML == "nosession") {
		alert("You are not logged in. That can happen if you don't use owncloud for a long time.");
		document.location(oc_webroot);
	}
	if(document.getElementById("js_events").innerHTML == "parsingfail" || typeof (newevents) == "undefined") {
		$(function() {
			$( "#parsingfail_dialog" ).dialog();
		});
	} else {
		events.concat(newevents);
	}
}

function oc_cal_load_cal(loadview) {
	if(loadview == "oneday") {
		document.getElementById("datecontrol_date").value = oc_cal_dayshort[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month] + oc_cal_space + oc_cal_year;
		document.getElementById("onedayview_today").innerHTML = oc_cal_daylong[oc_cal_dayofweek] + oc_cal_space + oc_cal_dayofmonth + oc_cal_space + oc_cal_monthshort[oc_cal_month];
	}
	if(loadview == "oneweek") {
		document.getElementById("datecontrol_date").value = "CW: " + oc_cal_calw();
		var dates = oc_cal_generate_dates("oneweek");
		document.getElementById("oneweekview_monday").innerHTML = oc_cal_dayshort[1] + oc_cal_space + dates[0][0] + oc_cal_space + oc_cal_monthshort[dates[0][1]];
		document.getElementById("oneweekview_tuesday").innerHTML = oc_cal_dayshort[2] + oc_cal_space + dates[1][0] + oc_cal_space + oc_cal_monthshort[dates[1][1]];
		document.getElementById("oneweekview_wednesday").innerHTML = oc_cal_dayshort[3] + oc_cal_space + dates[2][0] + oc_cal_space + oc_cal_monthshort[dates[2][1]];
		document.getElementById("oneweekview_thursday").innerHTML = oc_cal_dayshort[4] + oc_cal_space + dates[3][0] + oc_cal_space + oc_cal_monthshort[dates[3][1]];
		document.getElementById("oneweekview_friday").innerHTML = oc_cal_dayshort[5] + oc_cal_space + dates[4][0] + oc_cal_space + oc_cal_monthshort[dates[4][1]];
		document.getElementById("oneweekview_saturday").innerHTML = oc_cal_dayshort[6] + oc_cal_space + dates[5][0] + oc_cal_space + oc_cal_monthshort[dates[5][1]];
		document.getElementById("oneweekview_sunday").innerHTML = oc_cal_dayshort[0] + oc_cal_space + dates[6][0] + oc_cal_space + oc_cal_monthshort[dates[6][1]];
	}
	if(loadview == "fourweeks") {
		var calw1 = oc_cal_calw();
		if(calw1 == 52) { //////////////////////////////// !!!! OPTIMIEREN  !!!! ///////////////////////////////////////////////////////////
			var calw2 = 1;
		} else {
			var calw2 = oc_cal_calw() + 1;
		}
		if(calw1 == 51) {
			var calw3 = 1;
		} else if(calw1 == 52) {
			var calw3 = 2;
		} else {
			var calw3 = oc_cal_calw() + 2;
		}
		if(calw1 == 50) {
			var calw4 = 1;
		} else if(calw1 == 51) {
			var calw4 = 2;
		} else if(calw1 == 52) {
			var calw4 = 3;
		} else {
			var calw4 = oc_cal_calw() + 3;
		}
		var calwplusfour = calw4;
		var dates = oc_cal_generate_dates("fourweeks");
		document.getElementById("dateinfo_fourweeksview_monday_1").innerHTML = dates[0][0] + oc_cal_space + oc_cal_monthshort[dates[0][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_1").innerHTML = dates[1][0] + oc_cal_space + oc_cal_monthshort[dates[1][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_1").innerHTML = dates[2][0] + oc_cal_space + oc_cal_monthshort[dates[2][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_1").innerHTML = dates[3][0] + oc_cal_space + oc_cal_monthshort[dates[3][1]];
		document.getElementById("dateinfo_fourweeksview_friday_1").innerHTML = dates[4][0] + oc_cal_space + oc_cal_monthshort[dates[4][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_1").innerHTML = dates[5][0] + oc_cal_space + oc_cal_monthshort[dates[5][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_1").innerHTML = dates[6][0] + oc_cal_space + oc_cal_monthshort[dates[6][1]];
		document.getElementById("dateinfo_fourweeksview_monday_2").innerHTML = dates[7][0] + oc_cal_space + oc_cal_monthshort[dates[7][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_2").innerHTML = dates[8][0] + oc_cal_space + oc_cal_monthshort[dates[8][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_2").innerHTML = dates[9][0] + oc_cal_space + oc_cal_monthshort[dates[9][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_2").innerHTML = dates[10][0] + oc_cal_space + oc_cal_monthshort[dates[10][1]];
		document.getElementById("dateinfo_fourweeksview_friday_2").innerHTML = dates[11][0] + oc_cal_space + oc_cal_monthshort[dates[11][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_2").innerHTML = dates[12][0] + oc_cal_space + oc_cal_monthshort[dates[12][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_2").innerHTML = dates[13][0] + oc_cal_space + oc_cal_monthshort[dates[13][1]];
		document.getElementById("dateinfo_fourweeksview_monday_3").innerHTML = dates[14][0] + oc_cal_space + oc_cal_monthshort[dates[14][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_3").innerHTML = dates[15][0] + oc_cal_space + oc_cal_monthshort[dates[15][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_3").innerHTML = dates[16][0] + oc_cal_space + oc_cal_monthshort[dates[16][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_3").innerHTML = dates[17][0] + oc_cal_space + oc_cal_monthshort[dates[17][1]];
		document.getElementById("dateinfo_fourweeksview_friday_3").innerHTML = dates[18][0] + oc_cal_space + oc_cal_monthshort[dates[18][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_3").innerHTML = dates[19][0] + oc_cal_space + oc_cal_monthshort[dates[19][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_3").innerHTML = dates[20][0] + oc_cal_space + oc_cal_monthshort[dates[20][1]];
		document.getElementById("dateinfo_fourweeksview_monday_4").innerHTML = dates[21][0] + oc_cal_space + oc_cal_monthshort[dates[21][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_4").innerHTML = dates[22][0] + oc_cal_space + oc_cal_monthshort[dates[22][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_4").innerHTML = dates[23][0] + oc_cal_space + oc_cal_monthshort[dates[23][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_4").innerHTML = dates[24][0] + oc_cal_space + oc_cal_monthshort[dates[24][1]];
		document.getElementById("dateinfo_fourweeksview_friday_4").innerHTML = dates[25][0] + oc_cal_space + oc_cal_monthshort[dates[25][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_4").innerHTML = dates[26][0] + oc_cal_space + oc_cal_monthshort[dates[26][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_4").innerHTML = dates[27][0] + oc_cal_space + oc_cal_monthshort[dates[27][1]];
		document.getElementById("fourweeksview_calw1").innerHTML = calw1;
		document.getElementById("fourweeksview_calw2").innerHTML = calw2;
		document.getElementById("fourweeksview_calw3").innerHTML = calw3;
		document.getElementById("fourweeksview_calw4").innerHTML = calw4;
		document.getElementById("datecontrol_date").value = "CWs: " + oc_cal_calw() + " - " + calwplusfour;
	}
	if(loadview == "onemonth") {
		document.getElementById("datecontrol_date").value = oc_cal_monthlong[oc_cal_month] + oc_cal_space + oc_cal_year;
		if(oc_cal_checkforleapyear(oc_cal_year) == true) {
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
		document.getElementById("dateinfo_onemonthview_monday_1").innerHTML = dates[0][0] + oc_cal_space + oc_cal_monthshort[dates[0][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_1").innerHTML = dates[1][0] + oc_cal_space + oc_cal_monthshort[dates[1][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_1").innerHTML = dates[2][0] + oc_cal_space + oc_cal_monthshort[dates[2][1]];
		document.getElementById("dateinfo_onemonthview_thursday_1").innerHTML = dates[3][0] + oc_cal_space + oc_cal_monthshort[dates[3][1]];
		document.getElementById("dateinfo_onemonthview_friday_1").innerHTML = dates[4][0] + oc_cal_space + oc_cal_monthshort[dates[4][1]];
		document.getElementById("dateinfo_onemonthview_saturday_1").innerHTML = dates[5][0] + oc_cal_space + oc_cal_monthshort[dates[5][1]];
		document.getElementById("dateinfo_onemonthview_sunday_1").innerHTML = dates[6][0] + oc_cal_space + oc_cal_monthshort[dates[6][1]];
		document.getElementById("dateinfo_onemonthview_monday_2").innerHTML = dates[7][0] + oc_cal_space + oc_cal_monthshort[dates[7][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_2").innerHTML = dates[8][0] + oc_cal_space + oc_cal_monthshort[dates[8][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_2").innerHTML = dates[9][0] + oc_cal_space + oc_cal_monthshort[dates[9][1]];
		document.getElementById("dateinfo_onemonthview_thursday_2").innerHTML = dates[10][0] + oc_cal_space + oc_cal_monthshort[dates[10][1]];
		document.getElementById("dateinfo_onemonthview_friday_2").innerHTML = dates[11][0] + oc_cal_space + oc_cal_monthshort[dates[11][1]];
		document.getElementById("dateinfo_onemonthview_saturday_2").innerHTML = dates[12][0] + oc_cal_space + oc_cal_monthshort[dates[12][1]];
		document.getElementById("dateinfo_onemonthview_sunday_2").innerHTML = dates[13][0] + oc_cal_space + oc_cal_monthshort[dates[13][1]];
		document.getElementById("dateinfo_onemonthview_monday_3").innerHTML = dates[14][0] + oc_cal_space + oc_cal_monthshort[dates[14][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_3").innerHTML = dates[15][0] + oc_cal_space + oc_cal_monthshort[dates[15][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_3").innerHTML = dates[16][0] + oc_cal_space + oc_cal_monthshort[dates[16][1]];
		document.getElementById("dateinfo_onemonthview_thursday_3").innerHTML = dates[17][0] + oc_cal_space + oc_cal_monthshort[dates[17][1]];
		document.getElementById("dateinfo_onemonthview_friday_3").innerHTML = dates[18][0] + oc_cal_space + oc_cal_monthshort[dates[18][1]];
		document.getElementById("dateinfo_onemonthview_saturday_3").innerHTML = dates[19][0] + oc_cal_space + oc_cal_monthshort[dates[19][1]];
		document.getElementById("dateinfo_onemonthview_sunday_3").innerHTML = dates[20][0] + oc_cal_space + oc_cal_monthshort[dates[20][1]];
		document.getElementById("dateinfo_onemonthview_monday_4").innerHTML = dates[21][0] + oc_cal_space + oc_cal_monthshort[dates[21][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_4").innerHTML = dates[22][0] + oc_cal_space + oc_cal_monthshort[dates[22][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_4").innerHTML = dates[23][0] + oc_cal_space + oc_cal_monthshort[dates[23][1]];
		document.getElementById("dateinfo_onemonthview_thursday_4").innerHTML = dates[24][0] + oc_cal_space + oc_cal_monthshort[dates[24][1]];
		document.getElementById("dateinfo_onemonthview_friday_4").innerHTML = dates[25][0] + oc_cal_space + oc_cal_monthshort[dates[25][1]];
		document.getElementById("dateinfo_onemonthview_saturday_4").innerHTML = dates[26][0] + oc_cal_space + oc_cal_monthshort[dates[26][1]];
		document.getElementById("dateinfo_onemonthview_sunday_4").innerHTML = dates[27][0] + oc_cal_space + oc_cal_monthshort[dates[27][1]];
		document.getElementById("dateinfo_onemonthview_monday_5").innerHTML = dates[28][0] + oc_cal_space + oc_cal_monthshort[dates[28][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_5").innerHTML = dates[29][0] + oc_cal_space + oc_cal_monthshort[dates[29][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_5").innerHTML = dates[30][0] + oc_cal_space + oc_cal_monthshort[dates[30][1]];
		document.getElementById("dateinfo_onemonthview_thursday_5").innerHTML = dates[31][0] + oc_cal_space + oc_cal_monthshort[dates[31][1]];
		document.getElementById("dateinfo_onemonthview_friday_5").innerHTML = dates[32][0] + oc_cal_space + oc_cal_monthshort[dates[32][1]];
		document.getElementById("dateinfo_onemonthview_saturday_5").innerHTML = dates[33][0] + oc_cal_space + oc_cal_monthshort[dates[33][1]];
		document.getElementById("dateinfo_onemonthview_sunday_5").innerHTML = dates[34][0] + oc_cal_space + oc_cal_monthshort[dates[34][1]];
		document.getElementById("dateinfo_onemonthview_monday_6").innerHTML = dates[35][0] + oc_cal_space + oc_cal_monthshort[dates[35][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_6").innerHTML = dates[36][0] + oc_cal_space + oc_cal_monthshort[dates[36][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_6").innerHTML = dates[37][0] + oc_cal_space + oc_cal_monthshort[dates[37][1]];
		document.getElementById("dateinfo_onemonthview_thursday_6").innerHTML = dates[38][0] + oc_cal_space + oc_cal_monthshort[dates[38][1]];
		document.getElementById("dateinfo_onemonthview_friday_6").innerHTML = dates[39][0] + oc_cal_space + oc_cal_monthshort[dates[39][1]];
		document.getElementById("dateinfo_onemonthview_saturday_6").innerHTML = dates[40][0] + oc_cal_space + oc_cal_monthshort[dates[40][1]];
		document.getElementById("dateinfo_onemonthview_sunday_6").innerHTML = dates[41][0] + oc_cal_space + oc_cal_monthshort[dates[41][1]];
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
					document.getElementById("onedayview_allday_" + eventnumber).innerHTML = events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth]["allday"][eventnumber]["description"];
					eventnumber++;
				}
			}
			for( i = 0; i <= 23; i++) {
				if( typeof (oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth][i]) != "undefined") {
					var eventnumber = 1;
					while( typeof (oc_cal_events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth][i][eventnumber]) != "undefined") {
						var newp = document.createElement("p");
						newp.id = "onedayview_" + i + "_" + eventnumber;
						newp.className = "onedayview_event";
						eventcontainer.appendChild(newp);
						document.getElementById("onedayview_" + i + "_" + eventnumber).innerHTML = events[oc_cal_year][oc_cal_month][oc_cal_dayofmonth][i][eventnumber]["description"];
						eventnumber++;
					}
				}
			}
		}
	}
	if(loadview == "oneweek") {//(generate_dayofmonth, generate_month, generate_year);
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var dates = oc_cal_generate_dates("oneweek");
		document.getElementById("devbox").innerHTML = "";
		for(var i = 0; i <= 6; i++) {
			var loadevents_month = dates[i][0];
			var loadevents_days = dates[i][1];
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
							document.getElementById("oneweekview_" + weekdays[i] + "_allday_" + eventnumber).innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("oneweekview_" + weekdays[i] + "_" + time);
							while( typeof (oc_cal_events[year][loadevents_month][loadevents_days][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "oneweekview_" + i + "_" + eventnumber;
								newp.className = "oneweekview_event";
								eventcontainer.appendChild(newp);
								document.getElementById("oneweekview_" + i + "_" + eventnumber).innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][i][eventnumber]["description"];
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
			var loadevents_month = dates[i][0];
			var loadevents_days = dates[i][1];
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
							document.getElementById("fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum).innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
							pnum++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("events_fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum);
							while( typeof (events[oc_cal_year][loadevents_month][loadevents_days][i][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "fourweeksview_" + i + "_" + eventnumber;
								newp.className = "fourweeksview_event";
								eventcontainer.appendChild(newp);
								document.getElementById("fourweeksview_" + i + "_" + eventnumber).innerHTML = oc_cal_events[year][loadevents_month][loadevents_days][i][eventnumber]["description"];
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
			var loadevents_month = dates[i][0];
			var loadevents_days = dates[i][1];
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
							document.getElementById("onemonthview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum).innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
							pnum++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("events_onemonthview_" + weekdays[weekdaynum] + "_" + weeknum);
							while( typeof (oc_cal_events[year][loadevents_month][loadevents_days][i][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "onemonthview_" + i + "_" + eventnumber;
								newp.className = "onemonthview_event";
								eventcontainer.appendChild(newp);
								document.getElementById("onemonthview_" + i + "_" + eventnumber).innerHTML = oc_cal_events[oc_cal_year][loadevents_month][loadevents_days][i][eventnumber]["description"];
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
		document.getElementById("events_onemonthview_monday_1").innerHTML = "";
		document.getElementById("events_onemonthview_tuesday_1").innerHTML = "";
		document.getElementById("events_onemonthview_wednesday_1").innerHTML = "";
		document.getElementById("events_onemonthview_thursday_1").innerHTML = "";
		document.getElementById("events_onemonthview_friday_1").innerHTML = "";
		document.getElementById("events_onemonthview_saturday_1").innerHTML = "";
		document.getElementById("events_onemonthview_sunday_1").innerHTML = "";
		document.getElementById("events_onemonthview_monday_2").innerHTML = "";
		document.getElementById("events_onemonthview_tuesday_2").innerHTML = "";
		document.getElementById("events_onemonthview_wednesday_2").innerHTML = "";
		document.getElementById("events_onemonthview_thursday_2").innerHTML = "";
		document.getElementById("events_onemonthview_friday_2").innerHTML = "";
		document.getElementById("events_onemonthview_saturday_2").innerHTML = "";
		document.getElementById("events_onemonthview_sunday_2").innerHTML = "";
		document.getElementById("events_onemonthview_monday_3").innerHTML = "";
		document.getElementById("events_onemonthview_tuesday_3").innerHTML = "";
		document.getElementById("events_onemonthview_wednesday_3").innerHTML = "";
		document.getElementById("events_onemonthview_thursday_3").innerHTML = "";
		document.getElementById("events_onemonthview_friday_3").innerHTML = "";
		document.getElementById("events_onemonthview_saturday_3").innerHTML = "";
		document.getElementById("events_onemonthview_sunday_3").innerHTML = "";
		document.getElementById("events_onemonthview_monday_4").innerHTML = "";
		document.getElementById("events_onemonthview_tuesday_4").innerHTML = "";
		document.getElementById("events_onemonthview_wednesday_4").innerHTML = "";
		document.getElementById("events_onemonthview_thursday_4").innerHTML = "";
		document.getElementById("events_onemonthview_friday_4").innerHTML = "";
		document.getElementById("events_onemonthview_saturday_4").innerHTML = "";
		document.getElementById("events_onemonthview_sunday_4").innerHTML = "";
		document.getElementById("events_onemonthview_monday_5").innerHTML = "";
		document.getElementById("events_onemonthview_tuesday_5").innerHTML = "";
		document.getElementById("events_onemonthview_wednesday_5").innerHTML = "";
		document.getElementById("events_onemonthview_thursday_5").innerHTML = "";
		document.getElementById("events_onemonthview_friday_5").innerHTML = "";
		document.getElementById("events_onemonthview_saturday_5").innerHTML = "";
		document.getElementById("events_onemonthview_sunday_5").innerHTML = "";
		document.getElementById("events_onemonthview_monday_6").innerHTML = "";
		document.getElementById("events_onemonthview_tuesday_6").innerHTML = "";
		document.getElementById("events_onemonthview_wednesday_6").innerHTML = "";
		document.getElementById("events_onemonthview_thursday_6").innerHTML = "";
		document.getElementById("events_onemonthview_friday_6").innerHTML = "";
		document.getElementById("events_onemonthview_saturday_6").innerHTML = "";
		document.getElementById("events_onemonthview_sunday_6").innerHTML = "";
	}
	if(removeview == "list") {
		document.getElementById("listview").innerHTML = "";
	}
}