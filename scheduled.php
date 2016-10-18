<?php
setlocale(LC_MONETARY, 'en_AU');
date_default_timezone_set('Australia/Victoria');
include_once("connect.php");
include_once("basicDate.php");
include_once("frequency.php");
include_once("budgetUser.php");
include_once("expense.php");
include_once("income.php");

#include_once("utilities.php");
#includes
#include_once("connect.php");
#include_once("datePoint.php");
#include_once("frequency.php");


$db=new Connect();
$dateObj=new BasicDate();
$freqObj=new Frequency($db);
$userObj=new BudgetUser($db);
$expObj=new Expense($db);
$incObj=new Income($db);
$lockFilename='schedule.lock';
$adminEmail='rjrobinson77@gmail.com';
$cutoff_time=600; #the time after which the admin is notified that the lock file has frozen
$reset_time=1800;  #the time in seconds after which the lock file is removed regardless of it's state
$debug=False;

#sends an entry to a log file
function MLog($message) {
    global $debug;
    if ($debug) {
        $now=date("Y-m-d H:i:s");
        $fh=fopen('log.txt','a');
        fwrite($fh,$now." - ".$message);
        fclose($fh);
    }
}

#fully automate the lock file handling. Must be fast. returns True if the lock file is in use. returns False if there was no lock file
function isLocked() {
    global $lockFilename, $adminEmail, $cutoff_time, $reset_time;
    if (file_exists($lockFilename)) {
        #check the contents of the lock file
        $fh=fopen($lockFilename,'r');
        $counter=0;
        $lockTimeStamp=0;
        $adminNotified=False;
        $timeStamp_past_cutoff=False;
        while ($line = fgets($fh)) {
            $line=rtrim($line);
            if ($counter==0) {
                $lockTimeStamp=$line;
            }
            if ($counter==1) {
                $adminNotified=($line=='admin notified');
            }
            $counter+=1;
        }
        fclose($fh);
        
        #work out if the timestamp in the lock file is past the cutoff time
        $nowStamp=time();
        if ($nowStamp>$lockTimeStamp+$cutoff_time) {
            $timeStamp_past_cutoff=True;
        }
        
        #notify the Admin and add the flag to the lock file
        if ($timeStamp_past_cutoff and !$adminNotified) {
            $fh=fopen($lockFilename,'a');
            fwrite($fh,'admin notified');
            fclose($fh);
            email($adminEmail,'Budgetting System Scheduler Frozen','Warning only: If nothing else fails the lock file will automatically be deleted in 20 minutes');
        }
        
        #kill the lock file if we are past the reset time
        if ($nowStamp>$lockTimeStamp+$reset_time) {
            softUnlock();
            return False;
        }
    }
    #there is no lock file, so we are not locked
    else {
        return False;
    }
    
    #something went wrong, we ARE locked
    return True;
}

#sets a lock so that the scheduler script can only ever be run once at a time, if the lock file already exists, does not reset the lock
function softLock() {
    global $lockFilename;
    if (!file_exists($lockFilename)) {
        $fh=fopen($lockFilename,'w');
        $nowStamp=time();
        fwrite($fh,"$nowStamp\n");
        fclose($fh);
    }
}

#deletes the lock file, so that future instances of the scheduler script can execute
function softUnlock() {
    global $lockFilename;
    if (file_exists($lockFilename)) {
        unlink($lockFilename);
    }
}

#email helper function, just pass the details we care about
function email($address,$subject,$message) {
    $headers   = array();
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-type: text/plain; charset=iso-8859-1";
    $headers[] = "From: Sender Name <rjrobinson77@gmail.com>";
    $headers[] = "Reply-To: Recipient Name <rjrobinson77@gmail.com>";
    $headers[] = "Subject: {$subject}";
    $headers[] = "X-Mailer: PHP/".phpversion();

    mail($address, $subject, $message, implode("\r\n",$headers));
    MLog("EMAIL OUTGOING:\nAddress: $address\nSubject: $subject\nMessage: $message\n");
}

