<?php 
ini_set("display_errors",0);
session_start();
include("config.php");
include("helpers/locale.php");
function dbConnect()
{
	global$mysqli;
	global$dbHost;
	global$dbUser;
	global$dbPass;
	global$dbName;
	global$dbPort;

	if(isset($dbPort))
		$mysqli=new mysqli($dbHost,$dbUser,$dbPass,$dbName,$dbPort);
	else
		$mysqli=new mysqli($dbHost,$dbUser,$dbPass,$dbName);
	
	if($mysqli->connect_error)
	{
		fail('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"/><link rel="Shortcut Icon" type="image/ico" href="/img/favicon.png"><title>"._("Can\'t connect to database")."</title></head><style type="text/css">body{background: #ffffff;font-family: Helvetica, Arial;}#wrapper{background: #f2f2f2;width: 300px;height: 130px;margin: -140px 0 0 -150px;position: absolute;top: 50%;left: 50%;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;}p{text-align: center;line-height: 18px;font-size: 12px;padding: 0 30px;}h2{font-weight: normal;text-align: center;font-size: 20px;}a{color: #000;}a:hover{text-decoration: none;}</style><body><div id="wrapper"><p><h2>"._("Can\'t connect to database")."</h2></p><p>"._("There is a problem connecting to the database. Please try again later or see this <a href="http://anonym.to/https://sendy.co/troubleshooting#cannot-connect-to-database" target="_blank">troubleshooting tip</a>.")."</p></div></body></html>');
	}

	global $charset;
	mysqli_set_charset($mysqli,isset($charset)?$charset:"utf8");
	return $mysqli;
}

function fail($errorMsg)
{
	echo$errorMsg;
	exit;
}

dbConnect();
$q="SELECT COUNT(*)
FROM information_schema.tables WHERE table_schema = '$dbName' 
AND (table_name = 'apps' OR table_name = 'campaigns' OR table_name = 'links' OR table_name = 'lists' OR table_name = 'login' OR table_name = 'subscribers')";

$r=mysqli_query($mysqli,$q);
if($r)
{
	while($row=mysqli_fetch_array($r))
	{
		$table_count=$row["COUNT(*)"];
		
		if($table_count!=6)
		{
			if(currentPage()!="_install.php")
			{
				if(get_app_info("path")=="http://your_sendy_installation_url")
				{
					fail('<!DOCTYPE html><html><head><meta http-equiv="Content-Type" content="text/html;charset=utf-8"/><link rel="Shortcut Icon" type="image/ico" href="/img/favicon.png"><title>"._("APP_PATH not set")."</title></head><style type="text/css">body{background: #ffffff;font-family: Helvetica, Arial;}#wrapper{background: #f2f2f2;width: 300px;height: 130px;margin: -140px 0 0 -150px;position: absolute;top: 50%;left: 50%;-webkit-border-radius: 5px;-moz-border-radius: 5px;border-radius: 5px;}p{text-align: center;line-height: 18px;font-size: 12px;padding: 0 30px;}h2{font-weight: normal;text-align: center;font-size: 20px;}a{color: #000;}a:hover{text-decoration: none;}</style><body><div id="wrapper"><p><h2>"._("APP_PATH not set")."</h2></p><p>"._("Please set your APP_PATH in /includes/config.php to your Sendy installation URL.")."</p></div></body></html>');
				}
				else 
					header("Location: ".get_app_info("path")."/_install.php");
				exit;
			}
		}
	}
}

include("update.php");
$_SESSION["company"]="";
$_SESSION["is_sub_user"]="";

function unlog_session()
{
	session_destroy();
	if(setcookie("logged_in","",time()-60000,"/",COOKIE_DOMAIN))
		return true;
}

function currentPage()
{
	$currentFile=$_SERVER["PHP_SELF"];
	$parts=Explode("/",$currentFile);
	return $parts[count($parts)-1];
}

function ipaddress()
{
	if(getenv("HTTP_CLIENT_IP"))
	{
		$ip=getenv("HTTP_CLIENT_IP");
	}
	elseif (getenv("HTTP_X_FORWARDED_FOR"))
	{
		$ip=getenv("HTTP_X_FORWARDED_FOR");
	}
	else
	{
		$ip=getenv("REMOTE_ADDR");
	}
		
	return $ip;
}
	
