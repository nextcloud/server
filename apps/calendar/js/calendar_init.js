/************************************************
* ownCloud - Calendar Plugin                    *
*                                               *
* (c) Copyright 2011 Georg Ehrke                *
* author: Georg Ehrke                           *
* email: ownclouddev at georgswebsite dot de    *
* homepage: http://ownclouddev.georgswebsite.de *
* License: GPL                                  *
* <http://www.gnu.org/licenses/>.               *
************************************************/
//loading Buttons
$(function(){
	$("#view").buttonset();
	$("#choosecalendar").buttonset();
	$("#datecontrol_left").button();
	$("#datecontrol_left").button({ disabled: true });
	$("#datecontrol_date").button();
	$("#datecontrol_right").button();
	$("#datecontrol_right").button({ disabled: true });
	$(".choosecalendar_check").button();
	$("#oneday").button();
	$("#oneweek").button();
	$("#fourweek").button();
	$("#onemonth").button();
	$("#list").button();
});
//init date vars
var date = new Date();
var dayofweek = date.getDay();
var month = date.getMonth();
var dayofmonth = date.getDate();
var year = date.getFullYear();
var daylong = new Array("Sunday", "Monday", "Tuesday", "Wednesday", "Thursday", "Friday", "Saturday");
var dayshort = new Array("Sun.", "Mon.", "Tue.", "Wed.", "Thu.", "Fri.", "Sat.");
var monthlong = new Array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
var monthshort = new Array("Jan.", "Feb.", "Mar.", "Apr.", "May", "Jun.", "Jul.", "Aug.", "Sep.", "Oct.", "Nov.", "Dec.");
var space = " ";
var normal_cal = new Array("31","28", "31", "30", "31", "30", "31", "31", "30", "31", "30", "31");
var leap_cal = new Array("31","29", "31", "30", "31", "30", "31", "31", "30", "31", "30", "31");
//init today date vars
var today = new Date();
var todaydayofweek = today.getDay();
var todaymonth = today.getMonth();
var todaydayofmonth = today.getDate();
var todayyear = today.getFullYear();
//other vars
var rows;
var dates;
var listview_numofevents = 0;
var listview_count = 0;
//event vars
var events = new Array(2011);
events[2011] = new Array(0,1,2,3,4,5,6,7,8,9,10,11);
events[2011][7] = new Array();
events[2011][7][7] = new Array();
events[2011][7][7]["allday"] = new Array(1, 2);
events[2011][7][7]["allday"][1] = new Array("description");
events[2011][7][7]["allday"][1]["description"] = "abc";
events[2011][7][7]["allday"][2] = new Array("description");
events[2011][7][7]["allday"][2]["description"] = "ghfgh";
events[2011][7][13] = new Array();
