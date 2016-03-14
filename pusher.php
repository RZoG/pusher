<?php

function SendPushover($token, $user, $message, $device=NULL, $title=NULL, $url=NULL, $url_title=NULL, $priority=NULL, $sound=NULL) {
	curl_setopt_array($ch = curl_init(), array(
  		CURLOPT_URL => "https://api.pushover.net/1/messages.json",
  		CURLOPT_POSTFIELDS => array(
  		"token" => $token,
  		"user" => $user,
  		"message" => $message,
		"device" => $device,
		"title" => $title,
		"url" => $url,		
		"url_title" => $url_title,
		"priority" => $priority,
		"sound" => $sound
		)));
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_exec($ch);
	curl_close($ch);
}

function ICMPping($host,$time) {  
	$ping=shell_exec("ping -c".$time." ".$host);
	if (strpos($ping, " time=")) return ("Up");
	else {
		return("Down");
	}
}


function TCPping($host, $port, $timeout) {  
	$fp = @fSockOpen($host, $port, $errno, $errstr, $timeout); 
	if (!$fp) return ("Down");
	else {
		fclose($fp);
		return("Up");
	}
}

function HTTPstatus($host) {
	$ch = curl_init($host);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_exec($ch);
	$status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
	curl_close($ch);
	return($status);
}

function KeywordCheck($host, $string) {
	$page = file_get_contents($host);
    $status = strpos($page, $string);
    if(!$status) return ("Missing");
	else return("Found");
}

function mysnmpget($host, $string, $objectid, $time) {
	$status=snmpget($host, $string, $objectid, $time);
	if ($status == false) return ("!Error!");
	else return ($status);
}

function RememberStatus() {
	global $_fname, $old_status, $current_status;
	$_fname.=".log";
	if (file_exists($_fname)) {
		echo ("<br> File exists.\n");
		$handle=fopen($_fname, "r");
		if (!$handle) $old_status=$current_status; 
		else {
			$old_status=fread($handle, filesize($_fname));
			fclose($handle);
		}

	} 
	else {
		echo ("<br> File was missing\n");
    	$old_status=$current_status; 

	}
 
	$handle=fopen($_fname, "w");
	fwrite($handle,$current_status);
	fclose($handle); 

	echo ("<br> Old status was: ".$old_status."\n");
	echo ("<br> New status is: ".$current_status."\n");
}

//  Program start
//-----------------

$ver =0.98;				// Set version number
echo ("<br> Pusher ver. $ver\n");	// Introduce yourself
echo ("<br> Created by RZoG/Slight\n");	// Show credits

// Read configuration file
$ini_array = parse_ini_file("pusher.ini",TRUE);

//  Main loop
//-------------

