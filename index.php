
<?php
include('connect.php');

/*
 * DEFINITIONS
 *
 * load the autoload file
 * define the constants client id,secret and redirect url
 * start the session
 */
require_once __DIR__.'/gplus-lib/vendor/autoload.php';

const CLIENT_ID = '442997050808-3r3cdde9jcbm4o8c0bdul6jb3nr3tvug.apps.googleusercontent.com';
const CLIENT_SECRET = 'bw_TcUrmNfPv16I-tG1gtec4';
const REDIRECT_URI = 'http://localhost:80/a/';

session_start();

/* 
 * INITIALIZATION
 *
 * Create a google client object
 * set the id,secret and redirect uri
 * set the scope variables if required
 * create google plus object
 */
$client = new Google_Client();
$client->setClientId(CLIENT_ID);
$client->setClientSecret(CLIENT_SECRET);
$client->setRedirectUri(REDIRECT_URI);
$client->setScopes('email');

$plus = new Google_Service_Plus($client);

/*
 * PROCESS
 *
 * A. Pre-check for logout
 * B. Authentication and Access token
 * C. Retrive Data
 * D. Determining Location and finding nearby users
 */

/* 
 * A. PRE-CHECK FOR LOGOUT
 * 
 * Unset the session variable in order to logout if already logged in
 * Remove the user from online table    
 */
if (isset($_REQUEST['logout'])) {
  $sql_delete = "DELETE FROM online WHERE email='".$_SESSION['email']."'";
  $qry_delete = mysqli_query($dbconnect,$sql_delete);
   session_unset();
}


// If users in already online in online table Updating login time So have better knowledge about online status

if(isset($_SESSION['email'])&&$_SESSION['email'])
{
  $sql_check = "SELECT * FROM online WHERE email ='".$_SESSION['email']."'";
  $qry_check = mysqli_query($dbconnect,$sql_check);
  if(mysqli_num_rows($qry_check)>0)
  {
    $sql_update_time = "UPDATE online SET login=now() WHERE email ='".$_SESSION['email']."'";
    $qry_updare_time = mysqli_query($dbconnect,$sql_update_time);
  }
}

/* 
 * B. AUTHORIZATION AND ACCESS TOKEN
 *
 * If the request is a return url from the google server then
 *  1. authenticate code
 *  2. get the access token and store in session
 *  3. redirect to same url to eleminate the url varaibles sent by google
 */





if (isset($_GET['code'])) {
  $client->authenticate($_GET['code']);
  $_SESSION['access_token'] = $client->getAccessToken();
  $redirect = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'];
  header('Location: ' . filter_var($redirect, FILTER_SANITIZE_URL));
}

/* 
 * C. RETRIVE DATA
 * 
 * If access token if available in session 
 * load it to the client object and access the required profile data
 */
