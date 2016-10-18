<?php
/*
 * contains all the bindings between the classes, this is where the logic lives
 * includes all processing and report generation, also acts as an AJAX responder
 */

/*
TODO:
1. fully implement my basic date class to replace DateTime usage
2. investigate differences between prod and dev server values for budget balance float (may be related to DateTime)
3. add warning pop up for deleting items (incomes and expenses)
*/

//setup
setlocale(LC_MONETARY, 'en_AU');
include_once("connect.php");
include_once("budgetUser.php");
include_once("income.php");
include_once("frequency.php");
include_once("expense.php");
include_once("category.php");
include_once("datePoint.php");
include_once("baseLine.php");
include_once("utilities.php");

//respond to requests, used like a catalog
if (array_key_exists('option', $_REQUEST)) {
  	switch ($_REQUEST['option']) {
  		case "getExpenseList":
  		    print getExpenseList();
  		    break;
  		case "getIncomeList":
  		    print getIncomeList();
  		    break;
  		case "getBudgetReport":
  		    print getBudgetReport();
  		    break;
  		case "deleteIncomeByID":
  		    print deleteIncomeByID();
  		    break;
  		case "addExpense":
  		    print addExpense();
  		    break;
  		case "editExpense":
  		    print editExpense();
  		    break;
  		case "addIncome":
  		    print addIncome();
  		    break;
  		case "editIncome":
  		    print editIncome();
  		    break;
  		case "deleteExpenseByID":
  		    print deleteExpenseByID();
  		    break;
  		case "addNewExpenseDialog":
  		    print addNewExpenseDialog();
  		    break;
  		case "editExpenseDialog":
  		    print editExpenseDialog();
  		    break;
  		case "addNewIncomeDialog":
  		    print addNewIncomeDialog();
  		    break;
  		case "editIncomeDialog":
  		    print editIncomeDialog();
  		    break;
    }
}

//function definitions
function addNewExpenseDialog(){
    $db=new Connect();
    $cats=new Category($db);
    $freqs=new Frequency($db);
    $categoryList=$cats->getCategoryList();
    $frequencyList=$freqs->getFrequencies();
    $catListRender="";
    foreach ($categoryList as $key=>$item) {
        $catListRender.='<option value="'.$key.'">'.$item.'</option>';
    }
    $freListRender="";
    foreach($frequencyList as $key=>$item) {
        $freListRender.='<option value="'.$key.'">'.$item.'</option>';
    }

    $js='
        <script>
            function validate() {
                var result=true;
                if($("#description").val()=="") {
                    $("#descriptionTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#descriptionTitle").css("color","white");
                }

                if($("#amount").val()=="") {
                    $("#amountTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#amountTitle").css("color","white");
                }

                return result;
            };

            $( "#category" ).selectmenu();
            $( "#frequency" ).selectmenu();
            $( "#expenseDate" ).datepicker();
            $( "#expenseDate" ).datepicker("disable");
            
            //recalculate the size of the check box #datePointEnabled, and the datepicker
            $(document).ready(function(){
            	var size=parseFloat($("body").css("font-size"));
            	$("#datePointEnabled").css("width",size*2);
            	$("#datePointEnabled").css("height",size*2);
            	$("#expenseDate").css("font-size",size*0.60);
            	
            	//recalc the size of the drop down menus
            	var tableWidth=parseFloat($("#tableLeftCol").css("width"));
            	$("#category").selectmenu( "option", "width", tableWidth*0.92 );
            	$("#frequency").selectmenu( "option", "width", tableWidth*0.92 );
            	
            });
            
            //toggle the datepicker using the check box
            $("#datePointEnabled").click(function() {
            	if($(this).is(":checked"))
            	{
            		$("#expenseDate").datepicker("enable");
            	}
            	else
            	{
            		$("#expenseDate").datepicker("disable");
            	}
            });
            
            //add style to the input text boxes
            $("input:text").addClass("ui-corner-all");
            $("input:text").css("width","100%");

			//define the dialog buttons
            $("#dialog").dialog("option","buttons", {
                "Ok": function() {
                    if (validate()) {
                        $.ajax(
                        {
                            type: "POST",
                            url: "budgetController.php",
                            data:
                            {
                                option:"addExpense",
                                category:$("#category").val(),
                                frequency:$("#frequency").val(),
                                description:$("#description").val(),
                                amount:$("#amount").val(),
                                datepoint:$("#expenseDate").val(),
                                dateEnabled:$("#datePointEnabled").is(":checked")
                            }
                        })
                        .done(function()
                        {
                            //refresh dynamically, the expenses tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getExpenseList"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the expenses tab
                                $("#tabs-1").html(e);
                            });

                            //refresh dynamically, the report tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getBudgetReport"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the report tab
                                $("#tabs-3").html(e);
                            });




                        });
                        $(this).dialog("close");
                    }
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            });

        </script>
    ';

	

    $output='
        <form id="addNewExpenseForm" method="POST">
        <table class="dialogLayout">
        <tr>
        <td id="tableLeftCol" class="twocol">
        <h2>Category</h2><select id="category">'.$catListRender.'</select>
        <h2>Frequency</h2><select id="frequency">'.$freListRender.'</select>
        </td>
        
        <td class="twocol">
    	<h2>Date Point    <input type="checkbox" id="datePointEnabled" /></h2>
    	<div id="expenseDate"></div>
        </td>
        </tr>
        </table>
        <h2 id="descriptionTitle">Description</h2><input id="description" type="text" />
        <h2 id="amountTitle">Amount</h3><input id="amount" type="text" />
        </form>


    ';


    return $output.$js;
}

