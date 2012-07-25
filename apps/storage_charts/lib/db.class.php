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
 * This class manages storage_charts. 
 */
class OC_DLStCharts {
	
	/**
	 * UPDATE day use for a user
	 * @param $used user used space
	 * @param $total total users used space
	 */
	public static function update($used, $total){
		$query = OCP\DB::prepare("SELECT stc_id FROM *PREFIX*dlstcharts WHERE oc_uid = ? AND stc_dayts = ?");
		$result = $query->execute(Array(OCP\User::getUser(), mktime(0,0,0)))->fetchRow();
		if($result){
			$query = OCP\DB::prepare("UPDATE *PREFIX*dlstcharts SET stc_used = ?, stc_total = ? WHERE stc_id = ?");
			$query->execute(Array($used, $total, $result['stc_id']));
		}else{
			$query = OCP\DB::prepare("INSERT INTO *PREFIX*dlstcharts (oc_uid,stc_month,stc_dayts,stc_used,stc_total) VALUES (?,?,?,?,?)");
			$query->execute(Array(OCP\User::getUser(), date('Ym'), mktime(0,0,0), $used, $total));
		}
	}
	
	/**
	 * Get the size of the data folder
	 * @param $path path to the folder you want to calculate the total size
	 */
	public static function getTotalDataSize($path){
		if(is_file($path)){
			$path = dirname($path);
		}
		$path = str_replace('//', '/', $path);
		if(is_dir($path) and strcmp(substr($path, -1), '/') != 0){
			$path .= '/';
		}
		$size = 0;
		if($dh = opendir($path)){
			while(($filename = readdir($dh)) !== false) {
				if(strcmp($filename, '.') != 0 and strcmp($filename, '..') != 0){
					$subFile = $path . '/' . $filename;
					if(is_file($subFile)){
						$size += filesize($subFile);
					}else{
						$size += self::getTotalDataSize($subFile);
					}
				}
			}
		}
		return $size;
	}
	
	/**
	 * Get data to build the pie about the Free-Used space ratio
	 */
	public static function getPieFreeUsedSpaceRatio(){
		if(OC_Group::inGroup(OCP\User::getUser(), 'admin')){
			$query = OCP\DB::prepare("SELECT stc_id, stc_dayts, oc_uid FROM (SELECT * FROM *PREFIX*dlstcharts ORDER BY stc_dayts DESC) last GROUP BY oc_uid");
			$results = $query->execute()->fetchAll();
		}else{
			$query = OCP\DB::prepare("SELECT stc_id, MAX(stc_dayts) as stc_dayts FROM *PREFIX*dlstcharts WHERE oc_uid = ?");
			$results = $query->execute(Array(OCP\User::getUser()))->fetchAll();
		}
		
		$return = Array();
		foreach($results as $result){
			$query = OCP\DB::prepare("SELECT oc_uid, stc_used, stc_total FROM *PREFIX*dlstcharts WHERE stc_id = ?");
			$return[] = $query->execute(Array($result['stc_id']))->fetchAll();
		}
		
		return $return;
	}
	
	/**
	 * Get data to build the line chart about last 7 days used space evolution
	 */
	public static function getUsedSpaceOverTime($time){
		$return = Array();
		if(OC_Group::inGroup(OCP\User::getUser(), 'admin')){
			foreach(OCP\User::getUsers() as $user){
				if(strcmp($time, 'daily') == 0){
					$return[$user] = self::getDataByUserToLineChart($user);
				}else{
					$return[$user] = self::getDataByUserToHistoChart($user);
				}
			}
		}else{
			if(strcmp($time, 'daily') == 0){
				$return[OCP\User::getUser()] = self::getDataByUserToLineChart(OCP\User::getUser());
			}else{
				$return[OCP\User::getUser()] = self::getDataByUserToHistoChart(OCP\User::getUser());
			}
		}
		return $return;
	}
	
	/**
	 * Get configuration values stored in the database
	 * @param $key The conf key
	 * @return Array The conf value
	 */
	public static function getUConfValue($key, $default = NULL){
		$query = OCP\DB::prepare("SELECT uc_id,uc_val FROM *PREFIX*dlstcharts_uconf WHERE oc_uid = ? AND uc_key = ?");
		$result = $query->execute(Array(OCP\User::getUser(), $key))->fetchRow();
		if($result){
			return $result;
		}
		return $default;
	}
	
	/**
	 * Set configuration values stored in the database
	 * @param $key The conf key
	 * @param $val The conf value
	 */
	public static function setUConfValue($key,$val){
		$conf = self::getUConfValue($key);
		if(!is_null($conf)){
			$query = OCP\DB::prepare("UPDATE *PREFIX*dlstcharts_uconf SET uc_val = ? WHERE uc_id = ?");
			$query->execute(Array($val, $conf['uc_id']));
		}else{
			$query = OCP\DB::prepare("INSERT INTO *PREFIX*dlstcharts_uconf (oc_uid,uc_key,uc_val) VALUES (?,?,?)");
			$query->execute(Array(OCP\User::getUser(), $key, $val));
		}
	}
	
