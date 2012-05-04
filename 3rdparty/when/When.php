<?php
/**
 * Name: When
 * Author: Thomas Planer <tplaner@gmail.com>
 * Location: http://github.com/tplaner/When
 * Created: September 2010
 * Description: Determines the next date of recursion given an iCalendar "rrule" like pattern.
 * Requirements: PHP 5.3+ - makes extensive use of the Date and Time library (http://us2.php.net/manual/en/book.datetime.php)
 */
class When
{
	protected $frequency;
	
	protected $start_date;
	protected $try_date;
	
	protected $end_date;
	
	protected $gobymonth;
	protected $bymonth;
	
	protected $gobyweekno;
	protected $byweekno;
	
	protected $gobyyearday;
	protected $byyearday;
	
	protected $gobymonthday;
	protected $bymonthday;
	
	protected $gobyday;
	protected $byday;
	
	protected $gobysetpos;
	protected $bysetpos;
		
	protected $suggestions;
	
	protected $count;
	protected $counter;
	
	protected $goenddate;
	
	protected $interval;
	
	protected $wkst;
	
	protected $valid_week_days;
	protected $valid_frequency;
		
	/**
	 * __construct
	 */
	public function __construct()
	{
		$this->frequency = null;
		
		$this->gobymonth = false;
		$this->bymonth = range(1,12);
		
		$this->gobymonthday = false;
		$this->bymonthday = range(1,31);
		
		$this->gobyday = false;
		// setup the valid week days (0 = sunday)
		$this->byday = range(0,6);
		
		$this->gobyyearday = false;
		$this->byyearday = range(0,366);
		
		$this->gobysetpos = false;
		$this->bysetpos = range(1,366);
		
		$this->gobyweekno = false;
		// setup the range for valid weeks
		$this->byweekno = range(0,54);
		
		$this->suggestions = array();
		
		// this will be set if a count() is specified
		$this->count = 0;
		// how many *valid* results we returned
		$this->counter = 0;
		
		// max date we'll return
		$this->end_date = new DateTime('9999-12-31');
		
		// the interval to increase the pattern by
		$this->interval = 1;
		
		// what day does the week start on? (0 = sunday)
		$this->wkst = 0;
		
		$this->valid_week_days = array('SU', 'MO', 'TU', 'WE', 'TH', 'FR', 'SA');
		
		$this->valid_frequency = array('SECONDLY', 'MINUTELY', 'HOURLY', 'DAILY', 'WEEKLY', 'MONTHLY', 'YEARLY');
	}
	
	/**
	 * @param DateTime|string $start_date of the recursion - also is the first return value.
	 * @param string $frequency of the recrusion, valid frequencies: secondly, minutely, hourly, daily, weekly, monthly, yearly
	 */
	public function recur($start_date, $frequency = "daily")
	{
		try
		{
			if(is_object($start_date))
			{
				$this->start_date = clone $start_date;
			}
			else
			{
				// timestamps within the RFC have a 'Z' at the end of them, remove this.
				$start_date = trim($start_date, 'Z');
				$this->start_date = new DateTime($start_date);
			}
			
			$this->try_date = clone $this->start_date;
		}
		catch(Exception $e)
		{
			throw new InvalidArgumentException('Invalid start date DateTime: ' . $e);
		}
		
		$this->freq($frequency);
		
		return $this;
	}

	public function freq($frequency)
	{
		if(in_array(strtoupper($frequency), $this->valid_frequency))
		{
			$this->frequency = strtoupper($frequency);
		}
		else
		{
			throw new InvalidArgumentException('Invalid frequency type.');
		}

		return $this;
	}

	// accepts an rrule directly
	public function rrule($rrule)
	{
		// strip off a trailing semi-colon
		$rrule = trim($rrule, ";");
		
		$parts = explode(";", $rrule);

		foreach($parts as $part)
		{
			list($rule, $param) = explode("=", $part);

			$rule = strtoupper($rule);
			$param = strtoupper($param);

			switch($rule)
			{
				case "FREQ":
					$this->frequency = $param;
					break;
				case "UNTIL":
					$this->until($param);
					break;
				case "COUNT":
					$this->count($param);
					break;
				case "INTERVAL":
					$this->interval($param);
					break;
				case "BYDAY":
					$params = explode(",", $param);
					$this->byday($params);
					break;
				case "BYMONTHDAY":
					$params = explode(",", $param);
					$this->bymonthday($params);
					break;
				case "BYYEARDAY":
					$params = explode(",", $param);
					$this->byyearday($params);
					break;
				case "BYWEEKNO":
					$params = explode(",", $param);
					$this->byweekno($params);
					break;
				case "BYMONTH":
					$params = explode(",", $param);
					$this->bymonth($params);
					break;
				case "BYSETPOS":
					$params = explode(",", $param);
					$this->bysetpos($params);
					break;
				case "WKST":
					$this->wkst($param);
					break;
			}
		}

		return $this;
	}
	
