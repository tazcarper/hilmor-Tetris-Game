<?php
//include db configuration file
include_once("config.php");

//MySQLi query
$results = $mysqli->query("SELECT username,score FROM users");

//get all records from add_delete_record table
echo '<table>';
while($row = $results->fetch_assoc())
{
  echo '<tr><td>'.$row["username"].'</td>';

  echo '<td>'.$row["score"].'</td></tr>';
}
echo '</table>';

//close db connection
$mysqli->close();
?>