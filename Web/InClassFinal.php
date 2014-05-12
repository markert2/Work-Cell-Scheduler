<?php
require_once 'Work-Cell-Scheduler/WCS/os.php';

function ContainsString($needle,$haystack){
	if (strpos($haystack,$needle)===FALSE){
		return FALSE;
	}
	return TRUE;
}

function assertEquals($expected,$result) {
	if(!($expected===$result)){
		$message="assertEquasl: |$expected|$result|\n";
		throw new Exception($message);
	}
}

 //Create Arrays
 //key=supplier, value=capacity
 $supplier = array(
 "S1" => 600,
 "S2" => 300,
 "S3" => 200,
 "S4" => 500
 );
 //print_r($supplier);
 
 //Produciton cost.  Define as $pcost
 //key=supplier, value=production cost
 $pcost = array(
 "S1" => 10,
 "S2" => 14,
 "S3" => 40,
 "S4" => 11
 );
 //key=dept, value=demand
 $department = array(
 "D1" => 600,
 "D2" => 200,
 "D3" => 300,
 "D4" => 100,
 "D5" => 300
 );
 //assign profit per sale for each dept
 //key=dept, value=profit/unit sold
 $dprofit = array(
 "D1" => 20,
 "D2" => 30,
 "D3" => 40,
 "D4" => 25,
 "D5" => 25
 );

 //print_r($department);

 $distance = array(
 "S1_D1" => 2,
 "S1_D2" => 3,
 "S1_D3" => 3,
 "S1_D4" => 3,
 "S1_D5" => 3,
 "S2_D1" => 5,
 "S2_D2" => 2,
 "S2_D3" => 4,
 "S2_D4" => 4,
 "S2_D5" => 2,
 "S3_D1" => 3,
 "S3_D2" => 2,
 "S3_D3" => 8,
 "S3_D4" => 2,
 "S3_D5" => 2,
 "S4_D1" => 3,
 "S4_D2" => 2,
 "S4_D3" => 4,
 "S4_D4" => 2,
 "S4_D5" => 2
 );
 //print_r($distance);
 
 

//function SolveTransportation($supplier,$department,$dprofit,$distance){

	$totalcapacity=array_sum($supplier);
	$totaldemand=array_sum($department);
	//print_r($totalsupply);
	//print_r($totaldemand);
	//$trpofit will be an array that holds all OF COefficients
	//$tprofit = (profit/unit sold) - (cost of production) - (cost of transportation) 
	$tprofit=array();
	foreach( $supplier as $key => $value){
		foreach( $dprofit as $k => $v){
			$newkey = "{$key}_{$k}";
			//print_r($newkey);
			$tprofit[$newkey] = $v - $pcost[$key] - $distance[$newkey];
		}
	}
	//print_r($tprofit);

	//Set Decision Vars
	$os=New WebIS\OS;

	$shipping=array();
	foreach ($supplier as $key => $value){
		foreach ($department as $d => $v){
			$newkey="{$key}_{$d}";
			$shipping[$newkey]=0.0;
		}
	}

	//print_r($shipping);

	//OF
	foreach ($tprofit as $key => $value){
		//print_r($key);
		$os->addVariable($key);
		$os->addObjCoef($key,$value);
	}
	//print_r($os);

	//constraints 
	foreach ($supplier as $key => $value){
		$os->addConstraint($value,100);
		foreach ($department as $k=>$v){
			$newkey="{$key}_{$k}";
			//print_r($newkey);
			$os->addConstraintCoef($newkey,1);
		}
	}
	//print_r($os);
	//The constraints below account for the situation where demand is greater than capacity.  
	//Based on classical transportation formulation, 
	if($totaldemand<=$totalcapacity){
	foreach ($department as $key => $value){
		$os->addConstraint(NULL,$value);
		foreach ($supplier as $k => $v){
			$newkey="{$k}_{$key}";
			//print_r($s);
			$os->addConstraintCoef($newkey,1);
		}
	}
}   
	if($totaldemand>$totalcapacity){
		foreach ($department as $key => $value){
			$os->addConstraint($value,NULL);
			foreach ($supplier as $k => $v){
				$newkey="{$k}_{$key}";
				//print_r($s);
				$os->addConstraintCoef($newkey,1);
			}
		}
	}
	
	$os->solve();
	//print_r($os);
	$solutions=$os->value;
	$objval=(double)$os->osrl->optimization->solution->objectives->values->obj;
	
	//print_r($solutions);
	//print_r($objval);
	//Tables
	
	//Sum assignments over suppliers and then over departments

	$totalproduced=array();
	foreach ($supplier as $key=>$value){
		foreach($department as $k=>$v){
			$newkey="{$key}_{$k}";
			//print_r($newkey);
			if (ContainsString($key,$newkey)){
				$val=$solutions[$newkey];
				$totalproduced[$key]=$totalproduced[$key]+$val;
			}
		}
	}
	//print_r($totalproduced);
	
	$totalsold=array();
	foreach ($department as $key=>$value){
		foreach($supplier as $k=>$v){
			$newkey="{$k}_{$key}";
			//print_r($newkey);
			if (ContainsString($key,$newkey)){
				$val=$solutions[$newkey];
				$totalsold[$key]=$totalsold[$key]+$val;
			}
		}
	}
	 //print_r($totalsold);
	
	
