<html>
<form method="post" action="" >
	<label>Select Category</label>
<select name="Category" required="required">
 <option value="Musician">Musician</option>
  <option value="Painter">Painter</option>
  <option value="Designer">Designer</option>
</select>
<input type="submit"/>
</form>
</html>
<?php
/* First time users are directed here
 * The form aboves asks users to select the category
 * The code below after determing the category 
 *  Adds the user in the database and redirects the User back to Main Page
 *
 */
include('connect.php');
if(isset($_POST['Category']))
{
session_start();
$mainpage = "http://localhost/a/";
echo $_SESSION['email'];
if(isset($_SESSION['email'])&&$_SESSION['email'])
{
	$_SESSION['category']=$_POST['Category'];
	echo $_POST["Category"];
	
	$sql_add_user = "INSERT INTO user(email,name,pic_url,category) VALUES('".$_SESSION['email']."','".$_SESSION['username']."','".$_SESSION['pic']."','".$_POST['Category']."')";
	$qry_add_user = mysqli_query($dbconnect,$sql_add_user);
	header('Location: ' . filter_var($mainpage, FILTER_SANITIZE_URL));
	die();
	}
	header('Location: ' . filter_var($mainpage, FILTER_SANITIZE_URL));
	die();
}

?>