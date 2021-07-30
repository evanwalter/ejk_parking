<!DOCTYPE html>
<head>

<link href="bootstrap.min.css" rel="stylesheet">  

</head>
<html>
<body>
<form method="post" action="<?php echo $_SERVER['PHP_SELF'];?>">

<div class="container">

<input class="btn btn-primary" type="button" onclick="location.href='admin_sec_add.php'" value="Add New Record"> <br><br>
<input class="btn btn-primary" type="button" onclick="location.href='admin_sec.php'" value="Update Existing Record"> <br>

</div>
</form>
</html>