	//max number of items to return based on the pattern
	public function count($count)
	{
		$this->count = (int)$count;
		
		return $this;
	}
	
	// how often the recurrence rule repeats
	public function interval($interval)
	{
		$this->interval = (int)$interval;
		
		return $this;
	}
	
	// starting day of the week
	public function wkst($day)
	{
		switch($day)
		{
			case 'SU':
				$this->wkst = 0;
				break;
			case 'MO':
				$this->wkst = 1;
				break;
			case 'TU':
				$this->wkst = 2;
				break;
			case 'WE':
				$this->wkst = 3;
				break;
			case 'TH':
				$this->wkst = 4;
				break;
			case 'FR':
				$this->wkst = 5;
				break;
			case 'SA':
				$this->wkst = 6;
				break;
		}
		
		return $this;
	}
	
	// max date
	public function until($end_date)
	{		
		try
		{
			if(is_object($end_date))
			{
				$this->end_date = clone $end_date;
			}
			else
			{
				// timestamps within the RFC have a 'Z' at the end of them, remove this.
				$end_date = trim($end_date, 'Z');
				$this->end_date = new DateTime($end_date);
			}
		}
		catch(Exception $e)
		{
			throw new InvalidArgumentException('Invalid end date DateTime: ' . $e);
		}
		
		return $this;
	}

	public function bymonth($months)
	{	
		if(is_array($months))
		{
			$this->gobymonth = true;
			$this->bymonth = $months;
		}
		
		return $this;
	}
	
	public function bymonthday($days)
	{	
		if(is_array($days))
		{
			$this->gobymonthday = true;
			$this->bymonthday = $days;
		}
		
		return $this;
	}
	
	public function byweekno($weeks)
	{
		$this->gobyweekno = true;
		
		if(is_array($weeks))
		{
			$this->byweekno = $weeks;
		}
		
		return $this;
	}
	
	public function bysetpos($days)
	{
		$this->gobysetpos = true;
		
		if(is_array($days))
		{
			$this->bysetpos = $days;
		}
		
		return $this;
	}
	
	public function byday($days)
	{		
		$this->gobyday = true;
		
		if(is_array($days))
		{
			$this->byday = array();
			foreach($days as $day)
			{
				$len = strlen($day);
				
				$as = '+';
				
				// 0 mean no occurence is set
				$occ = 0;
				
				if($len == 3)
				{
					$occ = substr($day, 0, 1);
				}
				if($len == 4)
				{
					$as = substr($day, 0, 1);
					$occ = substr($day, 1, 1);
				}
				
				if($as == '-')
				{
					$occ = '-' . $occ;
				}
				else
				{
					$occ = '+' . $occ;
				}
				
				$day = substr($day, -2, 2);
				switch($day)
				{
					case 'SU':
						$this->byday[] = $occ . 'SU';
						break;
					case 'MO':
						$this->byday[] = $occ . 'MO';
						break;
					case 'TU':
						$this->byday[] = $occ . 'TU';
						break;
					case 'WE':
						$this->byday[] = $occ . 'WE';
						break;
					case 'TH':
						$this->byday[] = $occ . 'TH';
						break;
					case 'FR':
						$this->byday[] = $occ . 'FR';
						break;
					case 'SA':
						$this->byday[] = $occ . 'SA';
						break;
				}
			}
		}
		
		return $this;
	}
	
	public function byyearday($days)
	{
		$this->gobyyearday = true;
		
		if(is_array($days))
		{
			$this->byyearday = $days;
		}
		
		return $this;
	}
	
