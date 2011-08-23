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
 *************************************************/
//loading multiselect
$(document).ready(function(){
	$("#calendar_select").multiSelect({
		selectedText: "Calendars",
		noneSelectedText: "Calendars",
		selectedList: 0,
		close: function(){
			alert("abc");
   		}
	});
});
//init date vars
var oc_cal_date = new Date();
var oc_cal_dayofweek = oc_cal_date.getDay();
var oc_cal_month = oc_cal_date.getMonth();
var oc_cal_dayofmonth = oc_cal_date.getDate();
var oc_cal_year = oc_cal_date.getFullYear();
var oc_cal_space = " ";
var oc_cal_normal_cal = new Array("31","28", "31", "30", "31", "30", "31", "31", "30", "31", "30", "31");
var oc_cal_leap_cal = new Array("31","29", "31", "30", "31", "30", "31", "31", "30", "31", "30", "31");
//init today date vars
var oc_cal_today = new Date();
var oc_cal_todaydayofweek = oc_cal_today.getDay();
var oc_cal_todaymonth = oc_cal_today.getMonth();
var oc_cal_todaydayofmonth = oc_cal_today.getDate();
var oc_cal_todayyear = oc_cal_today.getFullYear();
//other vars
var oc_cal_rows;
var oc_cal_dates;
var oc_cal_listview_numofevents = 0;
var oc_cal_listview_count = 0;
var oc_cal_opendialog = 0;
var oc_cal_datemonthyear =  String(oc_cal_dayofmonth) + String(oc_cal_month) + String(oc_cal_year);
var oc_cal_calendars = new Array();
//event vars
var oc_cal_events = new Array(2011);
oc_cal_events[2011] = new Array(0,1,2,3,4,5,6,7,8,9,10,11);
oc_cal_events[2011][7] = new Array();
oc_cal_events[2011][7][7] = new Array();
oc_cal_events[2011][7][13] = new Array();