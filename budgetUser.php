<?php
include_once("frequency.php");

/*
 * Manages all logic for the budgetUser class, handles user details, name, password
 */
class BudgetUser {
    //attributes
    protected $db;
    protected $username;
    protected $firstname;
    protected $lastname;
    protected $password;
    protected $email;
    protected $name;

    //constructor
    public function __construct(&$db) {
        $this->db=$db;
    }

    //selects a user for further processing
    public function select($username) {
        $sql="select * from budgetUser where username='$username'";
        $result=$this->db->query($sql);
        $this->username=$result[0]['username'];
        $this->firstname=$result[0]['firstname'];
        $this->lastname=$result[0]['lastname'];
        $this->password=$result[0]['password'];
        $this->email=$result[0]['email'];
        $this->name=$result[0]['firstname']." ".$result[0]['lastname'];
    }

    //commits any changes in this object to the DB
    public function update() {
        //setup the query
        $sql="
        update budgetUser
        set firstname='".$this->firstname."',
        lastname='".$this->lastname."',
        password='".$this->password."',
        username='".$this->username."',
        email='".$this->email."' 
        where username='".$this->username."'
        ";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, doesnt require a select, but does require each field to have a value
    public function insert() {
        //prepare the query
        $sql="
        insert into budgetUser
        (username, password, firstname, lastname, email)
        values
        ('".$this->username."',
        '".$this->password."',
        '".$this->firstname."',
        '".$this->lastname."',
        '".$this->email."')
        ";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, in a single line, doesnt require a select or any previously inserted values
    public function quickInsert($username, $password, $firstname, $lastname, $email) {
        //prepare the query
        $sql="
        insert into budgetUser
        (username, password, firstname, lastname, email)
        values
        ('$username','$password','$firstname','$lastname', '$email')
        ";

        //perform the query
        $this->db->query($sql);
    }

    //deletes a user, by username
    public function delete($username) {
        //prepare the queries
        $sql="delete from income where username='$username';";
        $sql2="delete from budgetUser where username='$username'";

        //perform the queries
        $this->db->query($sql);
		$this->db->query($sql2);
    }

    //GETTERS

    //returns the username
    public function get_username() {
        return $this->username;
    }

    //returns the users full name
    public function get_name() {
        return $this->name;
    }

    //returns the users first name
    public function get_firstname() {
        return $this->firstname;
    }

    //returns the users lastname
    public function get_lastname() {
        return $this->lastname;
    }

    //returns the users password
    public function get_password() {
        return $this->password;
    }

    public function get_email() {
        return $this->email;
    }

    //SETTERS

    //sets the users firstname, and updates the derived name field
    public function set_firstname($value) {
        $this->firstname=$value;
        $this->name=$this->firstname." ".$this->lastname;
    }

    //sets the users lastname, and updates the derived name field
    public function set_lastname($value) {
        $this->lastname=$value;
        $this->name=$this->firstname." ".$this->lastname;
    }

    //sets the users password
    public function set_password($value) {
        $this->password=$value;
    }

    //sets the users username
    public function set_username($value) {
        $this->username=$value;
    }
    
    public function set_email($value) {
        $this->email=$value;
    }

    //OTHER USEFULL METHODS

    //returns a list of all usernames
    public function getUsernameList() {
        $result=array();
        $sql="select username from budgetUser";
        $queryResult=$this->db->query($sql);
        foreach($queryResult as $row) {
            $result[]=$row['username'];
        }
        return $result;
    }

    public function getUsernameLookup() {
        $result=array();
        $sql="select username,firstname,lastname,concat(firstname, ' ' ,lastname) as name from budgetUser;";
        $queryResult=$this->db->query($sql);
        foreach($queryResult as $row) {
            $result[$row['username']]=array('name'=>$row['name'],'firstname'=>$row['firstname'], 'lastname'=>$row['lastname']);
        }
        return $result;
    }

	//same as the function below, except returns the datepoint associated with the payment which coincides with the income item found below
	public function getUserIncomeDatePoint() {
        $results=array();
        $sql="
        select f.frequencyID as frequency, i.datePoint, sum(i.amount/f.value) as derived
        from income i
        left join frequency f on i.frequencyID=f.frequencyID
        where i.username='".$this->username."'
        group by f.description
        order by derived DESC;
        ";
		$queryResult=$this->db->query($sql);
		return $queryResult[0]['datePoint'];
	}

    public function getUserIncomeFrequency() {
        $results=array();
        $sql="
        select f.frequencyID as frequency, sum(i.amount/f.value) as derived
        from income i
        left join frequency f on i.frequencyID=f.frequencyID
        where i.username='".$this->username."'
        group by f.description
        order by derived DESC;
        ";
		$queryResult=$this->db->query($sql);
		return new Frequency($this->db, $queryResult[0]['frequency']);
    }
}
?>