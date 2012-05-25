<?php
$data = $_POST['data'];
$data = explode(',', $data);
$data = end($data);
$data = base64_decode($data);
OCP\JSON::checkLoggedIn();
OCP\App::checkAppEnabled('calendar');
$nl="\r\n";
$comps = array('VEVENT'=>true, 'VTODO'=>true, 'VJOURNAL'=>true);
$data = str_replace(array("\r","\n\n"), array("\n","\n"), $data);
$lines = explode("\n", $data);
unset($data);
$comp=$uid=$cal=false;
$cals=$uids=array();
$i = 0;
foreach($lines as $line) {
	if(strpos($line, ':')!==false) {
		list($attr, $val) = explode(':', strtoupper($line));
		if ($attr == 'BEGIN' && $val == 'VCALENDAR') {
			$cal = $i;
			$cals[$cal] = array('first'=>$i,'last'=>$i,'end'=>$i);
		} elseif ($attr =='BEGIN' && $cal!==false && isset($comps[$val])) {
			$comp = $val;
			$beginNo = $i;
		} elseif ($attr == 'END' && $cal!==false && $val == 'VCALENDAR') {
			if($comp!==false) {
				unset($cals[$cal]); // corrupt calendar, unset it
			} else {
				$cals[$cal]['end'] = $i;
			}
			$comp=$uid=$cal=false; // reset calendar
		} elseif ($attr == 'END' && $comp!==false && $val == $comp) {
			if(! $uid) {
				$uid = OC_Calendar_Object::createUID();
			}
			$uids[$uid][$beginNo] = array('end'=>$i, 'cal'=>$cal);
			if ($cals[$cal]['first'] == $cal) {
				$cals[$cal]['first'] = $beginNo;
			}
			$cals[$cal]['last'] = $i;
			$comp=$uid=false; // reset component
		} elseif ($attr =="UID" && $comp!==false) {
			list($attr, $uid) = explode(':', $line);
		}
	}
	$i++;
}
$calendars = OC_Calendar_Calendar::allCalendars(OCP\USER::getUser(), 1);
$id = $calendars[0]['id'];
foreach($uids as $uid) {
	$prefix=$suffix=$content=array();
	foreach($uid as $begin=>$details) {
		$cal = $details['cal'];
		if(!isset($cals[$cal])) {
			continue; // from corrupt/incomplete calendar
		}
		$cdata = $cals[$cal];
		// if we have multiple components from different calendar objects,
		// we should really merge their elements (enhancement?) -- 1st one wins for now.
		if(! count($prefix)) {
			$prefix = array_slice($lines, $cal, $cdata['first'] - $cal);
		}
		if(! count($suffix)) {
			$suffix = array_slice($lines, $cdata['last']+1, $cdata['end'] - $cdata['last']);
		}
		$content = array_merge($content, array_slice($lines, $begin, $details['end'] - $begin + 1));
	}
	if(count($content)) {
		$import = join($nl, array_merge($prefix, $content, $suffix)) . $nl;
		OC_Calendar_Object::add($id, $import);
	}
}
OCP\JSON::success();
?>