#finds any scheduled tasks which havent been executed and logged yet, and returns a list of names of them to be executed
function findTasks() {
    global $freqObj, $db, $dateObj;
    $freq_list=$freqObj->getFrequencyListIDPeriod();
    $sql="select * from scheduledTask";
    $tasks=$db->query($sql);
    
    #find tasks that have a date point that would hit today
    $todays_tasks=array();
    foreach($tasks as $single_task) {
        $thisFreq_string=$freq_list[$single_task['frequencyID']];
        $dateObj->setNextValidDateFromFrequencyAndDatePoint($thisFreq_string,$single_task['datePoint']);
        if ($dateObj->isToday()) {
            $todays_tasks[]=$single_task;
        }
    }
    
    #ok, we now have a list of tasks which might fall on today, further eliminate ones that have already been executed today
    $sql="select * from taskLog where date(datestamp) = date(now()) and time(datestamp)<time(now())"; #='$frequencyID'";
    $tasks_already_run=$db->query($sql);
    MLog("tasks already run today:\n");
    foreach($tasks_already_run as $logT) {
        MLog("taskLogID: ".$logT['taskLogID'].", taskID: ".$logT['taskID'].", dateStamp: ".$logT['dateStamp']."\n");
    }
    $tasks_run=array();
    foreach($tasks_already_run as $already_run_task) {
        if (!in_array($already_run_task['taskID'], $tasks_run)) {
            $tasks_run[]=$already_run_task['taskID'];
        }
    }
    
    #eliminate the tasks which have already run from the list of tasks which should run today
    $tasks_to_run=array();
    foreach($todays_tasks as $task) {
        if(!in_array($task['taskID'],$tasks_run)) {
            $tasks_to_run[]=$task;
        }
    }
    MLog("scheduled Tasks found to run:\n");
    foreach($tasks_to_run as $logT) {
        MLog("taskID: ".$logT['taskID'].", description: ".$logT['description'].", frequencyID: ".$logT['frequencyID'].", name: ".$logT['name'].", timeOfDay: ".$logT['timeOfDay'].", datePoint: ".$logT['datePoint']."\n");
    }
    return $tasks_to_run;
}

#sends an email of todays incomes to the budget user defined in the task name
function daily_income_notification($username) {
    
}

#resets all date points to the next possible one. This is to prevent performance degradation over time when calculating datepoints
function weekly_datepoint_recalibration() {
    global $expObj, $freqObj, $dateObj, $incObj;
    #start with expense datepoints
    $freq_list=$freqObj->getFrequencyListIDPeriod();
    $baseExpenseList=$expObj->getBaseLineExpenseList(); #select expenseID, amount, datePoint, frequencyID from expense
    foreach($baseExpenseList as $single_expense) {
        #MLog('expenseID: '.$single_expense['expenseID'].', amount: '.$single_expense['amount'].', datePoint: '.$single_expense['datePoint'].', frequencyID: '.$single_expense['frequencyID']."\n");
        $frequency_string=$freq_list[$single_expense['frequencyID']];
        $datePoint=$single_expense['datePoint'];
        $dateObj->setNextValidDateFromFrequencyAndDatePoint($frequency_string,$datePoint);
        $expObj->select($single_expense['expenseID']);
        #MLog("before: ".$expObj->get_datePoint()."\n");
        $expObj->set_datePoint($dateObj->toMysql());
        #MLog("after: ".$expObj->get_datePoint()."\n");
        $expObj->update();
    }
    #now do income datepoints
    $baseIncomeList=$incObj->getBaseLineIncomeList(); #select incomeID, amount, datePoint, frequencyID from income
    foreach($baseIncomeList as $single_income) {
        #MLog('incomeID: '.$single_income['incomeID'].', amount: '.$single_income['amount'].', datePoint: '.$single_income['datePoint'].', frequencyID: '.$single_income['frequencyID']."\n");
        $frequency_string=$freq_list[$single_income['frequencyID']];
        $datePoint=$single_income['datePoint'];
        $dateObj->setNextValidDateFromFrequencyAndDatePoint($frequency_string,$datePoint);
        $incObj->select($single_income['incomeID']);
        $incObj->set_datePoint($dateObj->toMysql());
        $incObj->update();
    }
}

