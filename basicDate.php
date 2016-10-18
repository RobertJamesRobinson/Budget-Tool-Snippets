<?php
/*
/ basic date handling to avoid using the buggy php datetime functions
/ this class handles all date strings in mysql friendly format, conversions to human can be made, 
/ but for the purposes of this class, date strings are in the format Y-m-d
*/
class BasicDate {
	protected $internalDateString;
	
	//basic constructor, can take a date, should be in mysql format, but will automatically convert to mysql if a human style is passed in
	//if no date is passed in, defaults to todays date
	public function __construct($myDate=null) {
		if ($myDate) {
			$this->internalDateString=$this->parseDate($myDate);
		}
		else {
			$this->internalDateString=date("Y-m-d");
		}
	}
	
	//converts between human and mysql date string styles
	public function convert ($anyDateString=null) {
		if ($anyDateString) {
			$chunks=explode('-',$anyDateString);
		}
		else {
			$chunks=explode('-',$this->internalDateString);
		}
		return $chunks[2]."-".$chunks[1]."-".$chunks[0];
	}
	
	//returns the internal date in mysql format
	public function toMysql () {
		return $this->internalDateString;
	}
	
	//returns the internal date in human format
	public function toHuman() {
		return $this->convert($this->internalDateString);
	}
	
    //returns the internal date in human format, using slashes instead of dashes
	public function toHuman2() {
		$tmp=$this->convert($this->internalDateString);
        return str_replace('-','/',$tmp);
	}
	
	//checks if a passed date is in mysql format
	public function isMysql($myDate) {
		$check=explode('-',$myDate);
		if (strlen($check[0])!=4) {
			return false;
		}
		return true;
	}
	
	//returns just the year component of this date
	public function year() {
		$chunks=explode('-',$this->internalDateString);
		return $chunks[0];
	}
	
	//returns 0 if $a==$b, -1 if $a<$b and +1 if $a>$b
	public function cmp($a, $b) {
		//ensure the compared values are both in mysql format before test
		$ta=$this->parseDate($a);
		$tb=$this->parseDate($b);
		
		//seperate each date into y, m, d components
		$ca=explode('-',$ta);
		$cb=explode('-',$tb);
		
		//check in order of years, then months then days, if at any time, the compared values are different, then we have a clear difference...
		for ($i=0; $i<3; $i++) {
			if ($ca[$i]>$cb[$i]) {
				return 1;
			}
			elseif ($ca[$i]<$cb[$i]) {
				return -1;
			}
		}
		return 0;
	}
	
	//same as cmp, except $a == $internal date
	public function cmp2($b) {
		//ensure the compared value is in mysql format before test
		$ta=$this->internalDateString;
		$tb=$this->parseDate($b);
		
		//seperate each date into y, m, d components
		$ca=explode('-',$ta);
		$cb=explode('-',$tb);
		
		//check in order of years, then months then days, if at any time, the compared values are different, then we have a clear difference...
		for ($i=0; $i<3; $i++) {
			if ($ca[$i]>$cb[$i]) {
				return 1;
			}
			elseif ($ca[$i]<$cb[$i]) {
				return -1;
			}
		}
		return 0;
	}
	
	//adds a datetime like interval string to our internal date
	public function add($intervalString) {
		/*
		Valid intervals are PnY, PnM, PnD, PnW
		*/
		//strip off the P
		$myStr=substr($intervalString,1);
		
		//get the interval string
		$myInt=substr($myStr,-1,1);
		
		//get the interval
		$myAmt=substr($myStr,0,-1);
		
		$chunks=explode('-',$this->internalDateString);
		
		switch ($myInt) {
			case 'Y':
				$chunks[0]+=$myAmt;
				break;
			case 'M':
				$chunks[1]+=$myAmt;
				$chunks=$this->normaliseMonths($chunks);
				break;
			case 'D':
				$chunks[2]+=$myAmt;
				break;
			case 'W':
				$chunks[2]+=$myAmt*7;
				break;
		}
		$chunks=$this->normalise($chunks);
		$this->internalDateString=sprintf("%04d",$chunks[0]).'-'.sprintf("%02d", $chunks[1]).'-'.sprintf("%02d", $chunks[2]);
	}
	
