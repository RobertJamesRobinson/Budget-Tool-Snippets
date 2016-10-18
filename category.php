<?php
/*
 * Manages all logic for the category class, handles descriptions
 */
class Category {
    //attributes
    protected $db;
    protected $categoryID;
    protected $description;

    //constructor
    public function __construct(&$db) {
        $this->db=$db;
    }

    //selects a category for further processing, by ID
    public function select($categoryID) {
        $sql="select * from category where categoryID='$categoryID'";
        $result=$this->db->query($sql);
        $this->categoryID=$result[0]['categoryID'];
        $this->description=$result[0]['description'];
    }

    //commits any changes in this object to the DB
    public function update() {
        //setup the query
        $sql="
        update category
        set categoryID=".$this->categoryID.",
        description='".$this->description."'
        ";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, doesnt require a select, but does require each field to have a value
    public function insert() {
        //prepare the query
        $sql="
        insert into category
        (description)
        values
        ('".$this->description."')
        ";

        //perform the query
        $this->db->query($sql);
    }

    //inserts a new record, in a single line, doesnt require a select or any previously inserted values
    public function quickInsert($description) {
        //prepare the query
        $sql="
        insert into category
        (description)
        values
        ('$description')
        ";

        //perform the query
        $this->db->query($sql);
    }

    //deletes a category, by category ID
    public function delete($categoryID) {
        //prepare the query
        $sql="delete from category where categoryID=$categoryID;";

        //perform the query
        $this->db->query($sql);
    }

    //GETTERS

    //returns the categoryID
    public function get_categoryID() {
        return $this->categoryID;
    }

    //returns the description
    public function get_description() {
        return $this->description;
    }

    //SETTERS

    //sets the categoryID
    public function set_categoryID($value) {
        $this->categoryID=$value;
    }

    //sets the category description
    public function set_description($value) {
        $this->description=$value;
    }

    //OTHER USEFULL METHODS

    //returns an associative array of category descriptions, where the key is the categoryID
    public function getCategoryList() {
        $result=array();
        $sql="select * from category";
        $queryResult=$this->db->query($sql);
        foreach ($queryResult as $row) {
            $result[$row['categoryID']]=$row['description'];
        }
        return $result;
    }
}
?>