function str_makerand($minlength,$maxlength,$useupper,$usespecial,$usenumbers)
{
	$key="";
	$charset="abcdefghijklmnopqrstuvwxyz";
	if($useupper)
		$charset.="ABCDEFGHIJKLMNOPQRSTUVWXYZ"; 
	
	if($usenumbers)
		$charset.="0123456789";
	
	if($usespecial)$charset.="~@#$%^*()_+-={}|][";
		
	if($minlength>$maxlength)
		$length=mt_rand($maxlength,$minlength);
	else 
		$length=mt_rand($minlength,$maxlength);
	
	for($i=0;$i<$length;$i++)
		$key.=$charset[(mt_rand(0,(strlen($charset)-1)))];
	return $key;
}

function start_app()
{
	global$mysqli;
	$q="SELECT * FROM login WHERE id = ".$_SESSION["userID"];

	$r=mysqli_query($mysqli,$q);
	
	if($r && mysqli_num_rows($r)>0)
	{
		while($row=mysqli_fetch_array($r))
		{
			$_SESSION["name"]=stripslashes($row["name"]);
			$_SESSION["company"]=stripslashes($row["company"]);
			$_SESSION["email"]=stripslashes($row["username"]);
			$_SESSION["password"]=stripslashes($row["password"]);
			$_SESSION["s3_key"]=stripslashes($row["s3_key"]);
			$_SESSION["s3_secret"]=stripslashes($row["s3_secret"]);
			$_SESSION["license"]=stripslashes(trim($row["license"]));
			$_SESSION["tied_to"]=stripslashes($row["tied_to"]);
			$_SESSION["restricted_to_app"]=stripslashes($row["app"]);
			$_SESSION["timezone"]=stripslashes($row["timezone"]);
			$_SESSION["language"]=stripslashes($row["language"]);
			$_SESSION["cron"]=stripslashes($row["cron"]);
			$_SESSION["send_rate"]=stripslashes($row["send_rate"]);
			$_SESSION["ses_endpoint"]=stripslashes($row["ses_endpoint"]);
			
			if($_SESSION["timezone"]=="")
				$_SESSION["timezone"]=date_default_timezone_get();

			date_default_timezone_set($_SESSION["timezone"]);
			
			if($_SESSION["language"]!="en_US")
				set_locale($_SESSION["language"]);
			
			if($_SESSION["tied_to"]!="")
			{
				$q="SELECT s3_key, s3_secret, license, ses_endpoint FROM login WHERE id = ".$_SESSION["tied_to"];
				$r=mysqli_query($mysqli,$q);
				if($r && mysqli_num_rows($r)>0)
				{
					while($row=mysqli_fetch_array($r))
					{
						$_SESSION["s3_key"]=stripslashes($row["s3_key"]);
						$_SESSION["s3_secret"]=stripslashes($row["s3_secret"]);
						$_SESSION["license"]=stripslashes($row["license"]);
						$_SESSION["ses_endpoint"]=stripslashes($row["ses_endpoint"]);
					}
				}
				$_SESSION["is_sub_user"]=true;
			}
			else
			{
				$_SESSION["is_sub_user"]=false;
				$_SESSION["tied_to"]=$_SESSION["userID"];
			}
		}
	}
			
	$q2="SELECT api_key FROM login ORDER BY id ASC LIMIT 1";
	$r2=mysqli_query($mysqli,$q2);
	if($r2&&mysqli_num_rows($r2)>0)
	{
		while($row=mysqli_fetch_array($r2))
			$_SESSION["api_key"]=$row["api_key"];
	}
	if(!isset($_COOKIE["version"]))
	{
        $version_latest = '2.1.1.3'; 
		if(setcookie("version",$version_latest,time()+86400,"/",COOKIE_DOMAIN))
		{
			$_SESSION["version_latest"]=$version_latest;
		}
	}
	else
	{
		$_SESSION["version_latest"]=$_COOKIE["version"];
	}
			
	if(!defined("CURRENT_VERSION"))
		define("CURRENT_VERSION","2.1.1.3");
	if(isset($_SESSION[$_SESSION["license"]]))
	{
		if($_SESSION[$_SESSION["license"]]!=hash("sha512",$_SESSION["license"]."2ifQ9IppVwYdOgSJoQhKOHAUK/oPwKZy"))
		{
			show_error(_("Invalid license or domain"),"<p>"._('Please refer to this <a href="http://anonym.to/https://sendy.co/troubleshooting#unlicensed-domain-error" target="_blank">troubleshooting tip</a>.')."</p>",false);
			unlog_session();
			exit;
		}
	}
	else
	{
		$license = 'Echo_Team';
		
		if($license=="blocked")
		{
			show_error(_("Outgoing connections blocked"),"<p>"._('Your server has a firewall blocking outgoing connections. Please refer to this <a href="http://anonym.to/https://sendy.co/troubleshooting#unlicensed-domain-error" target="_blank">troubleshooting tip</a>.')."</p>",false);
			exit;
		}
		else if($license == "version error")
		{
			show_error(_("Upgrade your license to 2.x"),"<p>"._('Your Sendy license requires an upgrade to version 2.x. Please visit <a href="http://anonym.to/https://sendy.co/get-updated" target="_blank">http://anonym.to/https://sendy.co/get-updated</a> to purchase an upgrade in order to proceed.')."</p>",false);
			exit;
		}
		else if($license)$_SESSION[$_SESSION["license"]] = hash("sha512",$_SESSION["license"]."2ifQ9IppVwYdOgSJoQhKOHAUK/oPwKZy");
		else
			$_SESSION[$_SESSION["license"]]="";
	}
	
	session_write_close();
}