function addNewIncomeDialog(){
    $db=new Connect();
    $users=new BudgetUser($db);
    $freqs=new Frequency($db);
    $userList=$users->getUsernameLookup();
    $frequencyList=$freqs->getFrequencies();
    $userListRender="";
    foreach ($userList as $key=>$item) {
        $userListRender.='<option value="'.$key.'">'.$item['name'].'</option>';
    }
    $freListRender="";
    foreach($frequencyList as $key=>$item) {
        $freListRender.='<option value="'.$key.'">'.$item.'</option>';
    }

    $js='
        <script>
            function validate() {
                var result=true;
                if($("#description").val()=="") {
                    $("#descriptionTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#descriptionTitle").css("color","white");
                }

                if($("#amount").val()=="") {
                    $("#amountTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#amountTitle").css("color","white");
                }

                return result;
            };

			$( "#name" ).selectmenu();
            $( "#frequency" ).selectmenu();
            $( "#incomeDate" ).datepicker();
            $( "#incomeDate" ).datepicker("disable");
            
            //recalculate the size of the check box #datePointEnabled, and the datepicker
            $(document).ready(function(){
            	var size=parseFloat($("body").css("font-size"));
            	$("#datePointEnabled").css("width",size*2);
            	$("#datePointEnabled").css("height",size*2);
            	$("#incomeDate").css("font-size",size*0.60);
            	
            	//recalc the size of the drop down menus
            	var tableWidth=parseFloat($("#tableLeftCol").css("width"));
            	$("#name").selectmenu( "option", "width", tableWidth*0.92 );
            	$("#frequency").selectmenu( "option", "width", tableWidth*0.92 );
            	
            });
            
            //toggle the datepicker using the check box
            $("#datePointEnabled").click(function() {
            	if($(this).is(":checked"))
            	{
            		$("#incomeDate").datepicker("enable");
            	}
            	else
            	{
            		$("#incomeDate").datepicker("disable");
            	}
            });

            //add style to the input text boxes
            $("input:text").addClass("ui-corner-all");
            $("input:text").css("width","100%");

            $("#dialog").dialog("option","buttons", {
                "Ok": function() {
                    if (validate()) {
                        $.ajax(
                        {
                            type: "POST",
                            url: "budgetController.php",
                            data:
                            {
                                option:"addIncome",
                                name:$("#name").val(),
                                frequency:$("#frequency").val(),
                                description:$("#description").val(),
                                amount:$("#amount").val(),
                                datepoint:$("#expenseDate").val(),
                                dateEnabled:$("#datePointEnabled").is(":checked")
                            }
                        })
                        .done(function()
                        {
                            //refresh dynamically, the incomes tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getIncomeList"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the expenses tab
                                $("#tabs-2").html(e);
                            });

                            //refresh dynamically, the report tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getBudgetReport"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the report tab
                                $("#tabs-3").html(e);
                            });
                        });
                        $(this).dialog("close");
                    }
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            });

        </script>
    ';

    $output='
    	<form id="addNewIncomeForm" method="POST">
        <table class="dialogLayout">
        <tr>
        <td id="tableLeftCol" class="twocol">
        
        <h2>Name</h2><select id="name">'.$userListRender.'</select>
        <h2>Frequency</h2><select id="frequency">'.$freListRender.'</select>
        </td>
        <td class="twocol">
    	<h2>Date Point    <input type="checkbox" id="datePointEnabled" /></h2>
    	<div id="incomeDate"></div>
        </td>
        </tr>
        </table>
        
        <h2 id="descriptionTitle">Description</h2><input id="description" type="text" />
        <h2 id="amountTitle">Amount</h3><input id="amount" type="text" />
        </form>
    	
        
    ';
    return $output.$js;
}

