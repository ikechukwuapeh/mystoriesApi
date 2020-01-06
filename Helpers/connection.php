<?Php
$host_name = "localhost";
$database = "LittleStories";
$username = "root";         
$password = "";          

try {
$conn = new PDO('mysql:host='.$host_name.';dbname='.$database, $username, $password);
} catch (PDOException $e) {
print "Error!: " . $e->getMessage() . "<br/>";
die();
}
?>