	private function normaliseMonths($chunks) {
		
		//normalise for months first
		while ($chunks[1]<1) {
			$chunks[1]+=12;
			$chunks[0]-=1;
		}
		
		while ($chunks[1]>12) {
			$chunks[1]-=12;
			$chunks[0]+=1;
		}
		
		$lookup=$this->monthsDataStructure($this->yearIsLeap($chunks[0]));
		if ($chunks[2]>$lookup[sprintf('%02d',$chunks[1])])
		{
			$chunks[2]=$lookup[sprintf('%02d',$chunks[1])];
		}
		return $chunks;
	}
	
	//takes an array of date chunks in mysql format and ensures that any overflows of months or days are recalculated correctly, to make a valid date
	//returns the repaired array, in its original structure, just corrected.
	private function normalise($chunks) {
		
		//normalise days second
		
		//normalise for negative days
		$lookup=$this->monthsDataStructure($this->yearIsLeap($chunks[0]));
		while ($chunks[2]<1) {
			$chunks[1]-=1;
			
			//going past years end
			if ($chunks[1]<1) {
				$chunks[0]-=1;
				$chunks[1]=12;
				$lookup=$this->monthsDataStructure($this->yearIsLeap($chunks[0]));
			}
			$chunks[2]+=$lookup[sprintf('%02d',$chunks[1])];
		}
		
		//normalise for positive days
		$lookup=$this->monthsDataStructure($this->yearIsLeap($chunks[0]));
		while ($chunks[2]>$lookup[sprintf('%02d',$chunks[1])]) {
			
			$chunks[2]-=$lookup[sprintf('%02d',$chunks[1])];
			
			$chunks[1]+=1;
			
			//going past years end
			if ($chunks[1]>12) {
				$chunks[0]+=1;
				$chunks[1]=1;
				$lookup=$this->monthsDataStructure($this->yearIsLeap($chunks[0]));
			}
		}
		
		
		return $chunks;
	}
	
	//subtracts a datetime like interval string from our internal date
	public function sub($intervalString) {
		/*
		Valid intervals are PnY, PnM, PnD, PnW
		*/
		//strip off the P
		$myStr=substr($intervalString,1);
		
		//get the interval string
		$myInt=substr($myStr,-1,1);
		
		//get the interval
		$myAmt=substr($myStr,0,-1);
		
		$chunks=explode('-',$this->internalDateString);
		
		switch ($myInt) {
			case 'Y':
				$chunks[0]-=$myAmt;
				break;
			case 'M':
				$chunks[1]-=$myAmt;
				$chunks=$this->normaliseMonths($chunks);
				break;
			case 'D':
				$chunks[2]-=$myAmt;
				break;
			case 'W':
				$chunks[2]-=$myAmt*7;
				break;
		}
		$chunks=$this->normalise($chunks);
		$this->internalDateString=$chunks[0].'-'.$chunks[1].'-'.$chunks[2];
	}
	
	//returns true if the passed in date is a leap year
	public function isLeap($newDate) {
		$newDate=$this->parseDate($newDate);
		//send only the year component to our leap year checker
		$chunks=explode('-',$newDate);
		return $this->yearIsLeap($chunks[0]);
	}
	
	//returns true if the passed in 4 digit year is a leap year
	public function yearIsLeap($year=null) {
		if(!$year) {
			$year=$this->internalDateString;
		}
		if ($year%4==0) {
			if ($year%100==0) {
				if($year%400==0) {
					return true;
				}
			}
			else {
				return true;
			}
		}
		return false;
	}
	
	//returns a new date string from adding the interval string passed in to the internal date, DOES NOT ALTER THE INTERNAL DATE
	public function addReturnStr($intervalString) {
		/*
		Valid intervals are PnY, PnM, PnD, PnW
		*/
		//strip off the P
		$myStr=substr($intervalString,1);
		
		//get the interval string
		$myInt=substr($myStr,-1,1);
		
		//get the interval
		$myAmt=substr($myStr,0,-1);
		
		$chunks=explode('-',$this->internalDateString);
		
		switch ($myInt) {
			case 'Y':
				$chunks[0]+=$myAmt;
				break;
			case 'M':
				$chunks[1]+=$myAmt;
				$chunks=$this->normaliseMonths($chunks);
				break;
			case 'D':
				$chunks[2]+=$myAmt;
				break;
			case 'W':
				$chunks[2]+=$myAmt*7;
				break;
		}
		$chunks=$this->normalise($chunks);
		return $chunks[0].'-'.$chunks[1].'-'.$chunks[2];
	}
	
