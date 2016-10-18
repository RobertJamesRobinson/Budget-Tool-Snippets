<?php
date_default_timezone_set('Australia/Victoria');

include_once("budgetController.php");


//setup the page properly, with css binding as well as javascript bindings
$output='
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
	<title>Roberts Budgeting System</title>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />

    <link type="text/css" href="js/jquery-ui/jquery-ui.css" rel="stylesheet" />


	<link type="text/css" href="//cdn.datatables.net/plug-ins/380cb78f450/integration/jqueryui/dataTables.jqueryui.css" rel="stylesheet" />


	<link type="text/css" href="js/custom.css" rel="stylesheet" />

	<script type="text/javascript" src="js/jquery-2.1.1.js"></script>
	<script type="text/javascript" src="js/jquery-ui/jquery-ui.js"></script>
	<script type="text/javascript" language="javascript" src="//cdn.datatables.net/1.10.3/js/jquery.dataTables.min.js"></script>


	<script type="text/javascript" src="//cdn.datatables.net/plug-ins/380cb78f450/integration/jqueryui/dataTables.jqueryui.js"></script>


    <script>
    $(document).ready(function() {
        var width=$( window ).width()-10;
        var height=$( window ).height()-10;
        var textSize=Math.floor(width/34.65);
        if(textSize>27) {
            textSize=27;
        }
        
        //remove me to make text big again
        //textSize=12;
        
        $("body").css("width", width+"px");
        $("body").css("height", height+"px");
        $("body").css("font-size", textSize+"px");
        $( "#tabs" ).tabs();

        //unselect anything that is selected when clicking on a new tab
        $( "a[id^=\'ui-id\']" ).click( function ()
        {
             $(".selected").removeClass("selected");
        });
    });
    </script>

</head>
<body>

';

$output.='

<div id="tabs">
	<ul>
		<li><a href="#tabs-1">Expenses</a></li>
		<li><a href="#tabs-2">Incomes</a></li>
		<li><a href="#tabs-3">Report</a></li>
	</ul>
	<div id="tabs-1">'.getExpenseList().'</div>
	<div id="tabs-2">'.getIncomeList().'</div>
	<div id="tabs-3">'.getBudgetReport().'</div>
</div>
<div id="dialog" title="Dialog Title"></div>';
$output.='

</body>
</html>';
print $output;
?>