<?php

include_once('Session.class.php');
include_once('DatabaseManager.class.php');
include_once('Logger.class.php');
include_once('BrowserDetect.class.php');
include_once('CommandHelper.class.php');

// README -->
Session::init(true);

DatabaseManager::connect();	

Logger::setLevel(Logger::$DEBUG);

Logger::echoLog();

Logger::catchSysErrors(true);

CommandHelper::init();
// <-- README

if (Session::exists('access')) {

$access = Session::get('access');

$html_error = array();
$success = true;
if ($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST["proceed"])) {
      
    // **
    //  * Username
    //  ** 
    
    $string_username = preg_replace("/[^a-z0-9]/i", "", $_POST["username"]);
    //for other letters would be possible to use function mb_eregi_replace($pattern, $replacement, $string);
    //for other way would be possibe to use function filter_var();
    if(strlen($string_username) < 3) {
    	$success = false;
    	$html_error[] = 'Too short in numbers of correct letters of the Username.';
    }
    $process_username = DatabaseManager::make_sql_safe(trim($string_username));
    
    $data = DatabaseManager::getSingleResult("SELECT id, nickname, status FROM lza_users WHERE username = %s;", $process_username );
    
    if(is_array($data) && !empty($data)) {
    	$success = false;
    	$html_error[] = 'Sorry, the Username already exists, contact us, if something wrong.';
    }
    
    unset($data);
    
    //  **
    //  * Password
    // ** 
    
    $string_password = $_POST["password"];
    
    if(empty($string_password)) {
	$today = getdate();
	$today_year = settype($today['year'], 'string');
    	$string_password = $string_username.$today_year;
    }
    
    if(strlen($string_password) < 6) {
    	//$success = false;//recommendation only
    	$html_error[] = 'Length of the Password is less than six (6) values. The Recommendation would be to revise it after some time.';
    }
    
    $process_password = DatabaseManager::make_sql_safe(md5(trim($string_password)));
    
    // **
    //  * Nickname
    //  ** 
    
    $string_nickname = $_POST["nickname"];
    
    if(empty($string_nickname)) {
    	$string_nickname = $string_username;
    }
    
    $process_nickname = DatabaseManager::make_sql_safe(trim($string_nickname));
    
    $data = DatabaseManager::getSingleResult("SELECT id, nickname, status FROM lza_users WHERE nickname = %s;", $process_nickname );
    
if(is_array($data) && !empty($data)) {
    	//$success = false;//recommendation only
    	$html_error[] = 'This Name is already in use. Misunderstanding can\'t be avoided.';
    }
    
    unset($data);
     
    //  **
    //  * Policy
    // ** 
if(!isset($_POST["policy"]) && empty($_POST["policy"])) {
	$success = false;
	$html_error[] = 'The User should not be created, if he does not accept Security and Privacy Policies of the Information System. If Security Policy is not defined, then communicate with Security Authority of this Information System (if there are no such entity, then Owner of the Information System is directly responsible). If Privacy Policy is not defined, then communicate with Data Protection Authority of this Information System (if there are no such entity, then Owner of the Information System is directly responsible).';
    }
    //  **
    //  * Push Username, Password and Nickname to the database.
    //  * Requirements. @Var success eq to TRUE
    // ** 
    
    if($success == true) {
    	$process_status = 1; //by default
    
    	// Prepare a sql statement
    	$sql = DatabaseManager::prepare("INSERT INTO lza_users (id, username, password, nickname, status) VALUES (null, %s, %s, %s, %d);", $process_username, $process_password, $process_nickname, $process_status );

    	// Do a insert, and get the inserted row id
    	$id = DatabaseManager::insert($sql);
    }
}

$user = ucfirst($access['user']);
echo <<<EOT
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta http-equiv="cache-control" content="no-cache">
<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
<title>Title Page - {$user} Homepage</title>
<style>
table {
font-family: arial, sans-serif;
border-collapse: collapse;
width: 100%;
}
td, th {
border: 1px solid #dddddd;
text-align: left;
padding: 8px;
}
tr:nth-child(even) {
background-color: #dddddd;
}
#required {
color: red;
}
.line-up {
position: relative;
float: left;
display: inline-block;
padding-left: 20px;
}
.clear {
clear: both;
}
.err {
color: #191970;
font-weight: 600;
}
pre {
white-space: pre-line;
}
a:hover {
color: #191970;
}
i {
padding-right: 10px;
padding-left: 10px; 
border-style: solid;
border-width: .5px;
}
</style>
</head>
<body>
<div>
<h1>Homepage Panel</h1>
<div class="line-up">
<i class="glyphicon glyphicon-home"></i>
<a href="homepage.php" target="_self">{$user}</a> is logged in.
</div>
<div class="line-up">
<i class="glyphicon glyphicon-log-out"></i>
<a href="index.php?logout=1" target="_self">Logout</a>
</div>
<div class="clear">
&nbsp;
</div>
</div>
EOT;