function parse_date($val,$longshort,$relative=true)
{
	if($relative)
	{
		$diff=time()-$val;
		if($diff<60)
			return $diff." sec".plural($diff)." ago";
		
		$diff=round($diff/60);
		if($diff<60)
			return $diff." min".plural($diff)." ago";
		
		$diff=round($diff/60);
		if($diff<24)
			return $diff." hr".plural($diff)." ago";
		
		$diff=round($diff/24);
		if($diff<7)
			return $diff." day".plural($diff)." ago";
		
		$diff=round($diff/7);
		if($diff<4)
			return $diff." week".plural($diff)." ago";
	}
	
	if($longshort=="long")
		return strftime("%a, %b %d, %Y, %I:%M%p",$val);
	else if($longshort=="short")
		return strftime("%a, %b %d, %I:%M%p",$val);
}

function plural($num)
{
	if($num!=1)
		return"s";
}

function company_name()
{
	global $mysqli;
	$q="SELECT company FROM login LIMIT 1";
	$r=mysqli_query($mysqli,$q);
	if($r)
	{
		while($row=mysqli_fetch_array($r))
		{
			return $company=$row["company"];
		}
	}
	else
	{
		return"Echo Team";
	}
}

function file_get_contents_curl($url)
{
	$ch=curl_init();
	curl_setopt($ch,CURLOPT_HEADER,0);
	curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);
	curl_setopt($ch,CURLOPT_URL,$url);
	curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
	curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
	$data=curl_exec($ch);
	$response_code=curl_getinfo($ch,CURLINFO_HTTP_CODE);
	curl_close($ch);
	if($response_code!=200)
		return"blocked";
	else 
		return $data;
}

function get_gravatar($email,$s=80,$d='mm',$r='g',$img=false,$atts=array())
{
	$url = "https://www.gravatar.com/avatar/";
	$url.= md5(strtolower(trim($email)));
	$url.= "?s=$s&d=$d&r=$r";
	if ($img) 
	{
		$url = "<img src=\"" . $url . "\"";
		foreach ($atts as $key => $val) 
		$url.= " " . $key . "=\"" . $val . "\"";
		$url.= " />";
	}
	return $url;
}

function ran_string($minlength, $maxlength, $useupper, $usespecial, $usenumbers) 
{
	$key = "";
	$charset = "abcdefghijklmnopqrstuvwxyz";
	if ($useupper) $charset.= "ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	if ($usenumbers) $charset.= "0123456789";
	if ($usespecial) $charset.= "~@#\$%^*()_+-={}|][";
	if ($minlength > $maxlength) $length = mt_rand($maxlength, $minlength);
	else
	{
		$length = mt_rand($minlength, $maxlength);
	}
	for ($i = 0;$i < $length;$i++) $key.= $charset[(mt_rand(0, (strlen($charset) - 1))) ];
	return $key;
}

function delete_between($beginning, $end, $string) 
{
	$beginningPos = strpos($string, $beginning);
	$endPos = strpos($string, $end);
	if ($beginningPos === false || $endPos === false) return $string;
	$textToDelete = substr($string, $beginningPos, ($endPos + strlen($end)) - $beginningPos);
	return str_replace($textToDelete, "", $string);
}

