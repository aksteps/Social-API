<?php
include('connect.php');
session_start();
if(isset($_SESSION['email'])&&$_SESSION['email'])
{
	/*The Code here ensures that we always get the user's latest activity time in login (online table)
	 * i.e. if there is no activity by a user for some time we will consider that user offline
	 * So this updates to the most recent time
	 * Thus giving us more accurate knowledge about users activity
	 * If we have multiple pages then we need to insert this code in every page
	 * This is the only aim for this page
	*/
  $sql_check = "SELECT * FROM online WHERE email ='".$_SESSION['email']."'";
  $qry_check = mysqli_query($dbconnect,$sql_check);
  if(mysqli_num_rows($qry_check)>0)
  {
    $sql_update_time = "UPDATE online SET login=now() WHERE email ='".$_SESSION['email']."'";
    $qry_updare_time = mysqli_query($dbconnect,$sql_update_time);
  }
}

echo "Name :".$_SESSION['username']."<br>";
echo "Email :".$_SESSION['email']."<br>";
echo "Pic URL :".$_SESSION['pic']."<br>";
echo "Category :".$_SESSION['category']."<br>";
echo "<a href='index.php'><button>Main Page</button></a>";

?>