	//returns a new date string from subtracting the interval string passed in from the internal date, DOES NOT ALTER THE INTERNAL DATE
	public function subReturnStr($intervalString) {
		/*
		Valid intervals are PnY, PnM, PnD, PnW
		*/
		//strip off the P
		$myStr=substr($intervalString,1);
		
		//get the interval string
		$myInt=substr($myStr,-1,1);
		
		//get the interval
		$myAmt=substr($myStr,0,-1);
		
		$chunks=explode('-',$this->internalDateString);
		
		switch ($myInt) {
			case 'Y':
				$chunks[0]-=$myAmt;
				break;
			case 'M':
				$chunks[1]-=$myAmt;
				$chunks=$this->normaliseMonths($chunks);
				break;
			case 'D':
				$chunks[2]-=$myAmt;
				break;
			case 'W':
				$chunks[2]-=$myAmt*7;
				break;
		}
		$chunks=$this->normalise($chunks);
		return $chunks[0].'-'.$chunks[1].'-'.$chunks[2];
	}
	
	//takes either a BasicDate obj or a date string of either Mysql or Human format and converts it to mysql string for the majority of the functions here
	private function parseDate($obj) {
		$result=null;
		if (getType($obj)=="string") {
			$result=($this->isMysql($obj))?$obj:$this->convert($obj);
		}
		elseif (get_class($obj)=="BasicDate") {
			$result=$obj->toMysql();
		}
		return $result;
	}
	
	//calculates the difference in days between $internal and $newDate, assumes $internal is in the past, $newDate is in the future
	//therefore 0 = the same date, <0 = number of days $internal is greater than $newDate, >0 number of days $newDate is greater than $internal
	public function diff($newDate) {
		$newDate=$this->parseDate($newDate);
		$lookup=$this->monthsDataStructure($this->isLeap($newDate));
		$result=0;
		
		//case 1, same year, same month
		$ca=explode('-',$this->internalDateString);
		$cb=explode('-',$newDate);
		
		if ($ca[0]==$cb[0]) {
			//in the same year
			if ($ca[1]==$cb[1]) {
				//in the same month
				$result=$cb[2]-$ca[2];
			}
			else {
				//same year, different month
				if ($this->cmp($this->internalDateString,$newDate)==-1) {
					//where $ca is before $cb in time
					//add leading of b, remaining of a and any whole months in between
					$result+=$this->daysRemainingInMonth($this->internalDateString);
					$result+=$this->daysLeadingInMonth($newDate);
					for ($i=$ca[1]+1; $i<$cb[1]; $i++) {
						$result+=$lookup[sprintf('%02d',$i)];
					}
				}
				else {
					//must be where $ca is after $cb in time
					//subtract leading of a, remaining of b and any whole months in between
					$result-=$this->daysLeadingInMonth($this->internalDateString);
					$result-=$this->daysRemainingInMonth($newDate);
					for ($i=$cb[1]+1; $i<$ca[1]; $i++) {
						$result-=$lookup[sprintf('%02d',$i)];
					}
				}
			}
		}
		else {
			//1 or more years difference
			if ($this->cmp($this->internalDateString,$newDate)==-1) {
				//where $ca is before $cb in time
				//add leading of b and remaining of a and anything in between
				$result+=$this->daysRemainingInYear($this->internalDateString);
				$result+=$this->daysLeadingInYear($newDate);
				for ($i=$ca[0]+1; $i<$cb[0]; $i++) {
					$result+=$this->daysInYear($i);
				}
			}
			else {
				//must be where $ca is after $cb in time
				//subtract leading of a, remaining of b and any whole years in between
				$result-=$this->daysLeadingInYear($this->internalDateString);
				$result-=$this->daysRemainingInYear($newDate);
				for ($i=$cb[0]+1; $i<$ca[0]; $i++) {
					$result-=$this->daysInYear($i);
				}
			}
		}
		return $result;
		
	}
	
	//returns the number of days in the year passed in the given date
	private function daysInYear($year) {
		return ($this->yearIsLeap($year))?366:365;
	}
	
	//returns the number of days remaining from the given date to the end of that year (inclusive, so 31-12-2015 would return 1 day (ie, today))
	private function daysRemainingInYear($newDate) {
		$result=0;
		$newDate=$this->parseDate($newDate);
		$lookup=$this->monthsDataStructure($this->isLeap($newDate));
		$chunks=explode('-',$newDate);
		
		//add the remainder of the current month
		$result+=$this->daysRemainingInMonth($newDate);
		
		//add the remaining whole month, day amounts
		for ($i=$chunks[1]+1; $i<13; $i++) {
			$result+=$lookup[sprintf('%02d',$i)];
		}
		
		return $result;
	}
	
