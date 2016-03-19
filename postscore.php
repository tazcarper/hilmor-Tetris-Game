<?php

date_default_timezone_set('America/Chicago');
$gmt_offset = '-6';

include('inc.php');

if(empty($_POST) && !empty($_GET['scotttest']))
	$_POST = $_GET;

$every_possible_field = array('username', 'score');
foreach($every_possible_field as $field) if(empty($_POST[$field])) $_POST[$field] = ''; //initializing
$_POST = array_map('trim', $_POST); //basic cleaning

$username = un_naughty(sanitize($_POST['username'], SANI_AVERAGE));
$score = sanitize($_POST['score'], SANI_INT);

if($username && $score) {
	
	$ip_address = get_ip_address();
	
	$fingers_raw = array(
		'username' => $username,
		'ip_address' => $ip_address,
		'user_agent' => $_SERVER['HTTP_USER_AGENT'],
		'http_accept' => $_SERVER['HTTP_ACCEPT'],
		'http_accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'],
		'http_accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'],
		//'_clientInfo' => $client_info
	);
	$fingers = serialize($fingers_raw);
	$fingerprint = hash('sha512', $fingers);
	$dbh ='';
	db_connect();
	$db_table = 'topscores';
	
	$result = array();
	
	try {
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbh->beginTransaction();
		$sql1 = "SELECT * FROM $db_table WHERE Fingerprint = :fingerprint";
		$sth1 = $dbh->prepare($sql1, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
		$sth1->execute(array(':fingerprint' => $fingerprint));
		$result1= $sth1->fetch(PDO::FETCH_ASSOC);
		$dbh->commit();
	}
	catch (Exception $e) {
		$dbh->rollBack();
	}
	
	try {
		$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
		$dbh->beginTransaction();
		if(empty($result1)) {
			$sql2 = "
				INSERT INTO $db_table 
				(DisplayName, Score, IPAddr, CreateDtTm, _SERVER, Fingerprint) VALUES 
				(:username, :score, :ipaddr, ".strtotime($gmt_offset.' hours'). ", :server, :fingerprint)";
			$sth2 = $dbh->prepare($sql2, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth2->execute(array(
				':username' => $username,
				':score' => $score,
				':ipaddr' => get_ip_address(),
				':server' => serialize($_SERVER),
				':fingerprint' => $fingerprint
			));
		}
		else {
			$sql2 = "
				UPDATE $db_table SET
				Score = :score
				WHERE Fingerprint = :fingerprint";
			$sth2 = $dbh->prepare($sql2, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			$sth2->execute(array(
				':score' => $score,
				':fingerprint' => $fingerprint
			));
		}
		$dbh->commit();
	}
	catch (Exception $e) {
		$dbh->rollBack();
	}
}

try {
	$dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
	$dbh->beginTransaction();
	$sql3 = "SELECT * FROM $db_table ORDER BY Score DESC LIMIT 10";
	$sth3 = $dbh->prepare($sql3, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
	$sth3->execute();
	while($row = $sth3->fetch(PDO::FETCH_ASSOC))
		$highscores[] = $row;
	$dbh->commit();
}
catch (Exception $e) {
	$dbh->rollBack();
}

echo "<pre>";print_r(array($sth1, $result1, $sth2, $sth3, $highscores));echo "</pre>";

if(count($highscores)) {
	echo '<table>';
	foreach( $highscores as $row) {
		echo '<tr><td>'.$row["DisplayName"].'</td>';
		echo '<td>'.$row["Score"].'</td></tr>';
	}
	echo '</table>';
}
else echo "You're the first!";