// Put configuration file in to array 
foreach($ini_array as $config){
	if(isset($config["Type"]))		$_type		=$config["Type"];	else $_type=NULL;
	if(isset($config["Title"]))		$_title		=$config["Title"];	else $_title=NULL;
	if(isset($config["FName"]))		$_fname		=$config["FName"];
	if(isset($config["Token"]))		$_token		=$config["Token"];
	if(isset($config["User"]))		$_user		=$config["User"];
	if(isset($config["Device"]))		$_device	=$config["Device"];
	if(isset($config["Url"]))		$_url		=$config["Url"];
	if(isset($config["Url_Title"]))		$_url_title	=$config["Url_Title"];
	if(isset($config["Priority"]))		$_priority	=$config["Priority"];
	if(isset($config["Sound"]))		$_sound		=$config["Sound"];
	if(isset($config["Host"]))		$_host		=$config["Host"];
	if(isset($config["Port"]))		$_port		=$config["Port"];
	if(isset($config["Time"]))		$_time		=$config["Time"];
	if(isset($config["String"]))		$_string	=$config["String"];
	if(isset($config["ObjectID"]))		$_objectid	=$config["ObjectID"];
// Show tittle of current job
	echo ("<br>\n<br> $_title");
// Execute specific acction determined by the type
	switch ($_type){

//  Set Default
//---------------

    	case "Defaults":					// Set global values pusher.ini should begin with those
			echo (" - Set default values\n");	// you can set it again to change pushover account
			break;					// or set some variables used in global in other places. 

//  ICMP Ping
//-------------
		case "ICMP_Ping":
			echo (" - ICMP Ping\n");
			
			$current_status=ICMPping($_host,$_time); 	// $_host - DNS name or IP of the target
									// $_time - number of trays any scccesful is OK
			RememberStatus();

			if ($old_status!=$current_status) { 
				if ($current_status=="Down") $_message="Adress ".$_host." stops responding!";
				else $_message="Adress ".$_host." is responding again.";
				echo ("<br> ".$_message."\n");
				SendPushover($_token, $_user, $_message, $_device, $_title, $_url, $_url_title, $_priority, $_sound);
			}
			else echo ("<br> The same old story\n");
			break;



//  TCP Ping
//------------
		case "TCP_Ping":
			echo (" - TCP Ping\n");
			
			$current_status=TCPping($_host, $_port, $_time);

			RememberStatus();

			if ($old_status!=$current_status) { 
				if ($current_status=="Down") $_message="Adress ".$_host." on port ".$_port." stops responding!";
				else $_message="Adress ".$_host." on port ".$_port." is responding again.";
				echo ("<br> ".$_message."\n");
				SendPushover($_token, $_user, $_message, $_device, $_title, $_url, $_url_title, $_priority, $_sound);
			}
			else echo ("<br> The same old story\n");
			break;

//  HTTP Status
//---------------
		case "HTTP_Status":
			echo (" - HTTP Status\n");

			$current_status=HTTPstatus($_host);

			RememberStatus();

			if ($old_status!=$current_status) {
				$_message="Website ".$_host. " HTTP status changed to ".$current_status.".";
				echo ("<br> ".$_message."\n");
				SendPushover($_token, $_user, $_message, $_device, $_title, $_url, $_url_title, $_priority, $_sound);
			}
			else echo ("<br> The same old story\n");
			break;

//  HTTP Content
//----------------
		case "HTTP_Content":
			echo (" - Keyword checking.\n");

			$current_status=KeywordCheck($_host,$_string);

			RememberStatus();

			if ($old_status!=$current_status) { 
				if ($current_status=="Missing") $_message="Website ".$_host." does not contain  \"".$_string.".\" keyword.";
				else $_message="Website ".$_host." does contain \"".$_string."\" keyword.";
				echo ("<br> ".$_message."\n");
				SendPushover($_token, $_user, $_message, $_device, $_title, $_url, $_url_title, $_priority, $_sound);
			}
			else echo ("<br> The same old story\n");
			break;

//  IP Address
//--------------
		case "IP_Address":
			echo (" - IP Address checking.\n");

			$current_status=gethostbyname($_host);

			RememberStatus();

			if ($old_status!=$current_status) { 
				$_message="Host ".$_host." IP changed to ".$current_status.".";
				echo ("<br> ".$_message."\n");
				SendPushover($_token, $_user, $_message, $_device, $_title, $_url, $_url_title, $_priority, $_sound);
			}
			else echo ("<br> The same old story\n");
			break;

//  SNMP Get
//------------
		case "SNMP_Get":
			echo (" - SNMP Checking.\n");

			$current_status=mysnmpget($_host, $_string, $_objectid, $_time);

			RememberStatus();

			if ($old_status!=$current_status) { 
				if ($current_status=="!Error!") $_message="Read SMTP velue returned an error";
				else $_message=$_host." Read of the SMTP value \"".$_string."\" from the object ".$_objectid." returned ".$current_status.".";
				echo ("<br> ".$_message."\n");
				SendPushover($_token, $_user, $_message, $_device, $_title, $_url, $_url_title, $_priority, $_sound);
			}
			else echo ("<br> The same old story\n");
			break;

//  Unknkwn action
//------------------

    default:					// Used action name is not speciffied in the code.
		echo " - No Action?!?\n";

	}
}
?> 