	// this creates a basic list of dates to "try"
	protected function create_suggestions()
	{
		switch($this->frequency)
		{
			case "YEARLY":
				$interval = 'year';
				break;
			case "MONTHLY":
				$interval = 'month';
				break;
			case "WEEKLY":
				$interval = 'week';
				break;
			case "DAILY":
				$interval = 'day';
				break;
			case "HOURLY":
				$interval = 'hour';
				break;
			case "MINUTELY":
				$interval = 'minute';
				break;
			case "SECONDLY":
				$interval = 'second';
				break;
		}
					
		$month_day = $this->try_date->format('j');
		$month = $this->try_date->format('n');
		$year = $this->try_date->format('Y');
		
		$timestamp = $this->try_date->format('H:i:s');
					
		if($this->gobysetpos)
		{				
			if($this->try_date == $this->start_date)
			{
				$this->suggestions[] = clone $this->try_date;
			}
			else
			{
				if($this->gobyday)
				{
					foreach($this->bysetpos as $_pos)
					{
						$tmp_array = array();
						$_mdays = range(1, date('t',mktime(0,0,0,$month,1,$year)));
						foreach($_mdays as $_mday)
						{
							$date_time = new DateTime($year . '-' . $month . '-' . $_mday . ' ' . $timestamp);
							
							$occur = ceil($_mday / 7);
							
							$day_of_week = $date_time->format('l');
							$dow_abr = strtoupper(substr($day_of_week, 0, 2));
							
							// set the day of the month + (positive)
							$occur = '+' . $occur . $dow_abr;
							$occur_zero = '+0' . $dow_abr;
							
							// set the day of the month - (negative)
							$total_days = $date_time->format('t') - $date_time->format('j');
							$occur_neg = '-' . ceil(($total_days + 1)/7) . $dow_abr;
							
							$day_from_end_of_month = $date_time->format('t') + 1 - $_mday;
							
							if(in_array($occur, $this->byday) || in_array($occur_zero, $this->byday) || in_array($occur_neg, $this->byday))
							{								
								$tmp_array[] = clone $date_time;
							}
						}
						
						if($_pos > 0)
						{
							$this->suggestions[] = clone $tmp_array[$_pos - 1];
						}
						else
						{
							$this->suggestions[] = clone $tmp_array[count($tmp_array) + $_pos];
						}
						
					}
				}
			}
		}
		elseif($this->gobyyearday)
		{
			foreach($this->byyearday as $_day)
			{
				if($_day >= 0)
				{
					$_day--;
					
					$_time = strtotime('+' . $_day . ' days', mktime(0, 0, 0, 1, 1, $year));
					$this->suggestions[] = new Datetime(date('Y-m-d', $_time) . ' ' . $timestamp);
				}
				else
				{
					$year_day_neg = 365 + $_day;
					$leap_year = $this->try_date->format('L');
					if($leap_year == 1)
					{
						$year_day_neg = 366 + $_day;
					}
					
					$_time = strtotime('+' . $year_day_neg . ' days', mktime(0, 0, 0, 1, 1, $year));
					$this->suggestions[] = new Datetime(date('Y-m-d', $_time) . ' ' . $timestamp);
				}					
			}
		}
		// special case because for years you need to loop through the months too
		elseif($this->gobyday && $interval == "year")
		{
			foreach($this->bymonth as $_month)
			{
				// this creates an array of days of the month
				$_mdays = range(1, date('t',mktime(0,0,0,$_month,1,$year)));
				foreach($_mdays as $_mday)
				{
					$date_time = new DateTime($year . '-' . $_month . '-' . $_mday . ' ' . $timestamp);
					
					// get the week of the month (1, 2, 3, 4, 5, etc)
					$week = $date_time->format('W');
					
					if($date_time >= $this->start_date && in_array($week, $this->byweekno))
					{
						$this->suggestions[] = clone $date_time;
					}
				}
			}
		}
		elseif($interval == "day")
		{
			$this->suggestions[] = clone $this->try_date;
		}
		elseif($interval == "week")
		{
			$this->suggestions[] = clone $this->try_date;
			
			if($this->gobyday)
			{
				$week_day = $this->try_date->format('w');
				
				$days_in_month = $this->try_date->format('t');
				
				$overflow_count = 1;
				$_day = $month_day;
				
				$run = true;
				while($run)
				{
					$_day++;
					if($_day <= $days_in_month)
					{
						$tmp_date = new DateTime($year . '-' . $month . '-' . $_day . ' ' . $timestamp);
					}
					else
					{
						//$tmp_month = $month+1;
						$tmp_date = new DateTime($year . '-' . $month . '-' . $overflow_count . ' ' . $timestamp);
						$tmp_date->modify('+1 month');
						$overflow_count++;
					}
					
					$week_day = $tmp_date->format('w');
					
					if($this->try_date == $this->start_date)
					{
						if($week_day == $this->wkst)
						{
							$this->try_date = clone $tmp_date;
							$this->try_date->modify('-7 days');
							$run = false;
						}
					}

					if($week_day != $this->wkst)
					{
						$this->suggestions[] = clone $tmp_date;
					}
					else
					{
						$run = false;
					}
				}
			}
		}
		elseif($this->gobyday && $interval == "month")
		{
			$_mdays = range(1, date('t',mktime(0,0,0,$month,1,$year)));
			foreach($_mdays as $_mday)
			{
				$date_time = new DateTime($year . '-' . $month . '-' . $_mday . ' ' . $timestamp);
				
				// get the week of the month (1, 2, 3, 4, 5, etc)
				$week = $date_time->format('W');
				
				if($date_time >= $this->start_date && in_array($week, $this->byweekno))
				{
					$this->suggestions[] = clone $date_time;
				}
			}
		}
		elseif($this->gobymonth)
		{
			foreach($this->bymonth as $_month)
			{
				$date_time = new DateTime($year . '-' . $_month . '-' . $month_day . ' ' . $timestamp);
				
				if($date_time >= $this->start_date)
				{
					$this->suggestions[] = clone $date_time;
				}
			}
		} 
		else 
		{
			$this->suggestions[] = clone $this->try_date;
		}
		
		if($interval == "month")
		{
			
			$this->try_date->modify('first day of next month');
			if((int) date('t', $this->try_date->format('U')) > (int) $this->start_date->format('j')){
				$this->try_date->modify('+' . (int) $this->start_date->format('j') - 1 . ' day'); 
			}else{
				$this->try_date->modify('+' . (int) date('t', $this->try_date->format('U')) - 1 . ' day'); 
			}
		}
		else
		{
			$this->try_date->modify($this->interval . ' ' . $interval);
		}
	}
	
