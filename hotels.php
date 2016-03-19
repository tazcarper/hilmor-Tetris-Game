<?php $hotels = "Conrad Chicago
Doubletree Chicago Magnificent Mile
Drake Hotel
Fairmont Chicago
Hampton Inn Chicago Downtown/Mag Mile
Hilton Chicago
Hilton Garden Inn Magnificent Mile
Hilton Suites Magnificent Mile
Holiday Inn Chicago Mart Plaza
Homewood Suites Chicago Downtown/Mag Mile
Hotel Allegro Chicago, a Kimpton Hotel
Hotel Chicago Formerly Hotel Sax
Hyatt Chicago Magnificent Mile
Hyatt Regency Chicago
InterContinental Chicago Mag. Mile
Kinzie Hotel
Omni Hotel Chicago
Sheraton Chicago Hotel and Towers
Swissotel Chicago
W Chicago City Center
W Chicago Lakeshore
Warwick Allerton Hotel Chicago
Westin Chicago River North
Westin Michigan Avenue Chicago
Wyndham Grand Riverfront
Chicago Marriott Downtown Mag Mile
Courtyard Marriott Downtown River North
Embassy Suites Chicago Lakefront
Hampton Majestic Chicago Theater District
Hyatt Regency McCormick Place
JW Marriott Chicago
Renaissance Blackstone Chicago Hotel
Renaissance Chicago Hotel";

$list = explode("\n", $hotels);

echo json_encode($list);

?>

