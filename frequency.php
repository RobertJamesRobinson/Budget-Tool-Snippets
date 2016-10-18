<?php
/*
 * Manages all logic for the frequency class, handles frequency details, descriptions and values
 * The values are for multiplying by an amount to get the descriptions rate, ie, for month, multiply by the month value
 */
class Frequency {
    //attributes
    protected $db;
    protected $frequencyID;
    protected $description;
    protected $value;

    //constructor
    public function __construct(&$db, $frequencyID=null) {
        $this->db=$db;
		if ($frequencyID) {
			if (is_numeric($frequencyID)) {
				$this->select($frequencyID);
			}
			else {
				$this->selectByDesc($frequencyID);
			}
		}
    }

    //selects a frequency for further processing, by ID
    public function select($frequencyID) {
        $sql="select * from frequency where frequencyID='$frequencyID'";
        $result=$this->db->query($sql);
        $this->frequencyID=$result[0]['frequencyID'];
        $this->description=$result[0]['description'];
        $this->value=$result[0]['value'];
    }
	
	//selects a frequency for further processing, by Description
    public function selectByDesc($description) {
        $sql="select * from frequency where description='$description'";
		$result=$this->db->query($sql);
		$this->frequencyID=$result[0]['frequencyID'];
        $this->description=$result[0]['description'];
        $this->value=$result[0]['value'];
    }

    //commits any changes in this object to the DB
    public function update() {
        //setup the query
        $sql="
        update frequency
        set frequencyID=".$this->frequencyID.",
        description='".$this->description."',
        value=".$this->value."
        where frequencyID=".$this->frequencyID;

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, doesnt require a select, but does require each field to have a value
    public function insert() {
        //prepare the query
        $sql="
        insert into frequency
        (description, value)
        values
        ('".$this->description."',
        ".$this->value.")
        ";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, in a single line, doesnt require a select or any previously inserted values
    public function quickInsert($description, $value) {
        //prepare the query
        $sql="
        insert into frequency
        (description, value)
        values
        ('$description',$value)
        ";

        //perform the query
        $this->db->query($sql);
    }

    //deletes a frequency, by frequency ID
    public function delete($frequencyID) {
        //prepare the query
        $sql="delete from frequency where frequencyID='$frequencyID';";

        //perform the query
        $this->db->query($sql);
    }

    //GETTERS

    //returns the frequencyID of this frequency
    public function get_frequencyID() {
        return $this->frequencyID;
    }

    //returns the description of this frequency
    public function get_description() {
        return $this->description;
    }

    //returns the value of this frequency
    public function get_value() {
        return $this->value;
    }

    //SETTERS

    //sets the frequencyID
    public function set_frequencyID($value) {
        $this->incomeID=$value;
    }

    //sets the description for this frequency
    public function set_description($value) {
        $this->description=$value;
    }

    //sets the value of this frequency
    public function set_value($value) {
        $this->value=$value;
    }

    //OTHER USEFULL METHODS

	//for debug purposes
	public function dump() {
		print "<p>ID: ".$this->frequencyID."</p>";
		print "<p>Description: ".$this->description."</p>";
		print "<p>Value: ".$this->value."</p>";
	}

    //returns an associative array of descriptions vs values, for all the frequency items
    public function getFrequencyList() {
        $result=array();
        $sql="select * from frequency";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
            $result[$row['description']]=$row['value'];
        }
        return $result;
    }

	//
	public function getFrequencyListIDValue() {
        $result=array();
        $sql="select * from frequency";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
            $result[$row['frequencyID']]=$row['value'];
        }
        return $result;
    }
    
    public function getFrequencyListIDPeriod() {
    	$result=array();
        $result[1]='P1D';
        $result[2]='P1W';
        $result[3]='P2W';
        $result[4]='P1M';
        $result[5]='P2M';
        $result[6]='P3M';
        $result[7]='P1Y';
        return $result;
    }
	
	//
    public function getFrequencies() {
        $result=array();
        $sql="select * from frequency";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
            $result[$row['frequencyID']]=$row['description'];
        }
        return $result;
    }
}
?>