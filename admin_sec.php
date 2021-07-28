<!DOCTYPE html>
<html>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">
<?php
/*   PAGE TO EDIT CUSTOMER SECURITY INFO

 http://127.0.0.1:4000/admin_sec.php 
 */

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
  $result_ret=lookup_customer($email,$license,$state);
  $email = $_POST['email'];
  $state=$result_ret["state"];
  $license=$result_ret["license"];
  $customer_id=$result_ret["customer_id"];

/*  
 if (($customer_id == "")&&($email != "")&&($action=="add" )) {
	  $customer_id=add_customer($email,$license,$state);
	  if ($customer_id == "") {
		  $action="update";
		  $message="";	  
	  }
  }
  */
  if (($action=="update")&& ($customer_id != "")) {
	  update_customer($customer_id,$license,$state,$is_employee,$is_admin,$is_fee_exempt,$prohibit_from_entering);
  }

  if (($customer_id == "")&&($email != "")) {
	  $action="add";
	  $message="Record Not Found";
  }
  
  if ($customer_id != "") {
	   $message="";
	  $action="update";
  }
} 

  if (empty($email)) {
    echo "Email: <input type=\"text\" name=\"email\" value=\"\"><br>";
  } else {
    echo "Email: <input type=\"text\" name=\"email\" value=\"" . $email . "\"><br>";
  }
	echo "License: <input type=\"text\" name=\"license\" value=\"" . $license . "\"><br>";
 	echo "State: <input type=\"text\" name=\"state\" value=\"" . $state . "\"><br>";
	echo "<input type=\"hidden\" name=\"action\" value=\"" . $action . "\">";
	echo "<input type=\"hidden\" name=\"customer_id\" value=\"" . $customer_id . "\">";
	echo "<p style=\"color: red;\">". $message  ."</p>";
  
  
 function lookup_customer($email,$license,$state) {
	$servername = "127.0.0.1";
	$username =   "garageuser"; 
	$password =  "etA36wq51";  
	$dbname = "garage_project";
	
	$result_ret = array("customer_id" => "", "license" => "","state" => "" );
	 
	// Create connection
	$conn = new mysqli("$servername", "$username", "$password", "$dbname");
	// Check connection
	if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
	}
	$sql = "SELECT a.customer_id, b.licence_number, b.state
			FROM Customer a left outer join Vehicle b on a.customer_id=b.customer_id
			where a.email='$email' or (licence_number='$license' and state='$state')";
	$result = $conn->query($sql);

	if ($result->num_rows > 0) {
	  // output data of each row
	  while($row = $result->fetch_assoc()) {
		  $result_ret["customer_id"]=  $row["customer_id"];
		  $result_ret["license"]=  $row["licence_number"];
		  $result_ret["state"]=  $row["state"];
	  }
	}
	$conn->close();
	return $result_ret;
}	


function add_customer($email,$license,$state) {
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

	$sql = "INSERT INTO Customer(email) values ('$email');
			SELECT customer_id FROM Customer where email='$email';
	";
	echo $sql;
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



 function update_customer($customer_id,$license,$state,$is_employee,$is_admin,$is_fee_exempt,$prohibit_from_entering) {
	$servername = "127.0.0.1";
	$username =   "garageuser"; 
	$password =  "etA36wq51";  
	$dbname = "garage_project";
	
	$result_ret = array("customer_id" => "", "license" => "","state" => "" );
	 
	// Create connection
	$conn = new mysqli("$servername", "$username", "$password", "$dbname");
	// Check connection
	if ($conn->connect_error) {
	die("Connection failed: " . $conn->connect_error);
	}
	$sql = "UPDATE Vehicle SET customer_id=$customer_id
			WHERE licence_number='$license' and state='$state';";
	$result = $conn->query($sql);	

	$sql = "INSERT INTO  Vehicle (customer_id,licence_number,state)
	        SELECT $customer_id,'$license','$state'
			WHERE NOT EXITS(select Count(1) from Vehicle where licence_number='$license' and state='$state');";
	$result = $conn->query($sql);	
	
	$sql = "UPDATE Customer SET 
			is_employee=$is_employee,
			is_admin=$is_admin,
			is_fee_exempt=$is_fee_exempt,
			prohibit_from_entering=$prohibit_from_entering
			WHERE customer_id=$customer_id;";
	$result = $conn->query($sql);

	$conn->close();
	return $result_ret;
}	

  
?>

<?php   
if ( $action == "lookup") {
?>
   <input id="update" type="submit" value="Lookup"/>

<?php } else {
if ( $action == "add") {
	?>
	
	Customer not found
<br>
   <input id="add" type="submit" value="Add"/>
	
	<?php 
} else {
	?>
<br>
Is Employee: <select name="is_employee">
   <option value="1">Yes</option>
   <option value="0" selected>No</option>
</select>
<br>
Is Admin: <select name="is_admin">
   <option value="1">Yes</option>
   <option value="0" selected>No</option>
</select>
<br>
Is Fee Exempt: <select name="is_fee_exempt">
   <option value="1">Yes</option>
   <option value="0" selected>No</option>
</select>
<br>
Prohibit from entering: <select name="prohibit_from_entering" default="0">
   <option value="1">Yes</option>
   <option value="0" selected="selected">No</option>
</select>

<br>
   <input id="update" type="submit" value="Update Customer"/>
<?php }
 };?>


</form>

</body>
</html>