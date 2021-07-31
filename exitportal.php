<!DOCTYPE html>
<html>
<head>

<link href="bootstrap.min.css" rel="stylesheet">  

</head>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<!--  in directory  php -S 127.0.0.1:4000  -->
<!--   http://127.0.0.1:4000/exitportal.php?camera=1&key=tnlkc6nfe363atedae94h&license=testcar&state=TX   -->

<?php

function authenticate_camera($conn,$camera_id,$auth_key){
	$grant_access = False;
	$sql = "SELECT camera_id
			FROM Camera
			where camera_id=$camera_id and authentication_key='$auth_key';";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  $grant_access=True;
	  }
	}	
	return $grant_access;
}


/**  CALCULATES THE FEE BASES ON:
	1. WHETHER CUSTOMER EXISTS AND IS FEE EXEMPT
	2. WHETHER THE CURRENT PARKING EVENT WAS MARKED FEE EXEMPT
	3. WHETHER THE CURRENT PARKING EVENT HAS BEEN PAID
	4. CALCULATES FEE OFF OF PAY MATRIX
**/
function determine_fee($conn, $license, $state, $current_datetime) {
	$fee = array("parking_event_id" => "", "vehicle_id" => "","fee" => "","customer_id" => ""  );
	$sql = "SELECT a.* ,
			CASE WHEN a.fee_exempt=true THEN 0.00 
				 WHEN fee_amount=0 AND fee_amount IS NOT NULL then 0.00
				 WHEN COALESCE(b.is_fee_exempt,false)=true THEN 0.00 
				 WHEN COALESCE(py.accepted,false)= true THEN 0.00
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
			LEFT OUTER JOIN Payment py on a.parking_event_id=py.parking_event_id
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

function end_parking_event($conn, $parking_event_id,$fee_amount,$current_datetime) {
	$sql = "UPDATE ParkingEvent SET fee_amount=$fee_amount,time_end='$current_datetime'
	WHERE parking_event_id=$parking_event_id;";
			   //echo $sql;
	$result = $conn->query($sql);
}

function get_payment_methods($conn, $customer_id) {
	$payment_methods=array();
	$sql = "Select payment_method_alias
	FROM PaymentMethod
	WHERE customer_id=$customer_id;";
			   //echo $sql;
	$result = $conn->query($sql);
	while($row = $result->fetch_assoc()) {
		  array_push($payment_methods,$row["payment_method_alias"]);
	  }
	return $payment_methods;
}


// GET THE VARIABLES
$camera_id = $_GET["camera"];
$auth_key = $_GET["key"];
$grant_access = False;

$license=$_GET["license"];
$state=$_GET["state"];

$vehicle_id="";
$fee="0.00";
$customer_id="";
$parking_event_id="";
$payment_id="NULL";
$payment_methods=array();

$servername = "127.0.0.1";
$username =   "garageuser"; 
$password =  "etA36wq51";  
$dbname = "garage_project";

$current_datetime= date("Y-m-d h:i:s");


if ($_SERVER["REQUEST_METHOD"] == "POST") {
 	  $parking_event_id=$_POST['parking_event_idh'];
	  $camera_id = $_POST["camera_id"];
	  $auth_key = $_POST["auth_key"];

}

// Create connection
$conn = new mysqli("$servername", "$username", "$password", "$dbname");

// Check connection
if ($conn->connect_error) {
  die("Connection failed: " . $conn->connect_error);
}

// AUTHENTICATE CAMERA
$grant_access=authenticate_camera($conn,$camera_id,$auth_key);

if (!$grant_access==True){
	die("{ \"status\": 400,\"message\": \"Unable to authenticate\" }");
}

// IF PAYMENT OPTION WAS OFFERED ATTEMPT TO PAY IT
if ($parking_event_id != ""){
 ///////////////////////////////////////////////////
 ///////////// HERE WE WILL CALL A METHOD TO AUTHENTICATE PAYMENT
 end_parking_event($conn, $parking_event_id,$fee_amount,$current_datetime);
	
}

//  SEARCHING FOR VEHICLE
$fee_arr=determine_fee($conn, $license, $state,$current_datetime);
$reply ="";
$customer_id=$fee_arr["customer_id"];
$vehicle_id=$fee_arr["vehicle_id"];
$parking_event_id=$fee_arr["parking_event_id"];
$fee=$fee_arr["fee"];
$fee_amount=$fee_arr["fee"];

if ($fee=="0.00" || $fee==""){
	echo "<div class=\"container\" ><br>";
	echo "<p>Thank You.  Have a Nice Day</p>";
	echo "</div>";
	
	end_parking_event($conn, $parking_event_id,$fee_amount,$current_datetime,$payment_id);
} else {	

		if ($customer_id != "") {
			$payment_methods=get_payment_methods($conn, $customer_id);
		}

		echo "<div class=\"container\" ><br>";
		
		echo "<h3>Amount owed: \$$fee_amount.</h3>
		<h4>Please enter cash below or enter credit card information.</h4><br>";
		if (count($payment_methods) > 0) {
		echo "<select name=\"paymentoption\" >";
		foreach ($payment_methods as &$value) {
			echo "<option > $value (saved account)</option>";
		} 
		echo "<option>New</option>";
		echo "</select><br><br><br>";
		}
		echo "<table>";
		echo "<tr><td>Name on card: </td><td><input type=\"text\" name=\"nameoncard\" ></td></tr>";
		echo "<tr><td>Card Number: </td><td><input type=\"text\" name=\"number\" ></td></tr>";
		//echo "Month: <input type=\"text\" name=\"month\" ><br>";
		//echo "Year: <input type=\"text\" name=\"year\" ><br>";
		echo "<tr><td>Expiration (MM/YY): </td><td><select name=\"m1\"><option>0</option><option>1</option><option>2</option></select>";
		echo "<select name=\"m2\"><option>0</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option></select>";
		echo "/<select name=\"y1\"><option>2</option><option>3</option></select>";
		echo "<select name=\"y2\"><option>0</option><option>1</option><option>2</option><option>3</option><option>4</option><option>5</option><option>6</option><option>7</option><option>8</option><option>9</option></select>";
		echo "</td></tr>";
		echo "<tr><td>code: </td><td><input type=\"text\" name=\"accesscode\" ></td></tr>";
		echo "</table><br><br>";
		echo "<input class=\"btn btn-primary\" type=\"submit\" value=\"submit\"   >";
		echo "</div>";
		echo "<input type=\"hidden\"  name=\"$parking_event_idh\" value=\"$parking_event_id\">";
		echo "<input type=\"hidden\"  name=\"camera_id\" value=\"$camera_id\">";
		echo "<input type=\"hidden\"  name=\"auth_key\" value=\"$auth_key\">";
		
}

$conn->close();


?> 
</form>
</body>
</html>