function editExpenseDialog() {
    $id=htmlentities($_REQUEST['id'], ENT_QUOTES);
    $db=new Connect();
    $cats=new Category($db);
    $freqs=new Frequency($db);
    $exp=new Expense($db);
    $exp->select($id);

	$datepoint='';
	$dateChecked=$exp->get_datePoint()==''?'':'checked';
	if($dateChecked=='checked')
	{
		$datepoint=switch_date_back($exp->get_datePoint());
	}
    $categoryList=$cats->getCategoryList();
    $frequencyList=$freqs->getFrequencies();
    $catListRender="";
    foreach ($categoryList as $key=>$item) {
        if ($key==$exp->get_categoryID()) {
            $catListRender.='<option selected="selected" value="'.$key.'">'.$item.'</option>';
        }
        else {
            $catListRender.='<option value="'.$key.'">'.$item.'</option>';
        }
    }
    $freListRender="";
    foreach($frequencyList as $key=>$item) {
        if ($key==$exp->get_frequencyID()) {
            $freListRender.='<option selected="selected" value="'.$key.'">'.$item.'</option>';
        }
        else {
            $freListRender.='<option value="'.$key.'">'.$item.'</option>';
        }
    }

    $js='
        <script>
            function validate() {
                var result=true;
                if($("#description").val()=="") {
                    $("#descriptionTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#descriptionTitle").css("color","white");
                }

                if($("#amount").val()=="") {
                    $("#amountTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#amountTitle").css("color","white");
                }

                return result;
            };

            $( "#category" ).selectmenu();
            $( "#frequency" ).selectmenu();
            $( "#expenseDate" ).datepicker();
            $( "#expenseDate" ).datepicker("disable");
            
            //recalculate the size of the check box #datePointEnabled, and the datepicker
            $(document).ready(function(){
            	var size=parseFloat($("body").css("font-size"));
            	$("#datePointEnabled").css("width",size*2);
            	$("#datePointEnabled").css("height",size*2);
            	$("#expenseDate").css("font-size",size*0.60);
            	
            	//recalc the size of the drop down menus
            	var tableWidth=parseFloat($("#tableLeftCol").css("width"));
            	$("#category").selectmenu( "option", "width", tableWidth*0.92 );
            	$("#frequency").selectmenu( "option", "width", tableWidth*0.92 );
            	
            	//switch on the date picker, if it should be on
            	if ($("#datePointEnabled").is(":checked"))
            	{
					$( "#expenseDate" ).datepicker("enable");
					$( "#expenseDate" ).datepicker("setDate","'.$datepoint.'");
            	}
            });
            
            //toggle the datepicker using the check box
            $("#datePointEnabled").click(function() {
            	if($(this).is(":checked"))
            	{
            		$("#expenseDate").datepicker("enable");
            	}
            	else
            	{
            		$("#expenseDate").datepicker("disable");
            	}
            });
            
            $("input:text").addClass("ui-corner-all");
            $("input:text").css("width","630px");

            $("#dialog").dialog("option","buttons", {
                "Ok": function() {
                    if (validate()) {
                        var rowSelected=$(".selected").attr("id");
                        $.ajax(
                        {
                            type: "POST",
                            url: "budgetController.php",
                            data:
                            {
                                option:"editExpense",
                                id:rowSelected,
                                category:$("#category").val(),
                                frequency:$("#frequency").val(),
                                description:$("#description").val(),
                                amount:$("#amount").val(),
                                datepoint:$("#expenseDate").val(),
                                dateEnabled:$("#datePointEnabled").is(":checked")
                            }
                        })
                        .done(function()
                        {
                            //refresh dynamically, the expenses tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getExpenseList"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the expenses tab
                                $("#tabs-1").html(e);
                            });

                            //refresh dynamically, the report tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getBudgetReport"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the report tab
                                $("#tabs-3").html(e);
                            });




                        });
                        $(this).dialog("close");
                    }
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            });

        </script>
    ';

    $output='
        <form id="addNewExpenseForm" method="POST">
        <table class="dialogLayout">
        <tr>
        <td id="tableLeftCol" class="twocol">
        <h2>Category</h2><select id="category">'.$catListRender.'</select>
        <h2>Frequency</h2><select id="frequency">'.$freListRender.'</select>
        </td>
        
        <td class="twocol">
    	<h2>Date Point    <input type="checkbox" id="datePointEnabled" '.$dateChecked.' /></h2>
    	<div id="expenseDate"></div>
        </td>
        </tr>
        </table>
        <h2 id="descriptionTitle">Description</h2><input id="description" type="text" value="'.$exp->get_description().'" />
        <h2 id="amountTitle">Amount</h3><input id="amount" type="text" value="'.($exp->get_amount()/100).'" />
        </form>


    ';


    return $output.$js;
}
function editIncomeDialog() {
    $id=htmlentities($_REQUEST['id'], ENT_QUOTES);
    $db=new Connect();
    $users=new BudgetUser($db);
    $freqs=new Frequency($db);
    $inc=new Income($db);
    $inc->select($id);
	
	$datepoint='';
	$dateChecked=$inc->get_datePoint()==''?'':'checked';
	if($dateChecked=='checked')
	{
		$datepoint=switch_date_back($inc->get_datePoint());
	}
	
    $userList=$users->getUsernameLookup();
    $frequencyList=$freqs->getFrequencies();
    $userListRender="";
    foreach ($userList as $key=>$item) {
        if ($key==$inc->get_username()) {
            $userListRender.='<option selected="selected" value="'.$key.'">'.$item['name'].'</option>';
        }
        else {
            $userListRender.='<option value="'.$key.'">'.$item['name'].'</option>';
        }
    }
    $freListRender="";
    foreach($frequencyList as $key=>$item) {
        if ($key==$inc->get_frequencyID()) {
            $freListRender.='<option selected="selected" value="'.$key.'">'.$item.'</option>';
        }
        else {
            $freListRender.='<option value="'.$key.'">'.$item.'</option>';
        }
    }

    $js='
        <script>
            function validate() {
                var result=true;
                if($("#description").val()=="") {
                    $("#descriptionTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#descriptionTitle").css("color","white");
                }

                if($("#amount").val()=="") {
                    $("#amountTitle").css("color","red");
                    result=false;
                }
                else {
                    $("#amountTitle").css("color","white");
                }

                return result;
            };

            $( "#name" ).selectmenu();
            $( "#frequency" ).selectmenu();
            $( "#incomeDate" ).datepicker();
            $( "#incomeDate" ).datepicker("disable");
            
			//recalculate the size of the check box #datePointEnabled, and the datepicker
            $(document).ready(function(){
            	var size=parseFloat($("body").css("font-size"));
            	$("#datePointEnabled").css("width",size*2);
            	$("#datePointEnabled").css("height",size*2);
            	$("#incomeDate").css("font-size",size*0.60);
            	
            	//recalc the size of the drop down menus
            	var tableWidth=parseFloat($("#tableLeftCol").css("width"));
            	$("#name").selectmenu( "option", "width", tableWidth*0.92 );
            	$("#frequency").selectmenu( "option", "width", tableWidth*0.92 );
            	
            	//switch on the date picker, if it should be on
            	if ($("#datePointEnabled").is(":checked"))
            	{
					$( "#incomeDate" ).datepicker("enable");
					$( "#incomeDate" ).datepicker("setDate","'.$datepoint.'");
            	}
            });
            
            //toggle the datepicker using the check box
            $("#datePointEnabled").click(function() {
            	if($(this).is(":checked"))
            	{
            		$("#incomeDate").datepicker("enable");
            	}
            	else
            	{
            		$("#incomeDate").datepicker("disable");
            	}
            });
            
            $("input:text").addClass("ui-corner-all");
            $("input:text").css("width","630px");

            $("#dialog").dialog("option","buttons", {
                "Ok": function() {
                    if (validate()) {
                        var rowSelected=$(".selected").attr("id");
                        $.ajax(
                        {
                            type: "POST",
                            url: "budgetController.php",
                            data:
                            {
                                option:"editIncome",
                                id:rowSelected,
                                name:$("#name").val(),
                                frequency:$("#frequency").val(),
                                description:$("#description").val(),
                                amount:$("#amount").val(),
                                datepoint:$("#incomeDate").val(),
                                dateEnabled:$("#datePointEnabled").is(":checked")
                            }
                        })
                        .done(function()
                        {
                            //refresh dynamically, the incomes tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getIncomeList"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the expenses tab
                                $("#tabs-2").html(e);
                            });

                            //refresh dynamically, the report tab
                            $.ajax(
                            {
                                type: "POST",
                                url: "budgetController.php",
                                data:
                                {
                                    option:"getBudgetReport"
                                }
                            })
                            .done(function(e)
                            {
                                //refresh dynamically, the report tab
                                $("#tabs-3").html(e);
                            });
                        });
                        $(this).dialog("close");
                    }
                },
                "Cancel": function() {
                    $(this).dialog("close");
                }
            });
        </script>
    ';

    $output='
    	<form id="addNewIncomeForm" method="POST">
        <table class="dialogLayout">
        <tr>
        <td id="tableLeftCol" class="twocol">
        
        <h2>Name</h2><select id="name">'.$userListRender.'</select>
        <h2>Frequency</h2><select id="frequency">'.$freListRender.'</select>
        </td>
        <td class="twocol">
    	<h2>Date Point    <input type="checkbox" id="datePointEnabled" '.$dateChecked.' /></h2>
    	<div id="incomeDate"></div>
        </td>
        </tr>
        </table>
        
        <h2 id="descriptionTitle">Description</h2><input id="description" type="text" value="'.$inc->get_description().'" />
        <h2 id="amountTitle">Amount</h3><input id="amount" type="text" value="'.($inc->get_amount()/100).'" />
        </form>
    ';
    return $output.$js;
}

