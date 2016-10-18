<?php
include_once("utilities.php");

/*
 * Manages all logic for the expense class, handles expense details, description, frequency and amount
 */
class Expense {
    //attributes
    protected $db;
    protected $expenseID;
    protected $description;
    protected $frequencyID;
    protected $amount;
    protected $categoryID;
    protected $datePoint;

    //constructor
    public function __construct(&$db) {
        $this->db=$db;
    }

    //selects an expense for further processing, by ID
    public function select($expenseID) {
        $sql="select * from expense where expenseID='$expenseID'";
        $result=$this->db->query($sql);
        $this->expenseID=$result[0]['expenseID'];
        $this->description=$result[0]['description'];
        $this->frequencyID=$result[0]['frequencyID'];
        $this->amount=$result[0]['amount'];
        $this->categoryID=$result[0]['categoryID'];
        $this->datePoint=$result[0]['datePoint']=='NULL'?'':$result[0]['datePoint'];
    }

    //commits any changes in this object to the DB
    public function update() {
        //setup the query
        $sql="
        update expense
        set expenseID=".$this->expenseID.",
        description='".$this->description."',
        frequencyID=".$this->frequencyID.",
        amount='".$this->amount."',
        categoryID=".$this->categoryID.",
        datePoint=".($this->datePoint==''?"null":"'".switch_date($this->datePoint)."'")." 
        where expenseID='".$this->expenseID."'
        ";
        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, doesnt require a select, but does require each field to have a value
    public function insert() {
        //prepare the query
        $sql="
        insert into expense
        (description, frequencyID, amount, categoryID, datePoint)
        values
        ('".$this->description."',
        ".$this->frequencyID.",
        ".$this->amount.",
        ".$this->categoryID.",
        ".($this->datePoint==''?"null":"'".switch_date($this->datePoint)."'").")
        ";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, in a single line, doesnt require a select or any previously inserted values
    public function quickInsert($description, $frequencyID, $amount, $categoryID, $datePoint) {
        //prepare the query
        $sql="
        insert into expense
        (description, frequencyID, amount, categoryID, datePoint)
        values
        ('$description',$frequencyID,$amount,$categoryID,".($datePoint==''?"null":"'".switch_date($datePoint)."'").")
        ";

		//perform the query
        $this->db->query($sql);
    }

    //deletes an expense, by expense ID
    public function delete($expenseID) {
        //prepare the query
        $sql="delete from expense where expenseID='$expenseID';";

        //perform the query
        $this->db->query($sql);
    }

    //GETTERS

    //returns the expenseID
    public function get_expenseID() {
        return $this->expenseID;
    }

    //returns the description
    public function get_description() {
        return $this->description;
    }

    //returns the frequencyID of this expense
    public function get_frequencyID() {
        return $this->frequencyID;
    }

    //returns the amount of this expense
    public function get_amount() {
        return $this->amount;
    }

    //returns the categoryID of this expense
    public function get_categoryID() {
        return $this->categoryID;
    }

	//returns the datePoint of this expense
    public function get_datePoint() {
        return $this->datePoint;
    }

    //SETTERS

    //sets the expenseID
    public function set_expenseID($value) {
        $this->expenseID=$value;
    }

    //sets the expenses description
    public function set_description($value) {
        $this->description=$value;
    }

    //sets the expenses frequencyID
    public function set_frequencyID($value) {
        $this->frequencyID=$value;
    }

    //sets the expenses amount
    public function set_amount($value) {
        $this->amount=$value;
    }

    //sets the expenses categoryID
    public function set_categoryID($value) {
        $this->categoryID=$value;
    }

	//sets the expenses datePoint
    public function set_datePoint($value) {
        $this->datePoint=$value;
    }

    //OTHER USEFULL METHODS

    //returns an associative array of associative arrays, each key is the expenseID, the value is the remaining fields from the expenses table
    public function getExpenseList() {
        $result=array();
        $sql="
        select e.expenseID as expenseID, e.description as description, e.frequencyID as frequencyID,
        e.amount as amount, e.categoryID as categoryID, f.description as frequency, c.description as category
        from expense e
        left join frequency f on e.frequencyID=f.frequencyID
        left join category c on e.categoryID=c.categoryID
        ";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
            $result[$row['expenseID']]=array(
            'expenseID'=>$row['expenseID'],
            'description'=>$row['description'],
            'frequencyID'=>$row['frequencyID'],
            'amount'=>$row['amount'],
            'categoryID'=>$row['categoryID'],
            'frequency'=>$row['frequency'],
            'category'=>$row['category']);
        }
        return $result;
    }

    //get the total expenses, in terms of the passed in frequency
    public function getTotalExpenses(Frequency $frequency) {
        //find the passed in frequency value
		$sql="select value from frequency where frequencyID='".$frequency->get_frequencyID()."';";
		if($res1=$this->db->query($sql)) {
	        $frequencyValue=$res1[0]['value'];
	        $sql2="select sum(round(amount*(".$frequencyValue."/f.value))) as amount from expense e left join frequency f on f.frequencyID=e.frequencyID;";
			if ($res2=$this->db->query($sql2)) {
			    return $res2[0]['amount'];
			}
			else {
				print "error in query at second at Expense::getTotalExpenses";
			}
		}
		else {
			print "error in query at first at Expense::getTotalExpenses";
		}
    }
    
    //get all expense details in a format that is baseline friendly: (expenseID, amount, datePoint, frequencyID)
    public function getBaseLineExpenseList() {
    	$result=array();
    	$sql="select expenseID, amount, datePoint, frequencyID from expense";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
        	$result[]=array(
			'expenseID'=>$row['expenseID'], 
			'amount'=>$row['amount'], 
			'datePoint'=>$row['datePoint'], 
			'frequencyID'=>$row['frequencyID']);
        }
        return $result;
    }
}
?>