//to test out session variables
//Session::set('session_variable', '1');
//$val = Session::get('session_variable');

//list of employees and their data
echo <<<EOT
<table style="width:100%">
<caption>List of employees in employees table</caption>
<tr>
<th>&nbsp;</th>
<th>No</th>
<th>Name</th>
<th>Job</th>
<th>Manager</th>
<th>Hiredate</th>
<th>Salary</th>
<th>Commission</th>
</tr>
EOT;

$data = DatabaseManager::getResults("SELECT * FROM employees;");

if(!is_array($data) || empty($data)) {
echo <<<EOT
<tr>
<td colspan=8>No data.</td>
</tr>
EOT;
		
}
else {

$length = count($data);
for ($i = 0; $i < $length; $i++) {

$j = $i+1;

echo <<<EOT
<tr>
<td>{$j}</td>
<td>{$data[$i]['empno']}</td>
<td>{$data[$i]['name']}</td>
<td>{$data[$i]['job']}</td>
<td>{$data[$i]['manager']}</td>
<td>{$data[$i]['hiredate']}</td>
<td>{$data[$i]['salary']}</td>
<td>{$data[$i]['commission']}</td>
</tr>
EOT;

}

}

echo <<<EOT
</table>

<table style="width:100%">
<caption>List of registered accounts in users table</caption>
<tr>
<th>&nbsp;</th>
<th>Username</th>
<th>Password</th>
<th>Name</th>
<th>Status</th>
</tr>
EOT;

unset($data);
//list of accounts and their users
$data = DatabaseManager::getResults("SELECT * FROM lza_users;");

if(!is_array($data) || empty($data)) {
echo <<<EOT
<tr>
<td colspan=5>No data.</td>
</tr>
EOT;
		
}
else {

$length = count($data);
for ($i = 0; $i < $length; $i++) {

$id = $data[$i]['id'];
$nickname = ucfirst($data[$i]['nickname']);
$username = substr($data[$i]['username'],0,2);
$status = settype($data[$i]['status'], 'integer');
$status = ($status == 1)? 'Activated':'Deactivated';
echo <<<EOT
<tr>
<td>{$id}</td>
<td>{$username}*</td>
<td>***</td>
<td>{$nickname}</td>
<td>{$status}</td>
</tr>
EOT;

}

}

echo <<<EOT
</table>
EOT;

//creation of new user
echo <<<EOT
<form action="homepage.php" method="post">
<div class="admin-box">
<h1>User Creation</h1>
<table style="width:100%">
<tr>
<td><span id="required">&#42;</span> Username of the Account</td>
<td><input type="text" name="username" value="" /></td>
<td>(accepting at minimum 6 (six) upper and lowercase Latin/English letters, and numbers only)</td>
</tr>
<tr>
<td>Password of the Account</td>
<td><input type="password" name="password" autocomplete="off" value="" /></td>
<td>(the same as concatinated string from Username and Year value(s), if empty)</td>
</tr>
<tr>
<td>Identity Name of the User</td>
<td><input type="text" name="nickname" value="" /></td>
<td>(the same as Username, if empty)</td>
</tr>
<tr>
<td><span id="required">&#42;</span> Accepting Security, Cybersecurity and Privacy Policies of the Information System</td>
<td><input type="checkbox" name="policy" /></td>
<td>(if checked, then the User has to be instructed in (Cyber) Security and Privacy Policies accordingly, which are related to this Information System for the first time and each year until the User Account is Active.)</td>
</tr>
EOT;

if (is_array($html_error) && !empty($html_error)) {

$html_output = '';
foreach ($html_error as $value) {
	$html_output .= $value . " \n\n";
}

echo <<<EOT
<tr>
<td class="err">Error:</td>
<td><pre>{$html_output}</pre></td>
<td>&nbsp;</td>
</tr>
EOT;

unset($html_error);
unset($html_output);
}

echo <<<EOT
<tr>
<td>&nbsp;</td>
<td><input class="button" type="submit" name="proceed" value="Create User" /></td>
<td>&nbsp;</td>
</tr>
</table>
</div>
</form>






EOT;
 	
echo <<<EOT
</body>
</html>
EOT;

}
else {
header("Location: index.php?response=Back to login page.");
}

DatabaseManager::close();

?>