if (isset($_SESSION['access_token']) && $_SESSION['access_token']) {
  
  $client->setAccessToken($_SESSION['access_token']);
  if($client->isAccessTokenExpired()) {

    $Url = $client->createAuthUrl();
    header('Location: ' . filter_var($Url, FILTER_SANITIZE_URL));


}
  $me = $plus->people->get('me');

  // Get User data
  $id = $me['id'];
  $name =  $me['displayName'];
  $email =  $me['emails'][0]['value'];
  $profile_image_url = $me['image']['url'];
  $cover_image_url = $me['cover']['coverPhoto']['url'];
  $profile_url = $me['url'];
  // Storing Name email and Pic URL in session
  $_SESSION['username'] = $name;
  $_SESSION['email'] = $email;
  $_SESSION['pic']=$profile_image_url;
  
} else {
  // get the login url   
  $authUrl = $client->createAuthUrl();
}


    /*
     * If login url is there then display login button
     * else print the retieved data
    */
    if (isset($authUrl)) {
        echo "<a class='login' href='" . $authUrl . "'><img src='gplus-lib/signin_button.png' height='50px'/></a>";
    } else {
        
         $sql = "SELECT * FROM user WHERE email='".$email."'";
        $qry = mysqli_query($dbconnect,$sql);
        if(mysqli_num_rows($qry)>0)
        {
          // User Alredy exists in our database So we will onle select his category and store in session
          $rs_old_user =mysqli_fetch_assoc($qry);
          $_SESSION['category'] = $rs_old_user['category'];
        }
        
       else{
            // there is no record of user in our database so we will just redirect him to add user page
            $Add_User_URL = "add_user.php";
            header('Location: ' . filter_var($Add_User_URL, FILTER_SANITIZE_URL));
            die();
          
          
        }
        
      // Displaying all the details of the user
        print "ID: {$id} <br>";
        print "Name: {$name} <br>";
        print "Email: {$email } <br>";
        echo "Profile Image URL:".$profile_image_url."<br>";
        print "Url: {$profile_url} <br><br>";
        print "Category : {$_SESSION['category']}<br>";
        echo "<a class='logout' href='?logout'><button>Logout</button></a><br><a href='page_b.php'><button>B page</button></a><br>";
        
    
/*

     D. Getting user Location and storing in database
     ->Selecting Online users
     ->Determing their distance from the user using Google Distance Matrix API
     ->Soring nearby users
 */



if(isset($_SESSION['lat'])&&isset($_SESSION['lon']))
{
  $lat = $_SESSION['lat'];
  $lon = $_SESSION['lon'];
  echo "User's Location  Latitide : ".$lat."  Longitude ".$lon."<br>";
  
 // Putting the logged in user in Online table
  
  $sql_making_online = "INSERT INTO online(email,name,lat,lon,login,category) VALUES ('".$_SESSION['email']."','".$_SESSION['username']."',".$lat.",".$lon.",now(),'".$_SESSION['category']."')";
  $qry_making_online = mysqli_query($dbconnect,$sql_making_online);

  // Selecting users which are online and have same cateogry as user
  // We will only select entries not older than 5 minutes as after the inactivity of 5 minutes we will consider user offline

  $sql_finding_online_users ="SELECT * FROM online WHERE login > (NOW() - INTERVAL 5 MINUTE) AND email <> '".$_SESSION['email']."' AND category='".$_SESSION['category']."'";
  $qry = mysqli_query($dbconnect,$sql_finding_online_users);

  $x = 0;
  $des="";

// if there are some online users
  if(mysqli_num_rows($qry)>0)
    {
      while($rs=mysqli_fetch_assoc($qry))
       {
         if($x){$des=$des."%7C"; }
         $people[$rs['email']] = 0;
  
         $des=$des.$rs['lat']."%2C".$rs['lon'];
         $x++;
      }
    $x = 0;

    
    /* Using Google Distance matrix API to determine distance of other online users
     * Decoding JSON obtained from Google Distance matrix API
     * Storing Distance in an array
     * Assigning Distance to the users
     * Sorting User according to their distance
     * Printing Online users Sorted by Distance
    */



    $string = file_get_contents("https://maps.googleapis.com/maps/api/distancematrix/json?units=imperial&origins=".$lat.",".$lon."&destinations=".$des."&key=AIzaSyC5Y5Poy0Xi0Rs3LrXP6u8JwZxnSoE4JYQ");

   $json_d = json_decode($string,true);
   
   foreach ($json_d['rows'] as $row) {
      
      foreach ($row['elements'] as $value) {
          
          $res = $value['distance']['value'];
          $dis[$x] = $res;
          $x++;
        }

   }

   $x = 0;
   foreach ($people as $key => $value) 
    {
        $people[$key] = $dis[$x];
        $x++;
    }
    // Sorting Online users accoridng to their distance
    asort($people);
    echo "Online Users <br>";
    foreach ($people as $key => $value) {
         
         echo $key." ->".$value."meters<br>";
      }
}
// if no online users found
else{
  echo "no online users Found";
}

}

/*if location is not set i.e. no coordinates
 * Calling javascript function to determine Location
*/

else{
  // Redirecting to User to determine location
  $Get_Location_URL = "get_location.php";
  header('Location: ' . filter_var($Get_Location_URL, FILTER_SANITIZE_URL));
}
}

?>