#sends an email of todays expenses to the budget user defined in the task name
function daily_expense_notification($username) {
    global $userObj,$expObj,$dateObj,$freqObj;
    #get the users email address and name from the database
    $userObj->select($username);
    $address=$userObj->get_email();
    $subject="Daily Expenses Notification for ".$userObj->get_name();
    
    #build the expenses report for today
    $message="Expense Report for ".date("d-m-Y")."\r\n";
    $freq_list=$freqObj->getFrequencyListIDPeriod();
    $baseExpenseList=$expObj->getBaseLineExpenseList(); #select expenseID, amount, datePoint, frequencyID from expense
    $message_entry=array();
    foreach($baseExpenseList as $single_expense) {
        MLog('expenseID: '.$single_expense['expenseID'].', amount: '.$single_expense['amount'].', datePoint: '.$single_expense['datePoint'].', frequencyID: '.$single_expense['frequencyID']."\n");
        $frequency_string=$freq_list[$single_expense['frequencyID']];
        $datePoint=$single_expense['datePoint'];
        $dateObj->setNextValidDateFromFrequencyAndDatePoint($frequency_string,$datePoint);
        if ($dateObj->isToday()) {
            $expObj->select($single_expense['expenseID']);
            $message_entry[]=$expObj->get_description()." - ".money_format('%n',$single_expense['amount']*0.01);
        }
    }
    $message.=implode("\r\n",$message_entry);
    $message.="\r\nThis is an automated message from Robs Budgeting system. Please do not reply.\r\n";
    #send the email!
    if (count($message_entry)>0) {
        email($address,$subject,$message);
    }
    else {
        MLog("daily_expense_notification: Nothing to report today, no email sent\n");
        #email($address,$subject,$message);
    }
    
}

#executes the task defined by taskName, the functions for the tasks should be defined above this function
function executeTask($task) {
    global $db;
    #daily expense notification task
    if (strlen($task['name'])>strlen("daily_expenses_notification") and substr($task['name'],0,27)=="daily_expenses_notification") {
        $chunks=explode("|",$task['name']);
        daily_expense_notification($chunks[1]);
        $sql="insert into taskLog (taskID) values ('".$task['taskID']."')";
        $db->query($sql);
    }
    #weekly date point re-calibration task
    if ($task['name']=="weekly_datepoint_recalibration") {
        weekly_datepoint_recalibration();
    }
}

#checks to see if a given time string, in the format hh:mm:ss has already passed now()
function timeHasPassed($timeString) {
    $nowTime=date("H:i:s");
    $now_chunks=explode(':', $nowTime);
    $the_chunks=explode(':', $timeString);
    if ($now_chunks[0]<$the_chunks[0]) {
        return False;
    }
    elseif ($now_chunks[0]>$the_chunks[0]) {
        return True;
    }
    #hours must match...
    elseif ($now_chunks[1]<$the_chunks[1]) {
        return False;
    }
    elseif ($now_chunks[1]>$the_chunks[1]) {
        return True;
    }
    #minutes must match...
    elseif ($now_chunks[2]<$the_chunks[2]) {
        return False;
    }
    elseif ($now_chunks[2]>=$the_chunks[2]) {
        return True;
    }
    exit("Weird shit happened in scheduler in timeHasPassed() function\n");
}

#program execution point, start here, always
function run() {
    #lock execution to us
    MLog("Trying lock file...\n");
    if(isLocked()) {
        MLog("Scheduler cannot run while lock file is in place\n");
        exit();
    }
    softLock();
    
    #get tasks and execute them
    $taskList=findTasks();
    foreach ($taskList as $singleTask) {
        if(timeHasPassed($singleTask['timeOfDay'])) {
            executeTask($singleTask);
        }
        
    }
    
    #release the lock and exit out
    softUnlock();
}

run();

#NOTES:
#email('rjrobinson77@gmail.com','test subject','hello Robert');

#query to find entry in task log table of given taskID for today, which has already been processed
#used to find any entries which have already been processed, if this query returns a value, it usually means 
#select * from taskLog where taskID=43 and date(datestamp) = date(now()) and time(datestamp)<time(now());

?>
