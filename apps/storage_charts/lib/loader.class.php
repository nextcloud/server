<?php

/**
* ownCloud - DjazzLab Storage Charts plugin
*
* @author Xavier Beurois
* @copyright 2012 Xavier Beurois www.djazz-lab.net
* 
* This library is free software; you can redistribute it and/or
* modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
* License as published by the Free Software Foundation; either 
* version 3 of the License, or any later version.
* 
* This library is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU AFFERO GENERAL PUBLIC LICENSE for more details.
*  
* You should have received a copy of the GNU Lesser General Public 
* License along with this library.  If not, see <http://www.gnu.org/licenses/>.
* 
*/

/**
 * This class load charts for storage_charts. 
 */
class OC_DLStChartsLoader {
	
	private static $l;
	
	/**
	 * Loader
	 * @param $chart_id The chart iD
	 * @return String
	 */
	public static function loadChart($chart_id, $l){
		self::$l = $l;
		
		switch($chart_id){
			case 'cpie_rfsus':
				return self::loadPieFreeUsedSpaceRatioChart();
				break;
			case 'clines_usse':
				return self::loadLinesLastSevenDaysUsedSpaceChart();
				break;
			case 'chisto_us':
				return self::loadHistoMonthlyUsedSpaceChart();
				break;
		}
	}
	
	/**
	 * Get free/used space ratio chart
	 * @return String
	 */
	private static function loadPieFreeUsedSpaceRatioChart(){
		return 'pierfsus=new Highcharts.Chart({chart:{renderTo:\'pie_rfsus\',backgroundColor:\'#F8F8F8\',plotBackgroundColor:\'#F8F8F8\',plotBorderWidth:false,plotShadow:false},title:{text:\'\'},tooltip:{formatter:function(){return \'<b>\'+this.point.name+\'</b>: \'+(Math.round(this.percentage*100)/100)+\' %\';}},plotOptions:{pie:{allowPointSelect:true,cursor:\'pointer\',dataLabels:{enabled:true,color:\'#000000\',connectorColor:\'#000000\',formatter:function(){return\'<b>\'+this.point.name+\'</b>: \'+Math.round(this.percentage)+\' %\';}}}},series:[{type:\'pie\',name:\'Used-Free space ratio\',data:[' . OC_DLStCharts::arrayParser('pie',OC_DLStCharts::getPieFreeUsedSpaceRatio(), self::$l) . ']}],exporting:{enabled:false}});';
	}
	
	/**
	 * Get seven days used space evolution chart
	 * @return String 
	 */
	private static function loadLinesLastSevenDaysUsedSpaceChart(){
		$units = Array('', 'KB', 'MB', 'GB', 'TB');
		$u = OC_DLStCharts::getUConfValue('hu_size', Array('uc_val' => 3));
		$u = $units[$u['uc_val']];
		return 'linesusse=new Highcharts.Chart({chart:{renderTo:\'lines_usse\',backgroundColor:\'#F8F8F8\',plotBackgroundColor:\'#F8F8F8\',type:\'line\'},title:{text:\'\'},subtitle:{text:\''.self::$l->t('Last 7 days').'\',x:-20},xAxis:{categories:["'.date('m/d', mktime(0,0,0,date('m'),date('d')-6)).'","'.date('m/d', mktime(0,0,0,date('m'),date('d')-5)).'","'.date('m/d', mktime(0,0,0,date('m'),date('d')-4)).'","'.date('m/d', mktime(0,0,0,date('m'),date('d')-3)).'","'.date('m/d', mktime(0,0,0,date('m'),date('d')-2)).'","'.date('m/d', mktime(0,0,0,date('m'),date('d')-1)).'","'.date('m/d', mktime(0,0,0,date('m'),date('d'))).'"]},yAxis:{title:{text:\''.self::$l->t('Used space').' ('.$u.')\'},plotLines:[{value:0,width:1,color:\'#808080\'}],startOnTick:false,min:0},tooltip:{crosshairs:true,formatter:function(){return \'<b>\'+this.series.name+\'</b><br/>\'+this.x+\': \'+this.y+\' '.$u.'\';}},legend:{layout:\'horizontal\',align:\'center\',verticalAlign:\'top\',x:-25,y:40,borderWidth:0},series:['.OC_DLStCharts::arrayParser('line', OC_DLStCharts::getUsedSpaceOverTime('daily'), self::$l).'],exporting:{enabled:false}});';
	}

