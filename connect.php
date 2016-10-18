<?php
/* connect to the DB, stores the DB connection, includes a query parser,
 * just to abstract away all DB functions to a simple interface
 */
class Connect {

    //Attributes
    protected $link=null;
    public $error;
    private $hostName='127.0.0.1';
    private $username='root';
    private $pword='';
    private $dbName='budget';
    private $port=3306;

    //Constructor
    public function __construct() {
        $this->link=new mysqli($this->hostName,$this->username,$this->pword,$this->dbName);
        if ($this->link->connect_errno) {
            $this->error=$this->link->connect_error;
            print "could not connect to DB: ".$this->error;
        }
    }

    //Destructor
    public function __destruct() {
        $this->close();
    }

    //close the DB connection, should be called when the connection is no longer needed
    public function close() {
        mysqli_close($this->link);
    }

    //process a query and return a user friendly result array
    //returns TRUE for querys that have no retyurn value
    //returns an assoc array for queries that return a value
    //returns false on failure
    public function query($myQuery) {
        $resultingArray=array();
		$this->link->autocommit(TRUE);
        $result=$this->link->query($myQuery);

        //mysql returns false on failed query
        if (is_a($result, 'mysqli_result')) {
            //resourse returned from a query that returns a value
            while($row=mysqli_fetch_assoc($result)) {
                array_push($resultingArray,$row);
            }
			
        }
		elseif ($result===False) {
			$this->error=$this->link->error;
			print "query error: ".$this->error;
        }
        return $resultingArray;
    }
}
?>