<?php
/*
 * Manages all logic for the income class, handles income details, username, frequency and amount
 */
include_once("frequency.php");

class Income {
    //attributes
    protected $db;
    protected $incomeID;
    protected $username;
    protected $frequencyID;
    protected $amount;
    protected $description;
    protected $datePoint;

    //constructor
    public function __construct(&$db) {
        $this->db=$db;
    }

    //selects an income for further processing, by ID
    public function select($incomeID) {
        $sql="select * from income where incomeID='$incomeID'";
        $result=$this->db->query($sql);
        $this->incomeID=$result[0]['incomeID'];
        $this->username=$result[0]['username'];
        $this->frequencyID=$result[0]['frequencyID'];
        $this->amount=$result[0]['amount'];
        $this->description=$result[0]['description'];
        $this->datePoint=$result[0]['datePoint']=='NULL'?'':$result[0]['datePoint'];
    }

    //commits any changes in this object to the DB
    public function update() {
        //setup the query
        $sql="
        update income
        set incomeID='".$this->incomeID."',
        username='".$this->username."',
        frequencyID='".$this->frequencyID."',
        amount='".$this->amount."',
        description='".$this->description."',
        datePoint=".($this->datePoint==''?"null":"'".switch_date($this->datePoint)."'")." 
        where incomeID='".$this->incomeID."'";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, doesnt require a select, but does require each field to have a value
    public function insert() {
        //prepare the query
        $sql="
        insert into income
        (username, frequencyID, amount, description, datePoint)
        values
        ('".$this->username."',
        ".$this->frequencyID.",
        ".$this->amount.",
        '".$this->description."'
        ".($this->datePoint==''?"null":"'".switch_date($this->datePoint)."'").")
        ";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, in a single line, doesnt require a select or any previously inserted values
    public function quickInsert($username, $frequencyID, $amount, $description, $datePoint) {
        //prepare the query
        $sql="
        insert into income
        (username, frequencyID, amount, description, datePoint)
        values
        ('$username','$frequencyID','$amount','$description',".($datePoint==''?"null":"'".switch_date($datePoint)."'").")
        ";

        //perform the query
        $this->db->query($sql);
    }

    //deletes an income, by income ID
    public function delete($incomeID) {
        //prepare the query
        $sql="delete from income where incomeID='$incomeID';";

        //perform the query
        $this->db->query($sql);
    }

    //GETTERS

    //returns the incomeID
    public function get_incomeID() {
        return $this->incomeID;
    }

    //returns the username
    public function get_username() {
        return $this->username;
    }

    //returns the frequencyID of this income
    public function get_frequencyID() {
        return $this->frequencyID;
    }

    //returns the amount of this income
    public function get_amount() {
        return $this->amount;
    }

    //returns the description of this income
    public function get_description() {
        return $this->description;
    }
    
    //returns the datePoint of this income
    public function get_datePoint() {
        return $this->datePoint;
    }

    //SETTERS

    //sets the incomeID
    public function set_incomeID($value) {
        $this->incomeID=$value;
    }

    //sets the incomes associated username
    public function set_username($value) {
        $this->username=$value;
    }

    //sets the incomes frequencyID
    public function set_frequencyID($value) {
        $this->frequencyID=$value;
    }

    //sets the income amount
    public function set_amount($value) {
        $this->amount=$value;
    }

    //sets the income description
    public function set_description($value) {
        $this->description=$value;
    }
    
    //sets the income datePoint
    public function set_datePoint($value) {
        $this->datePoint=$value;
    }

    //OTHER USEFULL METHODS

    //returns an array of associative arrays, for all the income items for a single user
    public function getIncomeListByUser($username) {
        $result=array();
        $sql="select i.incomeID, i.username, i.frequencyID, i.amount, i.description from income i left join frequency f on i.frequencyID=f.frequencyID where i.username='$username'";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
			$result[]=array('incomeID'=>$row['incomeID'],'frequency'=>new Frequency($this->db, $row['frequencyID']),'amount'=>$row['amount'],'description'=>$row['description']);
        }
        return $result;
    }

    //returns a more detailed income list as an associative array
    public function getDetailedIncomeList() {
        $result=array();
        $sql="
        select i.incomeID as incomeID, i.username as username, i.frequencyID as frequencyID, i.amount as amount, i.description as description,
        u.firstname as firstname, u.lastname as lastname, concat(u.firstname,' ',u.lastname) as name, f.description as frequency
        from income i
        left join budgetUser u on i.username=u.username
        left join frequency f on f.frequencyID=i.frequencyID
        ";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
            $result[$row['incomeID']]=array('incomeID'=>$row['incomeID'], 'username'=>$row['username'], 'frequencyID'=>$row['frequencyID'], 'amount'=>$row['amount'], 'description'=>$row['description'], 'firstname'=>$row['firstname'], 'lastname'=>$row['lastname'], 'name'=>$row['name'], 'frequency'=>$row['frequency'], );
        }
        return $result;
    }

    //returns a total users income for the given frequency
    public function getTotalUserIncome(Frequency $frequency, $username) {
        //get this users income items
        $incomeList=$this->getIncomeListByUser($username);

        //process the incomes
        $result=0;
        foreach ($incomeList as $income) {
            $result+=($income['amount']*($frequency->get_value()/$income['frequency']->get_value()));
        }
        return (int)$result;
    }
    //get the total expenses, in terms of the passed in frequency description
    public function getTotalIncome(Frequency $frequency) {
        //find the passed in frequency value
        $sql="select value from frequency where frequencyID='".$frequency->get_frequencyID()."';";
        $res1=$this->db->query($sql);
        $frequencyValue=$res1[0]['value'];
        $sql2="select sum(round(amount*(".$frequencyValue."/f.value))) as amount from income i left join frequency f on f.frequencyID=i.frequencyID;";
        $res2=$this->db->query($sql2);
        return $res2[0]['amount'];
    }
    
    //get all income details in a format that is baseline friendly: (incomeID, amount, datePoint, frequencyID)
    public function getBaseLineIncomeList() {
    	$result=array();
    	$sql="select incomeID, amount, datePoint, frequencyID from income";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
        	$result[]=array(
			'incomeID'=>$row['incomeID'], 
			'amount'=>$row['amount'], 
			'datePoint'=>$row['datePoint'], 
			'frequencyID'=>$row['frequencyID']);
        }
        return $result;
    }
}
?>