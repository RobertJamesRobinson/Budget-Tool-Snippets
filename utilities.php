<?php

function toDollars($amount) {
    $amount=(int)$amount;
    $amount*=0.01;
    return money_format('%+#10n',$amount);
}

function pc($percentage) {
    return round($percentage*100);
}

#converts the string date mm/dd/yyyy into mysql friendly yyyy-mm-dd
function switch_date($myDate) {
    if (strpos($myDate,'/')!==False) {
	    $chunks=explode("/",$myDate);
	    return $chunks[2]."-".$chunks[0]."-".$chunks[1];
    }
    return $myDate;
}

#converts the string date yyyy-mm-dd to jquery friendly mm/dd/yyyy
function switch_date_back($myDate) {
	$chunks=explode("-",$myDate);
	return $chunks[1]."/".$chunks[2]."/".$chunks[0];
}

//check if two datetime object are the same date only, same date returns 0, a<b returns -1, a>b returns +1
function safeCompareDates($a, $b) {
	//first check if they are identical, as this is the case which is unpredictable using standard diff
	if ($a->format('Y-m-d')==$b->format('Y-m-d')) {
		return 0;
	}
	if ($a<$b) {
		return -1;
	}
	if ($a>$b) {
		return 1;
	}
}

function test_compare_arrays($a,$b) {
	$length=count($a);
	$length2=count($b);
	if ($length2!=$length) {
		print "array length different<br/>length of First array  = $length<br/>length of Second array = $length2<br/>";
		return false;
	}
	for ($i=0; $i<$length; $i++) {
		if ($a[$i]!=$b[$i]) {
			print "array different at index $i<br/>First array value  = $a[$i]<br/>Second array value = $b[$i]<br/>";
			return false;
		}
	}
	return true;
}

function dateSorter($a,$b) {
	$item1date=$a['dateObj'];
	$item2date=$b['dateObj'];
	$differ=$item1date->diff($item2date);
	return $differ->invert;
}

?>