	/**
	 * Get monthly used space evolution chart
	 * @return String
	 */
	private static function loadHistoMonthlyUsedSpaceChart(){
		$units = Array('', 'KB', 'MB', 'GB', 'TB');
		$u = OC_DLStCharts::getUConfValue('hu_size_hus', Array('uc_val' => 3));
		$u = $units[$u['uc_val']];
		
		$months = self::getMonths();
		
		return 'histous=new Highcharts.Chart({chart:{renderTo:\'histo_us\',backgroundColor:\'#F8F8F8\',plotBackgroundColor:\'#F8F8F8\',type:\'column\'},title:{text:\'\'},xAxis:{categories:["'.self::$l->t($months[0]).' '.date('Y',mktime(0,0,0,date('m')-11)).'","'.self::$l->t($months[1]).' '.date('Y',mktime(0,0,0,date('m')-10)).'","'.self::$l->t($months[2]).' '.date('Y',mktime(0,0,0,date('m')-9)).'","'.self::$l->t($months[3]).' '.date('Y',mktime(0,0,0,date('m')-8)).'","'.self::$l->t($months[4]).' '.date('Y',mktime(0,0,0,date('m')-7)).'","'.self::$l->t($months[5]).' '.date('Y',mktime(0,0,0,date('m')-6)).'","'.self::$l->t($months[6]).' '.date('Y',mktime(0,0,0,date('m')-5)).'","'.self::$l->t($months[7]).' '.date('Y',mktime(0,0,0,date('m')-4)).'","'.self::$l->t($months[8]).' '.date('Y',mktime(0,0,0,date('m')-3)).'","'.self::$l->t($months[9]).' '.date('Y',mktime(0,0,0,date('m')-2)).'","'.self::$l->t($months[10]).' '.date('Y',mktime(0,0,0,date('m')-1)).'","'.self::$l->t($months[11]).' '.date('Y',mktime(0,0,0,date('m'))).'"]},yAxis:{min:0,title:{text:\''.self::$l->t('Average used space').' ('.$u.')\'},stackLabels:{enabled:true,style:{fontWeight:\'bold\',color:(Highcharts.theme&&Highcharts.theme.textColor)||\'gray\'},formatter:function(){return(Math.round(this.total*100)/100);}}},legend:{align:\'center\',x:-20,verticalAlign:\'top\',y:20,floating:true,backgroundColor:(Highcharts.theme&&Highcharts.theme.legendBackgroundColorSolid)||\'white\',borderColor:\'#CCC\',borderWidth:1,shadow:false},tooltip:{formatter:function(){return \'<b>\'+this.x+\'</b><br/>\'+this.series.name+\': \'+(Math.round(this.y*100)/100)+\' '.$u.'<br/>\'+\'Total: \'+(Math.round(this.point.stackTotal*100)/100)+\' '.$u.'\';}},plotOptions:{column:{stacking:\'normal\',dataLabels:{enabled:false,color:(Highcharts.theme&&Highcharts.theme.dataLabelsColor)||\'white\'}}},series:['.OC_DLStCharts::arrayParser('histo',OC_DLStCharts::getUsedSpaceOverTime('monthly'),self::$l,',','hu_size_hus').'],exporting:{enabled:false}});';
	}

	/**
	 * Get months
	 */
	private static function getMonths(){
		$months = Array('January','February','March','April','May','June','July','August','September','October','November','December');
		
		$tmp = Array();
		for($i=date('n');$i<12;$i++){
			$tmp[] = $months[$i];
		}
		for($i=0;$i<date('n');$i++){
			$tmp[] = $months[$i];
		}
		
		return $tmp;
	}
	
}