function show_error($title, $msg_html, $back = true) 
{
	echo '<!DOCTYPE html><html><head> <meta http-equiv="Content-Type" content="text/html;charset=utf-8" /> <link rel="Shortcut Icon" type="image/ico" href="/img/favicon.png"> <title>$title</title></head><style type="text/css"> body { background: #ffffff; font-family: Helvetica, Arial; } #wrapper { background: #f2f2f2; width: 360px; height: auto; margin: -250px 0 0 -180px; padding-bottom: 10px; position: absolute; top: 50%; left: 50%; -webkit-border-radius: 5px; -moz-border-radius: 5px; border-radius: 5px; } p { text-align: center; line-height: 18px; font-size: 12px; padding: 0 30px; } h2 { font-weight: normal; text-align: center; font-size: 20px; } a { color: #000; text-decoration:underline; } a:hover { text-decoration: none; }</style><body> <div id="wrapper"> <p> <h2>$title</h2> </p> $msg_html ';
	
	if ($back) 
		echo '<p><a href="javascript:window.history.back();" style="text-decoration:none;color:#4371ab;">&larr; Back</a></p>';
	
	echo "</div></body></html>";
}

function get_app_info($v)
{
	switch($v)
	{
		case "version":
			return CURRENT_VERSION;
			break;
		case "version_latest":
			if(isset($_SESSION["version_latest"]))
				return $_SESSION["version_latest"];
			else 
				return;
			break;
		case "cookie_domain":
			return COOKIE_DOMAIN;
			break;
		case "path":
			return APP_PATH;
			break;
		case "s3_key":
			if(isset($_SESSION["s3_key"]))
				return $_SESSION["s3_key"];
			else
				return;
			break;
		case"s3_secret":
			if(isset($_SESSION["s3_secret"]))
				return $_SESSION["s3_secret"];
			else 
				return;
			break;
		case "app":
			if(isset($_GET["i"]) && is_numeric($_GET["i"]))
				return $_GET["i"];
			else 
				echo '<script type="text/javascript">window.location = "".APP_PATH."/logout";</script>';
			break;
		case "userID":
			if(isset($_SESSION["userID"]))
				return $_SESSION["userID"];
			else 
				return;
			break;
		case "name":
			if(isset($_SESSION["name"]))
				return $_SESSION["name"];
			else 
				return;
			break;
		case "company":
			if(isset($_SESSION["company"]))
				$co=$_SESSION["company"];
			else
				$co="";
			if($co=="")
				return company_name();
			else 
				return $co;
			break;
		case "email":
			if(isset($_SESSION["email"]))
				return $_SESSION["email"];
			else 
				return;
			break;
		case "password":
			if(isset($_SESSION["password"]))
				return $_SESSION["password"];
			else 
				return;
			break;
		case "api_key":
			if(isset($_SESSION["api_key"]))
				return $_SESSION["api_key"];
			else 
				return;
			break;
		case "license":
			if(isset($_SESSION["license"]))
				return $_SESSION["license"];
			else 
				return;
			break;
		case "is_sub_user":
			if(isset($_SESSION["is_sub_user"]))
				return $_SESSION["is_sub_user"];
			else 
				return;
			break;
		case "main_userID":
			if(isset($_SESSION["tied_to"]))
				return $_SESSION["tied_to"];
			else 
				return;
			break;
		case "restricted_to_app":
			if(isset($_SESSION["restricted_to_app"]))
				return $_SESSION["restricted_to_app"];
			else 
				return;
			break;
		case "timezone":
			if(isset($_SESSION["timezone"]))
				return	$_SESSION["timezone"];
			else 
				return;
			break;
		case "language":
			if(isset($_SESSION["language"]))
				return $_SESSION["language"];
			else 
				return;
			break;
		case "cron_sending":
			if($_SESSION["cron"]==1)
				return true;
			else 
				return false;
			break;
		case "send_rate":
			if(isset($_SESSION["send_rate"]))
				return $_SESSION["send_rate"];
			else 
				return;
			break;
		case "ses_endpoint":
			if(isset($_SESSION["ses_endpoint"]))
				return $_SESSION["ses_endpoint"];
			else 
				return;
			break;
		case "ses_region":
			if(isset($_SESSION["ses_endpoint"]) && $_SESSION["ses_endpoint"]=="email.us-east-1.amazonaws.com")
				return "N. Virginia";
			else if(isset($_SESSION["ses_endpoint"]) && $_SESSION["ses_endpoint"]=="email.us-west-2.amazonaws.com")
				return "Oregon";
			else if(isset($_SESSION["ses_endpoint"]) && $_SESSION["ses_endpoint"]=="email.eu-west-1.amazonaws.com")
				return "Ireland";
			else if(!isset($_SESSION["ses_endpoint"]))
				return "No value";
			else 
				return;
			break;
	}
}

?>