	//returns the number of days leading up to the date given from the start of that year
	private function daysLeadingInYear($newDate) {
		$result=0;
		$newDate=$this->parseDate($newDate);
		$lookup=$this->monthsDataStructure($this->isLeap($newDate));
		$chunks=explode('-',$newDate);
		
		//add the leading for this month
		$result+=$this->daysLeadingInMonth($newDate);
		
		//add the whole month amounts
		for ($i=1; $i<$chunks[1]; $i++) {
			$result+=$lookup[sprintf('%02d', $i)];
		}
		return $result;
	}
	
	//returns the remaining days in the month represented by a passed in date (inclusive)
	private function daysRemainingInMonth($newDate) {
		// make sure the date is in mysql format
		$newDate=$this->parseDate($newDate);
		$lookup=$this->monthsDataStructure($this->isLeap($newDate));
		$chunks=explode('-',$newDate);
		return $lookup[$chunks[1]]-$chunks[2]+1;
	}
	
	//returns the number of days leading up to the date in the month represented in the date passed in (not inclusive)
	private function daysLeadingInMonth($newDate) {
		// make sure the date is in mysql format
		$newDate=$this->parseDate($newDate);
		$chunks=explode('-',$newDate);
		return $chunks[2]-1;
	}
	
	//returns a data structure useful for calculating the number of days in each month, takes an optional "is leap" boolean, if true, it returns the leap year equivalent
	private function monthsDataStructure($isLeap=null) {
		$result=array();
		if ($isLeap) {
			$result=array('01'=>31, '1'=>31, '02'=>29, '2'=>29, '03'=>31, '3'=>31, '04'=>30, '4'=>30, '05'=>31, '5'=>31, '06'=>30, '6'=>30, '07'=>31, '7'=>31, '08'=>31, '8'=>31, '09'=>30, '9'=>30, '10'=>31, '11'=>30, '12'=>31);
		}
		else {
			$result=array('01'=>31, '1'=>31, '02'=>28, '2'=>28, '03'=>31, '3'=>31, '04'=>30, '4'=>30, '05'=>31, '5'=>31, '06'=>30, '6'=>30, '07'=>31, '7'=>31, '08'=>31, '8'=>31, '09'=>30, '9'=>30, '10'=>31, '11'=>30, '12'=>31);
		}
		return $result;
	}
    
    //simple wrapper around the unix gettimestamp function
    public function getTimeStamp() {
        return time();
    }
    
    public function Log($message) {
        $now=date("Y-m-d H:i:s");
        #$fh=fopen('log.txt','a');
        #fwrite($fh,$now." - ".$message);
        #fclose($fh);
    }
    
    //takes a datepoint value and applies the given frequency interval (like P1D)in either direction until we have the next possible date that meets the frequency spec, and returns that date
    public function setNextValidDateFromFrequencyAndDatePoint($frequency_string,$datePoint) {
        $this->Log('before parse: '.$this->internalDateString."\n");
        $this->internalDateString=$this->parseDate($datePoint);
        $this->Log('after parse: '.$this->internalDateString."\n");
        
        $nowDate=date("Y-m-d");
        $diff=$this->diff($nowDate);
        #assume the date point is in the future, push it back until its before today, then roll it forward
        while($this->diff($nowDate)<0) {
            $this->Log('before sub: '.$this->diff($nowDate)."(internalDate: ".$this->internalDateString.") - (now date: $nowDate)\n");
            $this->sub($frequency_string);
            $this->Log('after sub: '. $this->diff($nowDate)."(internalDate: ".$this->internalDateString.") - (now date: $nowDate)\n");
        }
        
        #the internal date(datepoint) is still in the past, keep adding until its today or beyond
        while($this->diff($nowDate)>0) {
            $this->Log('before sub: '.$this->diff($nowDate)."(internalDate: ".$this->internalDateString.") - (now date: $nowDate)\n");
            $this->add($frequency_string);
            $this->Log('after sub: '.$this->diff($nowDate)."(internalDate: ".$this->internalDateString.") - (now date: $nowDate)\n");
        }
    }
    
    //returns True if the internal date matches todays date
    public function isToday() {
        return $this->internalDateString==date("Y-m-d");
    }
}

?>