function addExpense() {
    $amount=round(htmlentities($_REQUEST['amount'], ENT_QUOTES)*100);
    $category=htmlentities($_REQUEST['category'], ENT_QUOTES);
    $description=htmlentities($_REQUEST['description'], ENT_QUOTES);
    $frequency=htmlentities($_REQUEST['frequency'], ENT_QUOTES);
    $dateEnabled=htmlentities($_REQUEST['dateEnabled'], ENT_QUOTES);
    $datepoint=($dateEnabled=='true'?htmlentities($_REQUEST['datepoint'], ENT_QUOTES):'');
    $db=new Connect();
    $expenses=new Expense($db);
    $expenses->quickInsert($description, $frequency, $amount, $category, $datepoint);
}
function addIncome() {
    $amount=round(htmlentities($_REQUEST['amount'], ENT_QUOTES)*100);
    $username=htmlentities($_REQUEST['name'], ENT_QUOTES);
    $description=htmlentities($_REQUEST['description'], ENT_QUOTES);
    $frequency=htmlentities($_REQUEST['frequency'], ENT_QUOTES);
    $dateEnabled=htmlentities($_REQUEST['dateEnabled'], ENT_QUOTES);
    $datepoint=($dateEnabled=='true'?htmlentities($_REQUEST['datepoint'], ENT_QUOTES):'');
    $db=new Connect();
    $incomes=new Income($db);
    $incomes->quickInsert($username, $frequency, $amount, $description, $datepoint);
}
function editExpense() {
    $id=htmlentities($_REQUEST['id'], ENT_QUOTES);
    $amount=round(htmlentities($_REQUEST['amount'], ENT_QUOTES)*100);
    $category=htmlentities($_REQUEST['category'], ENT_QUOTES);
    $description=htmlentities($_REQUEST['description'], ENT_QUOTES);
    $frequency=htmlentities($_REQUEST['frequency'], ENT_QUOTES);
    $dateEnabled=htmlentities($_REQUEST['dateEnabled'], ENT_QUOTES);
    $datepoint=($dateEnabled=='true'?htmlentities($_REQUEST['datepoint'], ENT_QUOTES):'');
    $db=new Connect();
    $expenses=new Expense($db);
    $expenses->select($id);
    $expenses->set_amount($amount);
    $expenses->set_categoryID($category);
    $expenses->set_description($description);
    $expenses->set_frequencyID($frequency);
    $expenses->set_datePoint($datepoint);
    $expenses->update();
}
function editIncome() {
    $id=htmlentities($_REQUEST['id'], ENT_QUOTES);
    $amount=round(htmlentities($_REQUEST['amount'], ENT_QUOTES)*100);
    $name=htmlentities($_REQUEST['name'], ENT_QUOTES);
    $description=htmlentities($_REQUEST['description'], ENT_QUOTES);
    $frequency=htmlentities($_REQUEST['frequency'], ENT_QUOTES);
    $dateEnabled=htmlentities($_REQUEST['dateEnabled'], ENT_QUOTES);
    $datepoint=($dateEnabled=='true'?htmlentities($_REQUEST['datepoint'], ENT_QUOTES):'');
    $db=new Connect();
    $incomes=new Income($db);
    $incomes->select($id);
    $incomes->set_amount($amount);
    $incomes->set_username($name);
    $incomes->set_description($description);
    $incomes->set_frequencyID($frequency);
    $incomes->set_datePoint($datepoint);
    $incomes->update();
}