	/**
	 * Parse an array and return data in the highCharts format
	 * @param $operation operation to do 
	 * @param $elements elements to parse
	 */
	public static function arrayParser($operation, $elements, $l, $data_sep = ',', $ck = 'hu_size'){
		$return = "";
		switch($operation){
			case 'pie':
				$free = $total = 0;
				foreach($elements as $element){
					$element = $element[0];
					
					$total = $element['stc_total'];
					$free += $element['stc_used'];
					
					$return .= "['" . $element['oc_uid'] . "', " . $element['stc_used'] . "],";
				}
				$return .= "['" . $l->t('Free space') . "', " . ($total - $free) . "]";
			break;
			case 'histo':
			case 'line':
				$conf = self::getUConfValue($ck, Array('uc_val' => 3));
				$div = 1;
				switch($conf['uc_val']){
					case 4:
						$div = 1024;
					case 3:
						$div *= 1024;
					case 2:
						$div *= 1024;
					case 1:
						$div *= 1024;
				}
				
				foreach($elements as $user => $data){
					$return_tmp = '{"name":"' . $user . '","data":[';
					foreach($data as $number){
						$return_tmp .= round($number/$div, 2) . ",";
					}
					$return_tmp = substr($return_tmp, 0, -1) . "]}";
					
					$return .= $return_tmp . $data_sep;
				}
				$return = substr($return, 0, -(strlen($data_sep)));
			break;
		}
		return $return;
	}
	
	/**
	 * Get data by user for Seven Days Line Chart
	 * @param $user the user
	 * @return Array
	 */
	private static function getDataByUserToLineChart($user){
		$dates = Array(
			mktime(0,0,0,date('m'),date('d')-6),
			mktime(0,0,0,date('m'),date('d')-5),
			mktime(0,0,0,date('m'),date('d')-4),
			mktime(0,0,0,date('m'),date('d')-3),
			mktime(0,0,0,date('m'),date('d')-2),
			mktime(0,0,0,date('m'),date('d')-1),
			mktime(0,0,0,date('m'),date('d'))
		);
		
		$return = Array();
		foreach($dates as $kd => $date){
			$query = OCP\DB::prepare("SELECT stc_used FROM *PREFIX*dlstcharts WHERE oc_uid = ? AND stc_dayts = ?");
			$result = $query->execute(Array($user, $date))->fetchAll();
			
			if(count($result) > 0){
				$return[] = $result[0]['stc_used'];
			}else{
				if($kd == 0){
					$query = OCP\DB::prepare("SELECT stc_used FROM *PREFIX*dlstcharts WHERE oc_uid = ? AND stc_dayts < ? ORDER BY stc_dayts DESC");
					$result = $query->execute(Array($user, $date))->fetchAll();
					
					if(count($result) > 0){
						$return[] = $result[0]['stc_used'];
					}else{
						$return[] = 0;
					}
				}else{
					$return[] = 0;
				}
			}
		}
		
		$last = 0;
		foreach ($return as $key => $value) {
			if($value == 0){
				$return[$key] = $last;
			}
			$last = $return[$key];
		}
		return $return;
	}

	/**
	 * Get data by users for monthly evolution
	 * @param $user The user
	 * @return Array
	 */
	private static function getDataByUserToHistoChart($user){
		$months = Array(
			date('Ym',mktime(0,0,0,date('m')-11)),
			date('Ym',mktime(0,0,0,date('m')-10)),
			date('Ym',mktime(0,0,0,date('m')-9)),
			date('Ym',mktime(0,0,0,date('m')-8)),
			date('Ym',mktime(0,0,0,date('m')-7)),
			date('Ym',mktime(0,0,0,date('m')-6)),
			date('Ym',mktime(0,0,0,date('m')-5)),
			date('Ym',mktime(0,0,0,date('m')-4)),
			date('Ym',mktime(0,0,0,date('m')-3)),
			date('Ym',mktime(0,0,0,date('m')-2)),
			date('Ym',mktime(0,0,0,date('m')-1)),
			date('Ym',mktime(0,0,0,date('m')))
		);
		
		$return = Array();
		foreach($months as $km => $month){
			$query = OCP\DB::prepare("SELECT AVG(stc_used) as stc_used FROM *PREFIX*dlstcharts WHERE oc_uid = ? AND stc_month = ?");
			$result = $query->execute(Array($user, $month))->fetchAll();
			
			if(count($result) > 0){
				$return[] = $result[0]['stc_used'];
			}else{
				$return[] = 0;
			}
		}
		
		$last = 0;
		foreach ($return as $key => $value) {
			if($value == 0){
				$return[$key] = $last;
			}
			$last = $return[$key];
		}
		return $return;
	}
	
}