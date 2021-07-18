<!DOCTYPE html>
<html>
<body>
<!--  in directory  php -S 127.0.0.1:4000  -->
<!--  http://127.0.0.1:4000/enter.php?license=teste&state=TX   -->

<?php

function determine_fee($conn, $license, $state, $current_datetime) {
	$fee = array("parking_event_id" => "", "vehicle_id" => "","fee" => "","customer_id" => ""  );
	$sql = "SELECT a.* ,
			CASE WHEN a.fee_exempt=true THEN 0.00 
				 WHEN fee_amount=0 AND fee_amount IS NOT NULL then 0.00
				 WHEN COALESCE(b.is_fee_exempt,false)=true THEN 0.00 
				 ELSE fm.fee END AS fee,
		    COALESCE(customer_id,0) as customer_id
			FROM ParkingEvent a INNER JOIN 
				(
					SELECT max(parking_event_id) as most_recent_parking_event,c.is_fee_exempt,
					       c.customer_id
					FROM Vehicle v INNER JOIN ParkingEvent b ON v.vehicle_id=b.vehicle_id
						LEFT OUTER JOIN  Customer c ON v.customer_id=c.customer_id	
					WHERE state='$state' AND licence_number='$license' AND b.time_end is null
					GROUP BY c.customer_id,c.is_fee_exempt) b
			ON a.parking_event_id=b.most_recent_parking_event
			INNER JOIN FeeMatrix fm   
					ON TIME_FORMAT(TIMEDIFF('$current_datetime',a.time_start),'%H')
					BETWEEN mintime AND maxtime
				;
 ";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  $fee["vehicle_id"]=$row["vehicle_id"];
		  $fee["customer_id"]=$row["customer_id"];
		  $fee["parking_event_id"]=$row["parking_event_id"];
		  $fee["fee"]=$row["fee"];
	  }
	}
	return $fee;
}	

function end_parking_event($conn, $parking_event_id,$fee_amount,$current_datetime,$payment_id) {
	$sql = "UPDATE ParkingEvent SET fee_amount=$fee_amount,time_end='$current_datetime',
	           WHERE parking_event_id=$parking_event_id;";
			   echo $sql;
	$result = $conn->query($sql);

}


// GET THE VARIABLES
$camera_id = $_GET["camera"];
$auth_key = $_GET["key"];

$license=$_GET["license"];
$state=$_GET["state"];

$vehicle_id="";
$fee="0.00";
$customer_id="";
$parking_event_id="";
$payment_id="NULL";

$servername = "127.0.0.1";
$username =   "garageuser"; 
$password =  "etA36wq51";  
$dbname = "garage_project";

$current_datetime= date("Y-m-d h:i:s");

$reply="{ \"status\": 400,\"message\": \"Unable to enter\" }";

// Create connection
$conn = new mysqli("$servername", "$username", "$password", "$dbname");

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

//  SEARCHING FOR VEHICLE
$fee_arr=determine_fee($conn, $license, $state,$current_datetime);
$reply ="";
$customer_id=$fee_arr["customer_id"];
$vehicle_id=$fee_arr["vehicle_id"];
$parking_event_id=$fee_arr["parking_event_id"];
$fee=$fee_arr["fee"];
$fee_amount=$fee_arr["fee"];

if ($fee=="0.00"){
	end_parking_event($conn, $parking_event_id,$fee_amount,$current_datetime,$payment_id);
}

if ($vehicle_id == !""){
	$reply="{ \"status\": 200,\"vehicle_id\" $vehicle_id,\"customer_id\" $customer_id,\"parking_event_id\": $parking_event_id,\"fee\": \"$fee\", \"message\": \"\",\"current_datetime\": \"$current_datetime\" }";
	//$reply="tewte";
} else {
	$reply="{ \"status\": 400, \"message\": \"Unable to find vehicle\",\"current_datetime\": \"$current_datetime\" }";
}

echo $reply;

$conn->close();


?> 

</body>
</html>