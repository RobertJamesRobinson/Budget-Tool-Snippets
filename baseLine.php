<?php
/*
 * Manages all logic for the income class, handles income details, username, frequency and amount
 */

class BaseLine {
    //attributes
    protected $db;
    protected $baseLineList;
    protected $datePointList;
    protected $today;
    
    //constructor
    public function __construct(&$db) {
        $this->db=$db;
        $this->baseLineList=Array();
        $this->datePointList=Array();
        
        //calculate the today date point, ignoring the time component
        $todayWithTime=new DateTime("now");
        $this->today=new DateTime($todayWithTime->format('Y-m-d'));
        
    }

    //selects an income for further processing, by ID
    public function add($datePointObj) {
        $this->datePointList[]=$datePointObj;
    }
    
    //simply generates an array, in date order, for all incomes and expenses listed in the datePointList
    public function generateBaseLine() {
    	$testCounter=0;
    	foreach ($this->datePointList as $datePointObj) {
    		while ($singleDate=$datePointObj->getNext()) {
				$this->baseLineList[] = array('dateObj'=>$singleDate,'itemID'=>$datePointObj->getItemID(), 'amount'=>$datePointObj->getAmount(), 'amountType'=>$datePointObj->getAmountType()) ;
			}
		}
		usort($this->baseLineList, 'dateSorter');
	}
    
    public function getBalanceAtDate($requestedDate) {
    	$balance=0;
    	foreach ($this->baseLineList as $singleItem) {
    		//check if we've gone past our requested date
    		$differObj=$requestedDate->diff($singleItem['dateObj']);
    		if ($differObj->invert) {
    			if ($singleItem['amountType']=='expense') {
    				$balance-=$singleItem['amount'];
    			}
    			else {
    				$balance+=$singleItem['amount'];
    			}
    		}
    		else {
    			break;
    		}
    	}
    	return $balance;
    }
	
	public function getFloatNeededAtDate($requestedDate) {
		$lowest=0;
		$balance=0;
    	foreach ($this->baseLineList as $singleItem) {
    		//check if we've gone past our requested date
    		$differObj=$requestedDate->diff($singleItem['dateObj']);
    		if ($differObj->invert) {
    			if ($singleItem['amountType']=='expense') {
    				$balance-=$singleItem['amount'];
    			}
    			else {
    				$balance+=$singleItem['amount'];
    			}
    		}
    		else {
    			break;
    		}
    		if ($lowest>$balance) {
    			$lowest=$balance;
    		}
    	}
    	return -$lowest;
	}
	
	public function getFloatNeededToday() {
		$lowest=0;
		$balance=0;
    	foreach ($this->baseLineList as $singleItem) {
			if ($singleItem['amountType']=='expense') {
				$balance-=$singleItem['amount'];
			}
			else {
				$balance+=$singleItem['amount'];
			}
    		if ($lowest>=$balance) {
    			$lowest=$balance;
    		}
    	}
    	return -$lowest;
	}
	
	public function generateBalancePlotData() {
		$myToday=clone $this->today;
		$result=array();
		
		$balance=0;
    	foreach ($this->baseLineList as $singleItem) {
    		//check if we've gone past our requested date
    		
    			if ($singleItem['amountType']=='expense') {
    				$balance-=$singleItem['amount'];
    			}
    			else {
    				$balance+=$singleItem['amount'];
    			}
    			$result[]=array('date'=>$singleItem['dateObj']->format('d-m-Y'), 'balance'=>$balance);
    		
    	}
    	return $result;
	}
	
	#takes a frequency object and returns the baselinelist limited by the passed in frequency
	public function getPeriodLimitedBaseLineList($frequency) {
		//print_r($this->baseLineList);
		$running=True;
		$counter=0;
		$results=Array();
		
		//create a dattime object containing only todays date
        $todayWithTime=new DateTime("now");
        $today=new DateTime($todayWithTime->format('Y-m-d'));
        
		//calculate the cut off in days based on the frequency passed in
		$frequencyCutoffDays=$frequency->get_value();
		
		//iterate over the baseline objects and add then to the results array, stop when we have passed the frequency interval passed in
		while ($running) {
		    $singleItem=$this->baseLineList[$counter];
		 	$dateObj=$singleItem['dateObj'];
			$itemID=$singleItem['itemID'];
			$amount=$singleItem['amount'];
			$amountType=$singleItem['amountType'];
			
			$interval = $today->diff($dateObj);
			if ($interval->d>$frequencyCutoffDays) {
				$running=False;
			}
			else {
				$results[]=$singleItem;
			}
			
			//increment our iterator and make sure we dont go over the array size
			$counter+=1;
			if ($counter>=count($this->baseLineList)) {
				$running=False;
			}
		}
		return $results;
	}
	
	public function getOffsetAtDate($requestedDate) {
	
	}	

}
?>