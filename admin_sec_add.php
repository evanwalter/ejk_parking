<!DOCTYPE html>
<html>
<head>

<link href="bootstrap.min.css" rel="stylesheet">  

</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<div class="container">
<input class="btn btn-primary" type="button" onclick="location.href='admin.php'" value="Admin Home Page"> 
<input class="btn btn-primary" type="button" onclick="location.href='admin_sec.php'" value="Update Existing Record"> <br>
<br>

<?php

$action ="lookup";
$servername = "127.0.0.1";
$username =   "garageuser"; 
$password =  "etA36wq51";  
$dbname = "garage_project";

$current_datetime= date("Y-m-d h:i:s");
 
$message = "";
$email = "";
$customer_id="";
$vehicle_id="";
$license="";
$state="";

$is_employee = "0";
$is_admin = "0";
$is_fee_exempt = "0";
$prohibit_from_entering = "0";
$parking_event_fee_exempt = "0";
$parking_event_id="";
$time_start="";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
  $action = $_POST['action'];	
  // collect value of input field
  $email = $_POST['email'];
  $license = $_POST['license'];
  $state = $_POST['state'];
  $is_employee = $_POST['is_employee'];
  $is_admin = $_POST['is_admin'];
  $is_fee_exempt = $_POST['is_fee_exempt'];
  $prohibit_from_entering = $_POST['prohibit_from_entering'];
  $result_ret=lookup_customer($email,$license,$state,$is_employee,$is_admin,$is_fee_exempt,$prohibit_from_entering);
  if ($customer_id  != "") {
	$message="Customer already exists";
  } else {
	  $customer_id=add_customer($email,$license,$state,$is_employee,$is_admin,$is_fee_exempt,$prohibit_from_entering);
	  if ($customer_id != ""){
		  $message="Record successfully created!";
	  }
   }	  
}
 function lookup_customer($email,$license,$state) {
	$servername = "127.0.0.1";
	$username =   "garageuser"; 
	$password =  "etA36wq51";  
	$dbname = "garage_project";
	
	$result_ret = array("customer_id" => "", "license" => "","state" => "","parking_event_id" =>"", "time_start" =>"" );
	 
	// Create connection
	$conn = new mysqli("$servername", "$username", "$password", "$dbname");
	// Check connection
	if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
	}
	$sql = "SELECT a.customer_id, b.licence_number, b.state,
			       c.parking_event_id,c.time_start
			FROM Customer a LEFT OUTER JOIN Vehicle b on a.customer_id=b.customer_id
			                LEFT OUTER JOIN ParkingEvent c on b.vehicle_id=c.vehicle_id
							          and c.time_end IS NULL 
			where a.email='$email' or (licence_number='$license' and state='$state')";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  $result_ret["customer_id"]=  $row["customer_id"];
		  $result_ret["license"]=  $row["licence_number"];
		  $result_ret["state"]=  $row["state"];
		  $result_ret["parking_event_id"]=  $row["parking_event_id"];
		  $result_ret["time_start"]=  $row["time_start"];
	  }
	}
	$conn->close();
	return $result_ret;
}	


function add_customer($email,$license,$state,$is_employee,$is_admin,$is_fee_exempt,$prohibit_from_entering) {
	$servername = "127.0.0.1";
	$username =   "garageuser"; 
	$password =  "etA36wq51";  
	$dbname = "garage_project";
	
	$customer_id="";
	
	// Create connection
	$conn = new mysqli("$servername", "$username", "$password", "$dbname");
	// Check connection
	if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
	}

	$sql = "INSERT INTO Customer(email,is_employee,is_admin,is_fee_exempt,prohibit_from_entering) 
			values ('$email',$is_employee,$is_admin,$is_fee_exempt,$prohibit_from_entering);
	";
	$result = $conn->query($sql);	
	
	$sql = "SELECT customer_id FROM Customer where email='$email';
	";
	$result = $conn->query($sql);	
	if ($result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  $customer_id=  $row["customer_id"];
	  }
	}
	
	if ($customer_id != "") {
		$sql = "INSERT INTO  Vehicle (customer_id,licence_number,state)
				SELECT $customer_id,'$license','$state';";
		$result = $conn->query($sql);	
	}

	$conn->close();
	return $customer_id;
}	

if ($message != "")
{
	echo $message;
	
}  else {
?>

<table>
<tr><td>Email: </td><td><input type="text" name="email"> </td></tr>
<tr><td>License: </td><td><input type="text" name="license"> </td></tr>
<tr><td>State: </td><td><input type="text" name="state"> </td></tr>
<tr><td>
Is Employee:  </td><td><select name="is_employee">
   <option value="1" selected>Yes</option>
   <option value="0" >No</option>
</select></td></tr>
<tr><td>
Is Admin:  </td><td><select name="is_admin">
   <option value="1">Yes</option>
   <option value="0" selected>No</option>
</select></td></tr>
<tr><td>
Is Fee Exempt:  </td><td><select name="is_fee_exempt">
   <option value="1" selected>Yes</option>
   <option value="0" >No</option>
</select></td></tr>
<tr><td>
Prohibit from entering:  </td><td><select name="prohibit_from_entering" default="0">
   <option value="1">Yes</option>
   <option value="0" selected="selected">No</option>
</select></td></tr>
<tr><td>
Charge for current parking event:  </td><td><select name="parking_event_fee_exempt" default="0">
   <option value="1">Yes</option>
   <option value="0" selected>No</option>
</select></td></tr>
</table>
<br>
   <input id="update" type="submit" value="Create Record"/>

<?php 
};
?>

</div>
</form>

</body>
</html>