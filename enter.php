<!DOCTYPE html>
<html>
<body>
<!--  in directory  php -S 127.0.0.1:4000  -->
<!--  http://127.0.0.1:4000/enter.php?license=teste&state=TX   -->

<?php

function search_vehicle($conn, $license, $state) {
	$vid = "";
	$prohibit = "";
	$sql = "SELECT a.vehicle_id, a.customer_id, a.licence_number, a.state
			FROM Vehicle a
			where licence_number='$license' and state='$state'";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  $vid=  $row["vehicle_id"];
	  }
	}

	return $vid;
}	

function vehicle_is_authorized($conn,$vehicle_id){
	$prohibit = "";
	$sql = "SELECT coalesce(b.prohibit_from_entering  ,false) as prohibit_from_entering
			FROM Vehicle a LEFT OUTER JOIN Customer b on a.customer_id=b.customer_id
			where vehicle_id=$vehicle_id";
	$result = $conn->query($sql);
	if ($result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  $prohibit = $row["prohibit_from_entering"];
	  }
	}
	return $prohibit;
}

function insert_vehicle($conn, $license, $state) {
	$sql = "INSERT INTO Vehicle(licence_number, state) VALUES ('$license','$state')";
	$result = $conn->query($sql);
}

function add_parking_event($conn, $vehicle_id,$current_time) {
	$sql = "INSERT INTO ParkingEvent(vehicle_id,time_start) VALUES ($vehicle_id,'$current_time')";
	$result = $conn->query($sql);
}


// GET THE VARIABLES
$camera_id = $_GET["camera"];
$auth_key = $_GET["key"];

$license=$_GET["license"];
$state=$_GET["state"];

$vehicle_id="";
$customer_id="";
$prohibited_from_entering="1";

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
$vehicle_id=search_vehicle($conn, $license, $state);

// IF VEHICLE IS NOT FOUND INSERT A NEW RECORD
if ($vehicle_id == ""){
	insert_vehicle($conn, $license, $state);
	// SEARCH FOR VEHICLE AGAIN
	$vehicle_id=search_vehicle($conn, $license, $state);
}

// RETURN RESPONSE
if ($vehicle_id == !""){
	$prohibited_from_entering=vehicle_is_authorized($conn,$vehicle_id);
	if ($prohibited_from_entering =="0")
	{
		add_parking_event($conn,$vehicle_id,$current_datetime);
		$reply="{ \"status\": 200,\"vehicle_id\" $vehicle_id,\"Authorized\": \"Y\", \"message\": \"Authorized to enter\",\"current_datetime\": \"$current_datetime\" }";
	} else {
		$reply="{ \"status\": 200,\"vehicle_id\" $vehicle_id,\"Authorized\": \"N\", \"message\": \"Vehicle NOT Authorized to enter\",\"current_datetime\": \"$current_datetime\" }";
	}
} else {
	$reply="{ \"status\": 400, \"message\": \"Unable to authorize vehicle\",\"current_datetime\": \"$current_datetime\" }";
}

echo $reply;

$conn->close();


?> 

</body>
</html>