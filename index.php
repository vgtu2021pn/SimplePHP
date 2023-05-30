<?php

include_once('Session.class.php');
include_once('DatabaseManager.class.php');
include_once('Logger.class.php');
include_once('Browser.class.php');
include_once('CommandHelper.class.php');

// README -->
Session::init(true);

DatabaseManager::connect();

Logger::setLevel(Logger::$DEBUG);

Logger::echoLog();

Logger::catchSysErrors(true);

CommandHelper::init();
// <-- README

$html_browser = '';
$browser = new Browser();
if( $browser->getBrowser() == Browser::BROWSER_FIREFOX && $browser->getVersion() >= 2 ) {
	$html_browser = 'You have FireFox version 2 or greater.';
}
else {
	$html_browser = 'You don\'t have FireFox version 2 or greater.';
}

$html_response = '';
if ($_SERVER["REQUEST_METHOD"] == "POST" and isset($_POST["login"])) {
	$string_username = DatabaseManager::make_sql_safe(trim($_POST["username"]));
	$string_password = DatabaseManager::make_sql_safe(md5(trim($_POST["password"])));
	$data = DatabaseManager::getSingleResult("SELECT id, nickname, status FROM lza_users WHERE username = %s AND password = %s;", $string_username, $string_password );
	if(is_array($data) && !empty($data)) {
    		$id = $data['id'];
    		Session::logout();
		if($id) Session::recreate($id);
		Session::set('access', array(
			'id' => $id,
			'user' => $data['nickname'],
			'status' => $data['status'],
			'active' => time())
		);
		header("Location: homepage.php?user=".(int)$data['id']);
	}
	else {
		$html_response = "Wrong username or password.";
	}
}

/**
 * Try to change URL address:
 * /index.php?response=hello world
 * /index.php?response=<u>hello world</u>
 **/
$para = CommandHelper::getPara('response', false, CommandHelper::$PARA_TYPE_STRING);

if($para) {
	if (is_array($para)) {
		foreach ($para as $v) {
			$html_response .= htmlentities($v);
			$html_response .= " ";
		}
	} else {
		$html_response = htmlentities($para);
	}
}

unset($para);

$para = CommandHelper::getPara('logout', false, CommandHelper::$PARA_TYPE_NUMERIC);

if($para == 1) {
	$html_response = "Logged out.";
	if (Session::exists('access')) {
		$val = Session::get('access');
		$id = (int)$val['id'];
		unset($val);
	}
	Session::logout();
	if($id) Session::recreate($id);
}

echo <<<EOT
<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">
  <title>Title Page - Login</title>
  <style>
  	body {
	    margin: 0;
	    padding: 0;
	    font-family: sans-serif;
	    background: url() no-repeat;
	    background-size: cover;
	}
	.login-box {
	    width: 280px;
	    position: absolute;
	    top: 50%;
	    left: 50%;
	    transform: translate(-50%, -50%);
	    color: #191970;
	}
	.alert-box {
	    display:block;
	    padding:6px 7px 7px;
	    font-weight:300;
	    font-size:14px;
	    color:#191970;
	    background-color:#E67E22;
	    border:1px solid rgba(0,0,0,0.1);
	    margin-bottom:12px;
	    -webkit-border-radius:3px;
	    -moz-border-radius:3px;
	    -ms-border-radius:3px;
	    -o-border-radius:3px;
	    border-radius:3px;
	    text-shadow:0 -1px rgba(0,0,0,0.3);
	    position:relative
	}
	.login-box h1 {
	    float: left;
	    font-size: 40px;
	    border-bottom: 4px solid #191970;
	    margin-bottom: 50px;
	    padding: 13px;
	}
	.centered {
		float:none;
		margin:0 auto;
	}
	.textbox {
	    width: 100%;
	    overflow: hidden;
	    font-size: 20px;
	    padding: 8px 0;
	    margin: 8px 0;
	    border-bottom: 1px solid #191970;
	}
	.fa {
	    width: px;
	    float: left;
	    text-align: center;
	}
	.textbox input {
	    border: none;
	    outline: none;
	    background: none;
	    font-size: 18px;
	    float: left;
	    margin: 0 10px;
	}
	.button {
	    width: 100%;
	    padding: 8px;
	    color: #ffffff;
	    background: none #191970;
	    border: none;
	    border-radius: 6px;
	    font-size: 18px;
	    cursor: pointer;
	    margin: 12px 0;
	}
  </style>
</head>
<body>
<div class="centered">
	<div class="alert-box">
		<span style="font-weight:600">Note.</span><span style="font-weight:300"> {$html_browser}</span>
		<span style="font-weight:600">Note.</span><span style="font-weight:300"> {$html_response}</span>
	</div>
</div>
<form action="index.php" method="post">
        <div class="login-box">
            <h1>Login</h1>
            <div class="textbox">
                <i class="glyphicon glyphicon-user" aria-hidden="true"></i>
                <input type="text" placeholder="Username"
                         name="username" value="">
            </div>
            <div class="textbox">
                <i class="glyphicon glyphicon-lock" aria-hidden="true"></i>
                <input type="password" placeholder="Password"
                         name="password" autocomplete="off" value="">
            </div>
            <input class="button" type="submit"
                     name="login" value="Sign In">
        </div>
    </form>
</body>
</html>
EOT;

/*
//To trace the errors
try {
	echo (1/5);
	echo "\n";
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
}

try {
	//throw new Exception("Division by zero or so.");
	echo (1/0);
	echo "\n";
} catch (Exception $e) {
	echo $e->getMessage() . "\n";
	//trigger_error("Testing 1/0 ...", E_USER_ERROR);
} finally {
	echo "Finally." . "\n";
}
*/

DatabaseManager::close();

?>
