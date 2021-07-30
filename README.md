# ejk_parking

Camera calls API to record Vehicle Requesting to Enter Garage:
Example:
 http://127.0.0.1:4000/enter.php?camera=1&key=tnlkc6nfe363atedae94h&license=testcar&state=TX  


Reply:
{ "status": 200,"vehicle_id" 9,
	"Authorized": "Y", "message": "Authorized to enter","current_datetime": "2021-07-17 02:56:28" } 


Camera calls API to get Fee for Vehicle Requesting to Exit Garage:
Example:
 http://127.0.0.1:4000/exit.php?camera=1&key=tnlkc6nfe363atedae94h&license=testcar&state=TX 

Reply:
{ "status": 200,"vehicle_id" 9,"customer_id" 0,"fee": "1.00",
	"message": "","current_datetime": "2021-07-17 03:54:31" } 
