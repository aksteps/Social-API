<?php
session_start();
$MainPage = "index.php";
/*
 *If the location is given as input by user
 * Convert address to Coordinates Using Google Geocode
*/
if(isset($_POST['autocomplete']))
{
  $string = $_POST['autocomplete'];
$string = preg_replace('/\s+/', '+', $string);
$json_string = file_get_contents("https://maps.googleapis.com/maps/api/geocode/json?address=".$string."&key=AIzaSyDWDEzsjgBARJRp0EPVUuBx5WDSmtJU1hY");

$jsond = json_decode($json_string,true);
$_SESSION['lat']= $jsond['results'][0]['geometry']['location']['lat'];
$_SESSION['lon']= $jsond['results'][0]['geometry']['location']['lng'];
echo $_SESSION['lat'];
echo $_SESSION['lon'];

      header('Location: ' . filter_var($MainPage, FILTER_SANITIZE_URL));
      die();
}
/*
 *If the location is Detected using GPS
 * 
*/
if(isset($_POST['lat'])&&isset($_POST['lon']))
{
  $_SESSION['lat']=$_POST['lat'];
  $_SESSION['lon']=$_POST['lon'];
 echo $_SESSION['lat'];
 echo " ";
echo $_SESSION['lon'];
            header('Location: ' . filter_var($MainPage, FILTER_SANITIZE_URL));
            die();
}

?>
<html>
  <head>
   
    
    <style>
    #autocomplete{
width: 30%;
    }
    </style>
  </head>

  <body>
   <P>Provide your location</p>
      
<!-- Form to get address from User   -->
      <form method="post" action="">
      <input type="text" id="autocomplete" name="autocomplete" placeholder="Enter Your Address" ></input>
             <input type="submit" name="submit" ></input>
           </form>
           <p id="demo"></p>
<!-- Hidden Form to Convert Javascript values to $_POST-->
           <button type="submit" onclick="getLocation()" >Determine Using Location</button>
           <form method="post" action="" id="myForm">
<input type="hidden" name="lat" id="lat" visibility="hidden"/>
<input type="hidden" name="lon" id="lon" visibility="hidden"/>
</form>
  

    <script>
      // This example displays an address form, using the autocomplete feature
      // of the Google Places API to help users fill in the information.

      
      var placeSearch, autocomplete;
      var x = document.getElementById("demo");

      function initAutocomplete() {
        // Create the autocomplete object, restricting the search to geographical
        // location types.
        

        autocomplete = new google.maps.places.Autocomplete(
            /** @type {!HTMLInputElement} */(document.getElementById('autocomplete')),
            {types: ['geocode']});
      
        // When the user selects an address from the dropdown, populate the address
        // fields in the form.
             }

     // Function to determine Location Using GPS
      function getLocation() {
         x.innerHTML = "Determining Location ........";
         if (navigator.geolocation) {
           navigator.geolocation.watchPosition(showPosition);
        } else { 
            x.innerHTML = "Geolocation is not supported by this browser.";}
         }
    
      function showPosition(position) {
         document.getElementById("lat").value=position.coords.latitude;
         document.getElementById("lon").value=position.coords.longitude;
         document.getElementById("myForm").submit();
       }
  
      
    
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyDXxNJsO-GYBiRXvbdFTSzWetY48TpcEm0&libraries=places&callback=initAutocomplete"
        async defer></script>
  </body>
</html>