	protected function valid_date($date)
	{
		$year = $date->format('Y');
		$month = $date->format('n');
		$day = $date->format('j');
		
		$year_day = $date->format('z') + 1;
		
		$year_day_neg = -366 + $year_day;
		$leap_year = $date->format('L');
		if($leap_year == 1)
		{
			$year_day_neg = -367 + $year_day;
		}
		
		// this is the nth occurence of the date
		$occur = ceil($day / 7);
		
		$week = $date->format('W');
		
		$day_of_week = $date->format('l');
		$dow_abr = strtoupper(substr($day_of_week, 0, 2));
		
		// set the day of the month + (positive)
		$occur = '+' . $occur . $dow_abr;
		$occur_zero = '+0' . $dow_abr;
		
		// set the day of the month - (negative)
		$total_days = $date->format('t') - $date->format('j');
		$occur_neg = '-' . ceil(($total_days + 1)/7) . $dow_abr;
		
		$day_from_end_of_month = $date->format('t') + 1 - $day;
		
		if(in_array($month, $this->bymonth) && 
		   (in_array($occur, $this->byday) || in_array($occur_zero, $this->byday) || in_array($occur_neg, $this->byday)) && 
		   in_array($week, $this->byweekno) &&
		   (in_array($day, $this->bymonthday) || in_array(-$day_from_end_of_month, $this->bymonthday)) &&
		   (in_array($year_day, $this->byyearday) || in_array($year_day_neg, $this->byyearday)))
		{
			return true;
		}
		else
		{
			return false;
		}
	}

	// return the next valid DateTime object which matches the pattern and follows the rules
	public function next()
	{		
		// check the counter is set
		if($this->count !== 0)
		{
			if($this->counter >= $this->count)
			{
				return false;
			}
		}
		
		// create initial set of suggested dates
		if(count($this->suggestions) === 0)
		{
			$this->create_suggestions();
		}
		
		// loop through the suggested dates
		while(count($this->suggestions) > 0)
		{
			// get the first one on the array
			$try_date = array_shift($this->suggestions);
			
			// make sure the date doesn't exceed the max date
			if($try_date > $this->end_date)
			{
				return false;
			}
			
			// make sure it falls within the allowed days
			if($this->valid_date($try_date) === true)
			{
				$this->counter++;
				return $try_date;
			}
			else
			{
				// we might be out of suggested days, so load some more
				if(count($this->suggestions) === 0)
				{
					$this->create_suggestions();
				}
			}
		}
	}
}