echo "<html><h2>Lance Markert, LRMR47</h2>";
if($totaldemand>$totalcapacity){
	echo "<h3><font color='red'>Problem and Solution Shown Below:<br>Warning: Not all demand can be satisfied</font></h3>";
}   else
{echo "<h3>Problem and Solution Shown Below</h3>";
}
	
//Table for suppliers
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Suppliers</th>
	  <th>Capacity</th></tr>";
foreach ($supplier as $key=>$value){
	echo "<tr><td><b>$key</td>
		  <td>$value</td></tr>";	
}
echo "</table>";

//Table for departments
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Departments</th>
	  <th>Demand</th>
	  <th>Profit</th></tr>";
foreach ($department as $key=>$value){
	echo "<tr><td><b>$key</td>
	      <td>$value</td>
	      <td>$dprofit[$key]</td></tr>";
}
echo "</table>";
echo "<b>Total Supply=$totalcapacity<br>Total Demand=$totaldemand<br>";
//Table for distances
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Distances</th>";
foreach ($supplier as $key=>$value){
	echo "<th>$key</th>";
}
foreach ($department as $key=>$value){
	echo "<tr><td><b>$k</td>";
	foreach ($supplier as $k=>$v){
		$newkey="${k}_${key}";
		$val=$distance[$newkey];
		$val=round($val,0);
		echo "<td>$val</td>";
	}
echo "</tr>";
}

echo "</table>";

//Table for profit
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Total Profit</th>";
foreach ($supplier as $key=>$value){
	echo "<th>$key</th>";
}
foreach ($department as $key=>$value){
	echo "<tr><td><b>$key</td>";
	foreach ($supplier as $k=>$v){
		$newkey="${k}_${key}";
		$val=$tprofit[$newkey];
		$val=round($val,0);
		echo "<td>$val</td>";
	}
	echo "</tr>";
}
echo "</table>";

echo "<font size='5'><b>Solution:</font>";
//Table for transportation assignments
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Assignments</th>";
foreach ($supplier as $key=>$value){
	echo "<th>$key</th>";
}
foreach ($department as $key=>$value){
	echo "<tr><td><b>$key</td>";
	foreach ($supplier as $k=>$v){
		$newkey="${k}_${key}";
		$assign=$solutions[$newkey];
		$assign=round($assign,0);
		echo "<td>$assign</td>";
	}
	echo "</tr>";
}
echo "</table>";
echo "<font size='5'><b>Objective Function Value = $objval</font>";


//New Table for Production cost per plant
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Cost/Plant</th>
      <th>Production Cost</th></tr>";
foreach ($supplier as $key=>$value){
	//$val=($totalproduced) * ($pcost[$key]);
	echo "<tr><td><b>$key</td>";
}
		
//New Table for Profit per store
echo "<table border='1' cellpadding='10'>";
echo "<tr><th>Cost/Plant</th>
      <th>Production Cost</th></tr>";
foreach ($department as $key=>$value){
	echo "<tr><td><b>$key</td>";
}



echo "</body>";

?> 

