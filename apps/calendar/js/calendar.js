/*************************************************
 * ownCloud - Calendar Plugin                     *
 *                                                *
 * (c) Copyright 2011 Georg Ehrke                 *
 * author: Georg Ehrke                            *
 * email: ownclouddev at georgswebsite dot de     *
 * homepage: ownclouddev.georgswebsite.de         *
 * manual: ownclouddev.georgswebsite.de/manual    *
 * License: GNU General Public License (GPL)      *
 *                                                *
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
function calw() {
	var generate_dayofweek = dayofweek;
	if(generate_dayofweek == 0) {
		generate_dayofweek = 7;
	}
	var calw = Math.floor((doy() - generate_dayofweek) / 7) + 1;
	return calw;
}

function doy() {
	if(checkforleapyear(year) == true) {
		var cal = leap_cal;
	} else {
		var cal = normal_cal;
	}
	var doy = 0;
	for(var i = 0; i < month; i++) {
		doy = parseInt(doy) + parseInt(cal[i]);
	}
	doy = parseInt(doy) + dayofmonth;
	return doy;
}

function checkforleapyear(year2check) {
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

function update_view(view, task) {
	if(view == "") {
		view = currentview;
	}
	switch(view) {
		case "onedayview":
			if(task == "forward") {
				forward_day();
			}
			if(task == "backward") {
				backward_day();
			}
			remove_events("oneday");
			load_cal("oneday");
			load_events("oneday");
			break;
		case "oneweekview":
			if(task == "forward") {
				forward_week();
			}
			if(task == "backward") {
				backward_week();
			}
			remove_events("oneweek");
			load_cal("oneweek");
			load_events("oneweek");
			break;
		case "fourweeksview":
			if(task == "forward") {
				forward_week();
			}
			if(task == "backward") {
				backward_week();
			}
			remove_events("fourweeks");
			load_cal("fourweeks");
			load_events("fourweeks");
			break;
		case "onemonthview":
			if(task == "forward") {
				forward_month();
			}
			if(task == "backward") {
				backward_month();
			}
			remove_events("onemonth");
			load_cal("onemonth");
			load_events("onemonth");
			break;
		case "listview":
			if(task == "forward") {
				forward_day();
			}
			if(task == "backward") {
				backward_day();
			}
			remove_events("list");
			load_cal("list");
			load_events("list");
			break;
		default:
			return false;
	}
}

function listview(task) {
	if(task == "forward") {
		forward_day();
	}
	if(task == "backward") {
		backward_day();
	}
	document.getElementById("datecontrol_date_label").innerHTML = dayshort[dayofweek] + space + dayofmonth + space + monthshort[month] + space + year;
}

function forward_day() {
	if(checkforleapyear(year) == true) {
		var cal = leap_cal;
	} else {
		var cal = normal_cal;
	}
	if(dayofmonth == cal[month]) {
		if(month == 11) {
			date.setFullYear(year++, month = 0, dayofmonth = 1);
			if(dayofweek == 6) {
				dayofweek = 0;
			} else {
				dayofweek++;
			}
		} else {
			date.setMonth(month++, dayofmonth = 1);
			if(dayofweek == 6) {
				dayofweek = 0;
			} else {
				dayofweek++;
			}
		}
	} else {
		date.setDate(dayofmonth++);
		if(dayofweek == 6) {
			dayofweek = 0;
		} else {
			dayofweek++;
		}
	}
	if(month == 11) {
		update_eventsvar(year + 1);
	}
}

function forward_week() {
	for(var i = 1; i <= 7; i++) {
		forward_day();
	}
}

function forward_month() {
	if(checkforleapyear(year) == true) {
		var cal = leap_cal;
	} else {
		var cal = normal_cal;
	}
	for(var i = 1; i <= cal[month]; i++) {
		forward_day();
	}
}

function backward_day() {
	if(checkforleapyear(year) == true) {
		var cal = leap_cal;
	} else {
		var cal = normal_cal;
	}
	if(dayofmonth == 1) {
		if(month == 0) {
			date.setFullYear(year--, month = 11, dayofmonth = 31);
			if(dayofweek == 0) {
				dayofweek = 6;
			} else {
				dayofweek--;
			}
		} else {
			date.setMonth(month--, dayofmonth = cal[month]);
			if(dayofweek == 0) {
				dayofweek = 6;
			} else {
				dayofweek--;
			}
		}
	} else {
		date.setDate(dayofmonth--);
		if(dayofweek == 0) {
			dayofweek = 6;
		} else {
			dayofweek--;
		}
	}
	if(month == 0) {
		update_eventsvar( year - 1);
	}
}

function backward_week() {
	for(var i = 1; i <= 7; i++) {
		backward_day();
	}
}

function backward_month() {
	if(checkforleapyear(year) == true) {
		var cal = leap_cal;
	} else {
		var cal = normal_cal;
	}
	for(var i = cal[month]; i >= 1; i--) {
		backward_day();
	}
}

function generate_dates(view) {
	//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	if(view == "oneweek") {
		var generate_dayofweek = dayofweek;
		var generate_dayofmonth = dayofmonth;
		var generate_month = month;
		var generate_year = year;
		var dates = new Array();
		if(generate_dayofweek == 0) {
			generate_dayofweek = 7;
		}
		for(var i = generate_dayofweek; i > 1; i--) {
			if(checkforleapyear(generate_year) == true) {
				var cal = leap_cal;
			} else {
				var cal = normal_cal;
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
			if(checkforleapyear(generate_year) == true) {
				var cal = leap_cal;
			} else {
				var cal = normal_cal;
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
		var generate_dayofweek = dayofweek;
		var generate_dayofmonth = dayofmonth;
		var generate_month = month;
		var generate_year = year;
		var dates = new Array();
		if(generate_dayofweek == 0) {
			generate_dayofweek = 7;
		}
		for(var i = generate_dayofweek; i > 1; i--) {
			if(checkforleapyear(generate_year) == true) {
				var cal = leap_cal;
			} else {
				var cal = normal_cal;
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
			if(checkforleapyear(generate_year) == true) {
				var cal = leap_cal;
			} else {
				var cal = normal_cal;
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
		var generate_dayofweek = dayofweek;
		var generate_dayofmonth = dayofmonth;
		var generate_month = month;
		var generate_year = year;
		var dates = new Array();
		for(var i = generate_dayofmonth; i > 1; i--) {
			if(checkforleapyear(generate_year) == true) {
				var cal = leap_cal;
			} else {
				var cal = normal_cal;
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
			rows++;
		}
		for(var i = generate_dayofweek; i > 1; i--) {
			if(checkforleapyear(generate_year) == true) {
				var cal = leap_cal;
			} else {
				var cal = normal_cal;
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
			if(checkforleapyear(generate_year) == true) {
				var cal = leap_cal;
			} else {
				var cal = normal_cal;
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

function switch2today() {
	date = today;
	dayofweek = todaydayofweek;
	month = todaymonth;
	dayofmonth = todaydayofmonth;
	year = todayyear;
	update_view('', '');
}

function load_events(view, date) {

}

function update_eventsvar(loadyear) {
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

function load_cal(loadview) {
	if(loadview == "oneday") {
		document.getElementById("datecontrol_date_label").innerHTML = dayshort[dayofweek] + space + dayofmonth + space + monthshort[month] + space + year;
		document.getElementById("onedayview_today").innerHTML = daylong[dayofweek] + space + dayofmonth + space + monthshort[month];
	}
	if(loadview == "oneweek") {
		document.getElementById("datecontrol_date_label").innerHTML = "CW: " + calw();
		var dates = generate_dates("oneweek");
		document.getElementById("oneweekview_monday").innerHTML = dayshort[1] + space + dates[0][0] + space + monthshort[dates[0][1]];
		document.getElementById("oneweekview_tuesday").innerHTML = dayshort[2] + space + dates[1][0] + space + monthshort[dates[1][1]];
		document.getElementById("oneweekview_wednesday").innerHTML = dayshort[3] + space + dates[2][0] + space + monthshort[dates[2][1]];
		document.getElementById("oneweekview_thursday").innerHTML = dayshort[4] + space + dates[3][0] + space + monthshort[dates[3][1]];
		document.getElementById("oneweekview_friday").innerHTML = dayshort[5] + space + dates[4][0] + space + monthshort[dates[4][1]];
		document.getElementById("oneweekview_saturday").innerHTML = dayshort[6] + space + dates[5][0] + space + monthshort[dates[5][1]];
		document.getElementById("oneweekview_sunday").innerHTML = dayshort[0] + space + dates[6][0] + space + monthshort[dates[6][1]];
	}
	if(loadview == "fourweeks") {
		var calw1 = calw();
		if(calw1 == 52) { //////////////////////////////// !!!! OPTIMIEREN  !!!! ///////////////////////////////////////////////////////////
			var calw2 = 1;
		} else {
			var calw2 = calw() + 1;
		}
		if(calw1 == 51) {
			var calw3 = 1;
		} else if(calw1 == 52) {
			var calw3 = 2;
		} else {
			var calw3 = calw() + 2;
		}
		if(calw1 == 50) {
			var calw4 = 1;
		} else if(calw1 == 51) {
			var calw4 = 2;
		} else if(calw1 == 52) {
			var calw4 = 3;
		} else {
			var calw4 = calw() + 3;
		}
		var calwplusfour = calw4;
		var dates = generate_dates("fourweeks");
		document.getElementById("dateinfo_fourweeksview_monday_1").innerHTML = dates[0][0] + space + monthshort[dates[0][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_1").innerHTML = dates[1][0] + space + monthshort[dates[1][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_1").innerHTML = dates[2][0] + space + monthshort[dates[2][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_1").innerHTML = dates[3][0] + space + monthshort[dates[3][1]];
		document.getElementById("dateinfo_fourweeksview_friday_1").innerHTML = dates[4][0] + space + monthshort[dates[4][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_1").innerHTML = dates[5][0] + space + monthshort[dates[5][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_1").innerHTML = dates[6][0] + space + monthshort[dates[6][1]];
		document.getElementById("dateinfo_fourweeksview_monday_2").innerHTML = dates[7][0] + space + monthshort[dates[7][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_2").innerHTML = dates[8][0] + space + monthshort[dates[8][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_2").innerHTML = dates[9][0] + space + monthshort[dates[9][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_2").innerHTML = dates[10][0] + space + monthshort[dates[10][1]];
		document.getElementById("dateinfo_fourweeksview_friday_2").innerHTML = dates[11][0] + space + monthshort[dates[11][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_2").innerHTML = dates[12][0] + space + monthshort[dates[12][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_2").innerHTML = dates[13][0] + space + monthshort[dates[13][1]];
		document.getElementById("dateinfo_fourweeksview_monday_3").innerHTML = dates[14][0] + space + monthshort[dates[14][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_3").innerHTML = dates[15][0] + space + monthshort[dates[15][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_3").innerHTML = dates[16][0] + space + monthshort[dates[16][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_3").innerHTML = dates[17][0] + space + monthshort[dates[17][1]];
		document.getElementById("dateinfo_fourweeksview_friday_3").innerHTML = dates[18][0] + space + monthshort[dates[18][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_3").innerHTML = dates[19][0] + space + monthshort[dates[19][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_3").innerHTML = dates[20][0] + space + monthshort[dates[20][1]];
		document.getElementById("dateinfo_fourweeksview_monday_4").innerHTML = dates[21][0] + space + monthshort[dates[21][1]];
		document.getElementById("dateinfo_fourweeksview_tuesday_4").innerHTML = dates[22][0] + space + monthshort[dates[22][1]];
		document.getElementById("dateinfo_fourweeksview_wednesday_4").innerHTML = dates[23][0] + space + monthshort[dates[23][1]];
		document.getElementById("dateinfo_fourweeksview_thursday_4").innerHTML = dates[24][0] + space + monthshort[dates[24][1]];
		document.getElementById("dateinfo_fourweeksview_friday_4").innerHTML = dates[25][0] + space + monthshort[dates[25][1]];
		document.getElementById("dateinfo_fourweeksview_saturday_4").innerHTML = dates[26][0] + space + monthshort[dates[26][1]];
		document.getElementById("dateinfo_fourweeksview_sunday_4").innerHTML = dates[27][0] + space + monthshort[dates[27][1]];
		document.getElementById("fourweeksview_calw1").innerHTML = calw1;
		document.getElementById("fourweeksview_calw2").innerHTML = calw2;
		document.getElementById("fourweeksview_calw3").innerHTML = calw3;
		document.getElementById("fourweeksview_calw4").innerHTML = calw4;
		document.getElementById("datecontrol_date_label").innerHTML = "CWs: " + calw() + " - " + calwplusfour;
	}
	if(loadview == "onemonth") {
		document.getElementById("datecontrol_date_label").innerHTML = monthlong[month] + space + year;
		if(checkforleapyear(year) == true) {
			var cal = leap_cal;
		} else {
			var cal = normal_cal;
		}
		var monthview_dayofweek = dayofweek;
		var monthview_dayofmonth = dayofmonth;
		for(var i = monthview_dayofmonth; i > 1; i--) {
			if(monthview_dayofweek == 0) {
				monthview_dayofweek = 6;
			} else {
				monthview_dayofweek--;
			}
		}
		document.getElementById("onemonthview_week_5").style.display = "none";
		document.getElementById("onemonthview_week_6").style.display = "none";
		rows = parseInt(monthview_dayofweek) + parseInt(cal[month]);
		rows = rows / 7;
		rows = Math.ceil(rows);
		var dates = generate_dates("onemonth");
		document.getElementById("dateinfo_onemonthview_monday_1").innerHTML = dates[0][0] + space + monthshort[dates[0][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_1").innerHTML = dates[1][0] + space + monthshort[dates[1][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_1").innerHTML = dates[2][0] + space + monthshort[dates[2][1]];
		document.getElementById("dateinfo_onemonthview_thursday_1").innerHTML = dates[3][0] + space + monthshort[dates[3][1]];
		document.getElementById("dateinfo_onemonthview_friday_1").innerHTML = dates[4][0] + space + monthshort[dates[4][1]];
		document.getElementById("dateinfo_onemonthview_saturday_1").innerHTML = dates[5][0] + space + monthshort[dates[5][1]];
		document.getElementById("dateinfo_onemonthview_sunday_1").innerHTML = dates[6][0] + space + monthshort[dates[6][1]];
		document.getElementById("dateinfo_onemonthview_monday_2").innerHTML = dates[7][0] + space + monthshort[dates[7][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_2").innerHTML = dates[8][0] + space + monthshort[dates[8][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_2").innerHTML = dates[9][0] + space + monthshort[dates[9][1]];
		document.getElementById("dateinfo_onemonthview_thursday_2").innerHTML = dates[10][0] + space + monthshort[dates[10][1]];
		document.getElementById("dateinfo_onemonthview_friday_2").innerHTML = dates[11][0] + space + monthshort[dates[11][1]];
		document.getElementById("dateinfo_onemonthview_saturday_2").innerHTML = dates[12][0] + space + monthshort[dates[12][1]];
		document.getElementById("dateinfo_onemonthview_sunday_2").innerHTML = dates[13][0] + space + monthshort[dates[13][1]];
		document.getElementById("dateinfo_onemonthview_monday_3").innerHTML = dates[14][0] + space + monthshort[dates[14][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_3").innerHTML = dates[15][0] + space + monthshort[dates[15][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_3").innerHTML = dates[16][0] + space + monthshort[dates[16][1]];
		document.getElementById("dateinfo_onemonthview_thursday_3").innerHTML = dates[17][0] + space + monthshort[dates[17][1]];
		document.getElementById("dateinfo_onemonthview_friday_3").innerHTML = dates[18][0] + space + monthshort[dates[18][1]];
		document.getElementById("dateinfo_onemonthview_saturday_3").innerHTML = dates[19][0] + space + monthshort[dates[19][1]];
		document.getElementById("dateinfo_onemonthview_sunday_3").innerHTML = dates[20][0] + space + monthshort[dates[20][1]];
		document.getElementById("dateinfo_onemonthview_monday_4").innerHTML = dates[21][0] + space + monthshort[dates[21][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_4").innerHTML = dates[22][0] + space + monthshort[dates[22][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_4").innerHTML = dates[23][0] + space + monthshort[dates[23][1]];
		document.getElementById("dateinfo_onemonthview_thursday_4").innerHTML = dates[24][0] + space + monthshort[dates[24][1]];
		document.getElementById("dateinfo_onemonthview_friday_4").innerHTML = dates[25][0] + space + monthshort[dates[25][1]];
		document.getElementById("dateinfo_onemonthview_saturday_4").innerHTML = dates[26][0] + space + monthshort[dates[26][1]];
		document.getElementById("dateinfo_onemonthview_sunday_4").innerHTML = dates[27][0] + space + monthshort[dates[27][1]];
		document.getElementById("dateinfo_onemonthview_monday_5").innerHTML = dates[28][0] + space + monthshort[dates[28][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_5").innerHTML = dates[29][0] + space + monthshort[dates[29][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_5").innerHTML = dates[30][0] + space + monthshort[dates[30][1]];
		document.getElementById("dateinfo_onemonthview_thursday_5").innerHTML = dates[31][0] + space + monthshort[dates[31][1]];
		document.getElementById("dateinfo_onemonthview_friday_5").innerHTML = dates[32][0] + space + monthshort[dates[32][1]];
		document.getElementById("dateinfo_onemonthview_saturday_5").innerHTML = dates[33][0] + space + monthshort[dates[33][1]];
		document.getElementById("dateinfo_onemonthview_sunday_5").innerHTML = dates[34][0] + space + monthshort[dates[34][1]];
		document.getElementById("dateinfo_onemonthview_monday_6").innerHTML = dates[35][0] + space + monthshort[dates[35][1]];
		document.getElementById("dateinfo_onemonthview_tuesday_6").innerHTML = dates[36][0] + space + monthshort[dates[36][1]];
		document.getElementById("dateinfo_onemonthview_wednesday_6").innerHTML = dates[37][0] + space + monthshort[dates[37][1]];
		document.getElementById("dateinfo_onemonthview_thursday_6").innerHTML = dates[38][0] + space + monthshort[dates[38][1]];
		document.getElementById("dateinfo_onemonthview_friday_6").innerHTML = dates[39][0] + space + monthshort[dates[39][1]];
		document.getElementById("dateinfo_onemonthview_saturday_6").innerHTML = dates[40][0] + space + monthshort[dates[40][1]];
		document.getElementById("dateinfo_onemonthview_sunday_6").innerHTML = dates[41][0] + space + monthshort[dates[41][1]];
		if(rows == 5) {
			document.getElementById("onemonthview_week_5").style.display = "table-row";
		}
		if(rows == 6) {
			document.getElementById("onemonthview_week_5").style.display = "table-row";
			document.getElementById("onemonthview_week_6").style.display = "table-row";
		}
	}
	if(loadview == "list") {
		document.getElementById("datecontrol_date_label").innerHTML = dayshort[dayofweek] + space + dayofmonth + space + monthshort[month] + space + year;
	}
}

function load_events(loadview) {
	if(loadview == "oneday") {
		if( typeof (events[year][month][dayofmonth]) != "undefined") {
			if( typeof (events[year][month][dayofmonth]["allday"]) != "undefined") {
				var eventnumber = 1;
				var eventcontainer = document.getElementById("onedayview_wholeday");
				while( typeof (events[year][month][dayofmonth]["allday"][eventnumber]) != "undefined") {
					var newp = document.createElement("p");
					newp.id = "onedayview_allday_" + eventnumber;
					newp.className = "onedayview_event";
					eventcontainer.appendChild(newp);
					document.getElementById("onedayview_allday_" + eventnumber).innerHTML = events[year][month][dayofmonth]["allday"][eventnumber]["description"];
					eventnumber++;
				}
			}
			for( i = 0; i <= 23; i++) {
				if( typeof (events[year][month][dayofmonth][i]) != "undefined") {
					var eventnumber = 1;
					while( typeof (events[year][month][dayofmonth][i][eventnumber]) != "undefined") {
						var newp = document.createElement("p");
						newp.id = "onedayview_" + i + "_" + eventnumber;
						newp.className = "onedayview_event";
						eventcontainer.appendChild(newp);
						document.getElementById("onedayview_" + i + "_" + eventnumber).innerHTML = events[year][month][dayofmonth][i][eventnumber]["description"];
						eventnumber++;
					}
				}
			}
		}
	}
	if(loadview == "oneweek") {//(generate_dayofmonth, generate_month, generate_year);
		var weekdays = new Array("monday", "tuesday", "wednesday", "thursday", "friday", "saturday", "sunday");
		var dates = generate_dates("oneweek");
		document.getElementById("devbox").innerHTML = "";
		for(var i = 0; i <= 6; i++) {
			var loadevents_month = dates[i][0];
			var loadevents_days = dates[i][1];
			if( typeof (events[year][loadevents_month]) != "undefined") {
				if( typeof (events[year][loadevents_month][loadevents_days]) != "undefined") {
					if( typeof (events[year][loadevents_month][loadevents_days]["allday"]) != "undefined") {
						var eventnumber = 1;
						var eventcontainer = document.getElementById("oneweekview_" + weekdays[i] + "_allday");
						while( typeof (events[year][loadevents_month][loadevents_days]["allday"][eventnumber]) != "undefined") {
							var newp = document.createElement("p");
							newp.id = "oneweekview_" + weekdays[i] + "_allday_" + eventnumber;
							newp.className = "oneweekview_event";
							eventcontainer.appendChild(newp);
							document.getElementById("oneweekview_" + weekdays[i] + "_allday_" + eventnumber).innerHTML = events[year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (events[year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("oneweekview_" + weekdays[i] + "_" + time);
							while( typeof (events[year][loadevents_month][loadevents_days][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "oneweekview_" + i + "_" + eventnumber;
								newp.className = "oneweekview_event";
								eventcontainer.appendChild(newp);
								document.getElementById("oneweekview_" + i + "_" + eventnumber).innerHTML = events[year][loadevents_month][loadevents_days][i][eventnumber]["description"];
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
		var dates = generate_dates("fourweeks");
		var weekdaynum = 0;
		var weeknum = 1;
		for(var i = 0; i <= 27; i++) {
			var loadevents_month = dates[i][0];
			var loadevents_days = dates[i][1];
			if( typeof (events[year][loadevents_month]) != "undefined") {
				if( typeof (events[year][loadevents_month][loadevents_days]) != "undefined") {
					var pnum = 0;
					if( typeof (events[year][loadevents_month][loadevents_days]["allday"]) != "undefined") {
						var eventnumber = 1;
						var eventcontainer = document.getElementById("events_fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum);
						while( typeof (events[year][loadevents_month][loadevents_days]["allday"][eventnumber]) != "undefined") {
							var newp = document.createElement("p");
							newp.id = "fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum;
							newp.className = "fourweeksview_event";
							eventcontainer.appendChild(newp);
							document.getElementById("fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum).innerHTML = events[year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
							pnum++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (events[year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("events_fourweeksview_" + weekdays[weekdaynum] + "_" + weeknum);
							while( typeof (events[year][loadevents_month][loadevents_days][i][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "fourweeksview_" + i + "_" + eventnumber;
								newp.className = "fourweeksview_event";
								eventcontainer.appendChild(newp);
								document.getElementById("fourweeksview_" + i + "_" + eventnumber).innerHTML = events[year][loadevents_month][loadevents_days][i][eventnumber]["description"];
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
		var dates = generate_dates("onemonth");
		var weekdaynum = 0;
		var weeknum = 1;
		for(var i = 0; i <= 41; i++) {
			var loadevents_month = dates[i][0];
			var loadevents_days = dates[i][1];
			if( typeof (events[year][loadevents_month]) != "undefined") {
				if( typeof (events[year][loadevents_month][loadevents_days]) != "undefined") {
					var pnum = 0;
					if( typeof (events[year][loadevents_month][loadevents_days]["allday"]) != "undefined") {
						var eventnumber = 1;
						var eventcontainer = document.getElementById("events_onemonthview_" + weekdays[weekdaynum] + "_" + weeknum);
						while( typeof (events[year][loadevents_month][loadevents_days]["allday"][eventnumber]) != "undefined") {
							var newp = document.createElement("p");
							newp.id = "onemonthview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum;
							newp.className = "onemonthview_event";
							eventcontainer.appendChild(newp);
							document.getElementById("onemonthview_" + weekdays[weekdaynum] + "_" + weeknum + "_" + pnum).innerHTML = events[year][loadevents_month][loadevents_days]["allday"][eventnumber]["description"];
							eventnumber++;
							pnum++;
						}
					}
					for(var time = 0; time <= 23; time++) {
						if( typeof (events[year][loadevents_month][loadevents_days][time]) != "undefined") {
							var eventnumber = 1;
							var eventcontainer = document.getElementById("events_onemonthview_" + weekdays[weekdaynum] + "_" + weeknum);
							while( typeof (events[year][loadevents_month][loadevents_days][i][eventnumber]) != "undefined") {
								var newp = document.createElement("p");
								newp.id = "onemonthview_" + i + "_" + eventnumber;
								newp.className = "onemonthview_event";
								eventcontainer.appendChild(newp);
								document.getElementById("onemonthview_" + i + "_" + eventnumber).innerHTML = events[year][loadevents_month][loadevents_days][i][eventnumber]["description"];
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

function remove_events(removeview) {
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