function deleteExpenseByID() {
    $id=htmlentities($_REQUEST['id'], ENT_QUOTES);
    $db=new Connect();
    $expenses=new Expense($db);
    $expenses->delete($id);
}

function deleteIncomeByID() {
    $id=htmlentities($_REQUEST['id'], ENT_QUOTES);
    $db=new Connect();
    $incomes=new Income($db);
    $incomes->delete($id);
}

//returns a renderable expense list, in table format
function getExpenseList() {
    //initialise
    $db=new Connect();
    $expenses=new Expense($db);
    $list=$expenses->getExpenseList();

    //define the JS
    $js="
    <script>
    $(document).ready(function() {
        //define the data table
        var table=$('#expenses').DataTable({
            paging: false,
            searching: false
        });

        $( '#dialog' ).dialog({
            autoOpen: false,
            width: 700,
            buttons: [
                {
                    text: 'Ok',
                    click: function() {
                        $( this ).dialog( 'close' );
                    }
                },
                {
                    text: 'Cancel',
                    click: function() {
                        $( this ).dialog( 'close' );
                    }
                }
            ]
        });

        //define the delete button as a jquery ui button
        $( '#expenseDeleteButton' ).button();
        $( '#addExpenseButton' ).button();
        $( '#editExpenseButton' ).button();

        //toggle selected row
        $('#expenses tbody').on( 'click', 'tr', function ()
        {
            if ( $(this).hasClass('selected') )
            {
                $(this).removeClass('selected');
            }
            else
            {
                table.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        //handle the delete button
		$('#expenseDeleteButton').click( function ()
		{
		    var rowSelected=$('.selected').attr('id');
		    if(typeof rowSelected != 'undefined'){
                $.ajax(
                {
                    type: 'POST',
                    url: 'budgetController.php',
                    data: {option:'deleteExpenseByID',id:rowSelected}
                })
                .done(function()
                {
                    table.row('.selected').remove().draw( false );
                    //refresh dynamically, the report tab
                    $.ajax(
                    {
                        type: 'POST',
                        url: 'budgetController.php',
                        data:
                        {
                            option:'getBudgetReport'
                        }
                    })
                    .done(function(e)
                    {
                        //refresh dynamically, the report tab
                        $('#tabs-3').html(e);
                    });
                });
            }
        });

        //handle the add button
        $('#addExpenseButton').click(function(event) {

            //put the correct text in the dialog box
            $.ajax(
            {
                type: 'POST',
                url: 'budgetController.php',
                data: {option:'addNewExpenseDialog'}
            })
            .done(function(e)
            {
                $('#dialog').html(e);
            });
            $('#dialog').dialog('option','title','Add New Expense');

            $('#dialog').dialog('open');
        });

        //handle the edit button
        $('#editExpenseButton').click(function(event) {

            var rowSelected=$('.selected').attr('id');
            if(typeof rowSelected != 'undefined'){
                //put the correct text in the dialog box
                $.ajax(
                {
                    type: 'POST',
                    url: 'budgetController.php',
                    data: {option:'editExpenseDialog', id:rowSelected}
                })
                .done(function(e)
                {
                    $('#dialog').html(e);
                });
                $('#dialog').dialog('option','title','Edit Expense');

                $('#dialog').dialog('open');
            }
        });
    });
    </script>";

    //define the start of the table
    $output="<button id='expenseDeleteButton'>Delete</button>";
    $output.="<button id='addExpenseButton'>Add</button>";
    $output.="<button id='editExpenseButton'>Edit</button>";
    $output.="<table id='expenses' class='display' cellspacing='0' width='100%'>";
    $output.="<thead><tr><th>Category</th><th>Description</th><th>Amount</th><th>Frequency</th></tr></thead>";
    $output.="<tbody>";

    //iterate over the expenses and create the bulk of the table
    foreach ($list as $row) {
        $output.="<tr id='".$row['expenseID']."'></td><td>".$row['category']."</td><td>".$row['description']."</td><td>"
        .toDollars($row['amount'])."</td><td>".$row['frequency']."</td></tr>";
    }
    $output.="</tbody>";
    $output.="</table>";
    return $output.$js;
}

function getIncomeList() {
    //initialise
    $db=new Connect();
    $incomes=new Income($db);
    $list=$incomes->getDetailedIncomeList();

    //define the JS
    $js="
    <script>
    $(document).ready(function() {
        //define the data table
        var table=$('#incomes').DataTable({
            paging: false,
            searching: false
        });

        $( '#dialog' ).dialog({
            autoOpen: false,
            width: 700,
            buttons: [
                {
                    text: 'Ok',
                    click: function() {
                        $( this ).dialog( 'close' );
                    }
                },
                {
                    text: 'Cancel',
                    click: function() {
                        $( this ).dialog( 'close' );
                    }
                }
            ]
        });

        //define the delete button as a jquery ui button
        $( '#incomeDeleteButton' ).button();
        $( '#addIncomeButton' ).button();
        $( '#editIncomeButton' ).button();

        //toggle selected row
        $('#incomes tbody').on( 'click', 'tr', function ()
        {
            if ( $(this).hasClass('selected') )
            {
                $(this).removeClass('selected');
            }
            else
            {
                table.$('tr.selected').removeClass('selected');
                $(this).addClass('selected');
            }
        });

        //handle the delete button
		$('#incomeDeleteButton').click( function ()
		{
		    var rowSelected=$('.selected').attr('id');
		    if(typeof rowSelected != 'undefined'){
                $.ajax(
                {
                    type: 'POST',
                    url: 'budgetController.php',
                    data: {option:'deleteIncomeByID',id:rowSelected}
                })
                .done(function()
                {
                    table.row('.selected').remove().draw( false );
                    //refresh dynamically, the report tab
                    $.ajax(
                    {
                        type: 'POST',
                        url: 'budgetController.php',
                        data:
                        {
                            option:'getBudgetReport'
                        }
                    })
                    .done(function(e)
                    {
                        //refresh dynamically, the report tab
                        $('#tabs-3').html(e);
                    });
                });
            }
        });

        //handle the add button
        $('#addIncomeButton').click(function(event) {

            //put the correct text in the dialog box
            $.ajax(
            {
                type: 'POST',
                url: 'budgetController.php',
                data: {option:'addNewIncomeDialog'}
            })
            .done(function(e)
            {
                $('#dialog').html(e);
            });
            $('#dialog').dialog('option','title','Add New Income');

            $('#dialog').dialog('open');
        });

        //handle the edit button
        $('#editIncomeButton').click(function(event) {

            var rowSelected=$('.selected').attr('id');
            if(typeof rowSelected != 'undefined'){
                //put the correct text in the dialog box
                $.ajax(
                {
                    type: 'POST',
                    url: 'budgetController.php',
                    data: {option:'editIncomeDialog', id:rowSelected}
                })
                .done(function(e)
                {
                    $('#dialog').html(e);
                });
                $('#dialog').dialog('option','title','Edit Income');

                $('#dialog').dialog('open');
            }
        });
    });
    </script>";

    //define the start of the table
    //$output="<div><input type='button' id='deleteButton' value='delete'/></div>";
    $output="<button id='incomeDeleteButton'>Delete</button>";
    $output.="<button id='addIncomeButton'>Add</button>";
    $output.="<button id='editIncomeButton'>Edit</button>";
    $output.="<table id='incomes' class='display' cellspacing='0' width='100%'>";
    $output.="<thead><tr><th>Name</th><th>Description</th><th>Amount</th><th>Frequency</th></tr></thead>";
    $output.="<tbody>";
    //iterate over the expenses and create the bulk of the table
    foreach ($list as $row) {
        $output.="<tr id='".$row['incomeID']."'><td>".$row['name']."</td><td>".$row['description']."</td><td>".toDollars($row['amount'])."</td><td>".$row['frequency']."</td></tr>";
    }
    $output.="</tbody>";
    $output.="</table>";
    return $output.$js;
}

function getUserPercentageIncomeSplitList() {
    $db=new Connect();
    $users=new BudgetUser($db);
	$yearFreq=new Frequency($db);
	$yearFreq->selectByDesc('year');
    $userList=$users->getUsernameList();
    $income=new Income($db);
    $total=0;
    $result=array();
    foreach ($userList as $user){
        $total+=$income->getTotalUserIncome($yearFreq, $user);
        $result[$user]=0;
    }
    foreach ($userList as $user) {
        $result[$user]=$income->getTotalUserIncome($yearFreq,$user)/$total;
    }
    return $result;
}

function getUserIncomeFrequencyList() {
    $db=new Connect();
    $users=new BudgetUser($db);
    $userList=$users->getUsernameList();
    $results=array();
    foreach($userList as $user) {
        $users->select($user);
		$results[$user]=$users->getUserIncomeFrequency();
    }
    return $results;
}


function getUserIncomeDatePointList() {
	$db=new Connect();
	$users=new BudgetUser($db);
    $userList=$users->getUsernameList();
	$results=array();
    foreach($userList as $user) {
        $users->select($user);
		$results[$user]=$users->getUserIncomeDatePoint();
    }
    return $results;
}

function getUserPayIntoJointList() {
    //potentially closing mysql connections calls here
    $inputPCs=getUserPercentageIncomeSplitList();
    $inputFrequencies=getUserIncomeFrequencyList();
	$datePointsList=getUserIncomeDatePointList();
    
	//new db connections
    $db=new Connect();
    $expenses=new Expense($db);
    $budgetUser=new BudgetUser($db);

    $results=array();
    $inputAmounts=array();

    $userList=$budgetUser->getUsernameList();

    foreach ($userList as $user) {
		$tmpExpenses=$expenses->getTotalExpenses($inputFrequencies[$user]);
        $tmpAmount=(int)($tmpExpenses*$inputPCs[$user]);
		$results[$user]=array('frequency'=>$inputFrequencies[$user], 'amount'=>$tmpAmount, 'datePoint'=>$datePointsList[$user]);
    }
    return $results;
}

function getUserLeavesPerPayList() {
    $input=getUserPayIntoJointList();
    $result=array();

    $db=new Connect();
    $users=new BudgetUser($db);
    $income=new Income($db);
    $userList=$users->getUsernameList();

    foreach ($userList as $user) {
        $result[$user]['amount']=$income->getTotalUserIncome($input[$user]['frequency'],$user)-$input[$user]['amount'];
        $result[$user]['frequency']=$input[$user]['frequency'];
    }
    return $result;
}

function getUserTotalIncomesList(Frequency $frequency) {
    $db=new Connect();
    $income=new Income($db);
    $users=new BudgetUser($db);
    $result=array();

    $userList=$users->getUsernameList();
    foreach ($userList as $user) {
        $result[$user]=$income->getTotalUserIncome($frequency,$user);
    }
    return $result;
}

function getBudgetReport() {
    $db=new Connect();
	$yearFreq=new Frequency($db,'year');
	$monthFreq=new Frequency($db, 'month');
	
	$percentages=getUserPercentageIncomeSplitList();
    $payIntoJoint=getUserPayIntoJointList();
    $leavesPerPay=getUserLeavesPerPayList();
	$totalIncomes=getUserTotalIncomesList($yearFreq);

    $users=new budgetUser($db);
    $incomes=new Income($db);
    $expense=new Expense($db);
    $baseLine=new BaseLine($db);
    $userList=$users->getUsernameLookup();
	
	$totalIncome=$incomes->getTotalIncome($yearFreq);
	$totalExpense=$expense->getTotalExpenses($yearFreq);
    $totalIncomeMonth=$incomes->getTotalIncome($monthFreq);
	$totalExpenseMonth=$expense->getTotalExpenses($monthFreq);
    
    //populate the baseline, then generate it
    $baselineExpenseList=$expense->getBaseLineExpenseList();
    $baselineIncomeList=$incomes->getBaseLineIncomeList();
    $requiredFloatToday='';
    $couldGenerateBaseline=true;
    foreach ($baselineExpenseList as $baselineExpenseItem) {
		$tmpExp=new DatePoint($db,$baselineExpenseItem['expenseID'],'expense',$baselineExpenseItem['amount'],$baselineExpenseItem['datePoint'],$baselineExpenseItem['frequencyID']);
		if ($baselineExpenseItem['datePoint']=='NULL') {
			$couldGenerateBaseline=false;
			break;
		}
		$baseLine->add($tmpExp);
    }
    
	if ($couldGenerateBaseline) {
		foreach ($userList as $user=>$names) {
			$tmpInc=new DatePoint($db,0,'income',$payIntoJoint[$user]['amount'],$payIntoJoint[$user]['datePoint'],$payIntoJoint[$user]['frequency']->get_frequencyID());
			$baseLine->add($tmpInc);
		}
    }
    if ($couldGenerateBaseline) {
		$baseLine->generateBaseline();
		$requiredFloatToday=$baseLine->getFloatNeededToday();
    }
    else {
    	$requiredFloatToday='';
    }
    $floatText=($requiredFloatToday=='')?'Error. Not all incomes or expenses have date points':toDollars($requiredFloatToday);

    //define the JS
    $js="
    <script>
    </script>";
    $output="";
    foreach ($userList as $user=>$names) {
        $output.="<h1>".$names['name']."'s budget</h1>";
        $tmpIncome=getUserTotalIncomesList($payIntoJoint[$user]['frequency']);
        $output.="<p>".$names['firstname']." earns a total of ".toDollars($tmpIncome[$user]).", every ".$payIntoJoint[$user]['frequency']->get_description().".</p>";
        $output.="<p>".$names['firstname']."'s total annual income is ".toDollars($totalIncomes[$user])."</p>";
        $output.="<p>".$names['firstname']."'s income percentage is ". pc($percentages[$user])."</p>";
        $output.="<p>".$names['firstname']." pays ".toDollars($payIntoJoint[$user]['amount'])." into the joint account every ". $payIntoJoint[$user]['frequency']->get_description()."</p>";
        $output.="<p>".$names['firstname']." has ".toDollars($leavesPerPay[$user]['amount']).", left over every ".$payIntoJoint[$user]['frequency']->get_description()."</p>";
    }

    //print the totals
    $output.= "<h1>Totals:</h1>";
    $output.= "<p>"."Total annual budget income, ".toDollars($totalIncome)."</p>";
    $output.= "<p>"."Total annual budget expenses, ".toDollars($totalExpense)."</p>";
    $output.= "<p>"."Total annual remaining: ".toDollars($totalIncome-$totalExpense)."</p>";
    $output.= "<p>"."Total monthly budget income, ".toDollars($totalIncomeMonth)."</p>";
    $output.= "<p>"."Total monthly budget expenses, ".toDollars($totalExpenseMonth)."</p>";
    $output.= "<p>"."Total monthly remaining: ".toDollars($totalIncomeMonth-$totalExpenseMonth)."</p>";
    $output.= "<p>"."Budget Account balance should be today: ".$floatText."</p>";
	
	#$data=$baseLine->generateBalancePlotData();
	#$output.="<p><table>";
	#foreach ($data as $item) {
	#	$output.="<tr><td>".$item['date']."</td><td>".$item['balance']."</td></tr>";
	#}
	#$output.="</table></p>";
	
	if ($couldGenerateBaseline) {
		$output.='<h1>Fortnightly Forecast</h1>';
		$frequency=new Frequency($db, 'fortnight');
		$data=$baseLine->getPeriodLimitedBaseLineList($frequency);
		$result=Array();
		
		foreach ($data as $s) {
			$singleArray=Array();
			if ($s['amountType']=='expense') {
				$expense->select($s['itemID']);
				$singleArray['Description']=$expense->get_description();
			}
			else {
				$incomes->select($s['itemID']);
				$singleArray['Description']='Pay';
			}
			$singleArray['Amount']=$s['amount'];
			$singleArray['Date']=$s['dateObj']->format('d-m-Y');
			$result[]=$singleArray;
		}
		//print_r($result);
		$output.="<p><table class='report'>";
		$output.="<tr><th>Date</th><th>Amount</th><th>Description</th></tr>";
		foreach($result as $item) {
			$output.="<tr class='report_row'><td>".$item['Date']."</td><td class='amount'>".toDollars($item['Amount'])."</td><td class='description'>".$item['Description']."</td></tr>";
		}
		$output.="</table></p>";
		
	}
	return $output.$js;
}




?>

















