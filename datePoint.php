<?php
/*
 * Manages all logic for the datepoint class,
 */
include_once("frequency.php");
include_once("utilities.php");

class DatePoint {
    //attributes
    protected $datePoint;
    protected $today;
    protected $interval;
    protected $itemID;
    protected $amount;
    protected $amountType;
    protected $firstDate;
    protected $currentStamp;
    protected $db;
    protected $myFreq;

    //constructor
	/*
	requires:
	$db 			- 	a db object
	$thisID 		- 	an arbitrary id number. for tracking back to the expense or income id used to create this date point
	$amountType 	- 	either 'expense' or 'income', just to allow tracking in other modules
	$thisAmount 	- 	the whole cent amount, 10 dollars = 1000
	$thisDatePoint 	- 	the mysql friendly date associated with this datepoint, ie 2015-04-20
	$thisInterval 	- 	the frequency module numerical representation of a time interval, ie 2 = week, 3 = fortnight, 4 = month
	*/
    public function __construct(&$db,$thisID,$amountType,$thisAmount,$thisDatePoint,$thisInterval) {
        $this->itemID=$thisID;
        $this->amount=$thisAmount;
        $this->datePoint=new DateTime($thisDatePoint);
        $this->interval=$thisInterval;
        $this->amountType=$amountType;
        $this->db=$db;
        
		$this->myFreq=new Frequency($this->db);
		$freqLookup=$this->myFreq->getFrequencyListIDValue();
		$freqLookup2=$this->myFreq->getFrequencyListIDPeriod();
		
        //calculate the first date point, ignoring the time component
        $todayWithTime=new DateTime("now");
        $this->today=new DateTime($todayWithTime->format('Y-m-d'));
        
        //calculate the first interval bound date, that occurs either today or into the future
        $simpleDiff=safeCompareDates($this->today,$this->datePoint);
        if ($simpleDiff==0) {
        	$this->firstDate=clone $this->today;
        	$this->currentStamp=clone $this->today;
        }
        elseif ($simpleDiff==-1) {
        	//the datepoint is in the future
        	$difference=$this->today->diff($this->datePoint);
        	
			//iterate back, until we have a valid start date
        	$this->currentStamp=clone $this->datePoint;
        	
        	//php doesnt fill in all the values for a new dateinterval, so we have to create it from a datetime diff
        	$d1=new DateTime();
        	$d2=new DateTime();
        	$d2->add(new DateInterval($freqLookup2[$this->interval]));
        	$tmpDateInterval=$d2->diff($d1);
        	//print_r($tmpDateInterval);
        	//print "<h3>lookup string: ".$freqLookup2[$this->interval]."</h3>";
        	$testCounter=0;
        	//print "<p>".$difference->days."###".$tmpDateInterval->days."</p>";
        	while (($difference->days>=$tmpDateInterval->days) && $testCounter<20) {
        		$test=new DateInterval($freqLookup2[$this->interval]);
        		//print_r($test);
        		
        		$this->currentStamp->sub(new DateInterval($freqLookup2[$this->interval]));
        		$difference=$this->today->diff($this->currentStamp);
        		$testCounter+=1;
        		//print "<p>".$difference->days."###".$tmpDateInterval->days."</p>";
        		
        	}
			$this->firstDate=clone $this->currentStamp;
			
		}
        else {
        	//the datepoint is in the past
        	$difference=$this->today->diff($this->datePoint);
        	
			//iterate forward, until we have a valid start date
        	$this->currentStamp=clone $this->datePoint;
        	
        	$tmpDifferenceWithSign=($difference->invert)?$tmpDifferenceWithSign=-$difference->days:$difference->days;
        	while ($tmpDifferenceWithSign<0) {
        		$this->currentStamp->add(new DateInterval($freqLookup2[$this->interval]));
        		$difference=$this->today->diff($this->currentStamp);
        		$tmpDifferenceWithSign=($difference->invert)?$tmpDifferenceWithSign=-$difference->days:$difference->days;
        	
        	}
			$this->firstDate=clone $this->currentStamp;
        }
    }
    
    public function isExpense() {
    	return $this->amountType=='expense';
    }
    
    public function isIncome() {
    	return $this->amountType=='income';
    }
    
    public function getAmount() {
    	return $this->amount;
    }
    
    public function getAmountType() {
    	return $this->amountType;	
	}
	
	public function getItemID() {
		return $this->itemID;
	}
    
	public function getFirstDate() {
		return $this->firstDate;
	}
	
    //increments the current stamp forward by 1 interval after returning the value, after going past 2 years worth, the current counter resets
    public function getNext() {
    	$result=clone $this->currentStamp;
    	$freqLookup2=$this->myFreq->getFrequencyListIDPeriod();
		$this->currentStamp->add(new DateInterval($freqLookup2[$this->interval]));
		$difference=$this->currentStamp->diff($this->today);
		$tmpTwoYearDate= clone $this->today;
		$tmpTwoYearDate->add(new DateInterval('P2Y'));
		$tmpTwoYearDate->add(new DateInterval($freqLookup2[$this->interval]));
		$thisItemsMaxInterval=$this->today->diff($tmpTwoYearDate);
		if ($difference->days>$thisItemsMaxInterval->days) {
			$this->reset();
			return False;
		}
		else {
			return $result;
		}
    }
    
    //just resets the current time stamp to the first valid one we found during initialisation
    public function reset() {
		//print "DATEPOINT ITERATOR RESET<br/>";
    	$this->currentStamp = clone $this->firstDate;
    }
}
    
?>