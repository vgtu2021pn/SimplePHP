* Simple App

Includes:
db.sql - Backup of MySQL database "company"
index.php - Basic Authentication Page
homepage.php - Authorized User Page

* Session Manager
~~~~~~~~~~~~~~~

This allows you to abstract Session management, particularly useful if you want to do secure your sessions by using a database or extend this class to do even more interesting things! 

Usage:

// Initialize the session, and use php's standard session management
Session::init(false);

// ... or, initialize the session, and use the a database to store the session data
Session::init(true);

// If using a database to store the sessions, then you need to have a table
// with the following fields:
// id varchar(255) {primary key}
// access datetime
// data text

// If using a database to store the system errors, then you need to have a table
// with the following fields:
//
// log_time datetime {}
// no mediumint
// message text
// file varchar(255)
// line mediumint;

// If using a database to store the users, then you need to have a table
// with the following fields:
//
// id int {primary key}
// username char(100)
// password char(100)
// nickname varchar(125)
// status tinyint;

// Set a session variable
Session::set('session_variable', 5);

// Get a session variable
$val = Session::get('session_variable');

// Clear a session variable
Session::clear('session_variable');

// Check to see if a session variable exists
if (Session::exists('session_variable')){
	Logger::debug("Session variable exists!");
}


* Database Manager
~~~~~~~~~~~~~~~~

This allows you to abstract database management, it is inspired by the wordpress database class but uses a static class to hold the database object rather than passing a database object around. Since version 2.1, this class
can handle master and slave connections. If any slave hosts are set then the class will open one single slave connection chosen at random from all of the slave hosts that are defined. This slave will be used 
for all reads for this session. 

Usage:

// Set your database credentials, I typically do this in a file called 'settings.php.sticky' 
// as this is server-specific and I don't include this file in the repo. You can also
// just add the credentials into the DatabaseManager class and dispense with these defines.
define("database_user", "dbuser");
define("database_pass", "dbpass");
define("database_name", "dbname");
define("database_host", "localhost");
define("database_verbose", false); // If set to true, the database will log activity using the Logger
define("slave_host_1", "somehost"); // If set, defines a slave database to use for all reads
define("slave_host_1", "somehost"); // If set, defines a slave database to use for all reads

// Though you don't need to setup or explicitly create the connection (the class will check for connection and connect if 
// needed when you call any of its methods.
DatabaseManager::connect();

// Prepare a sql statement
$sql = DatabaseManager::prepare("SELECT id FROM sometable WHERE textField = %s AND numericField = %d",  $string, $number ); 		

// Get a single variable
$id = DatabaseManager::getVar("SELECT id FROM sometable WHERE textField = %s AND numericField = %d",  $string, $numbe);		

// Get a result returned in a associative array
$data = DatabaseManager::getResults("SELECT * FROM sometable WHERE textField = %s AND numericField = %d",  $string, $number);		

// Get a single result returned in a associative array
$data = DatabaseManager::getSingleResult("SELECT * FROM sometable WHERE textField = %s AND numericField = %d",  $string, $number);		

// Get a single column, returned in an array
$data = DatabaseManager::getColumn("SELECT val FROM sometable WHERE textField = %s AND numericField = %d",  $string, $number);		

// Do a insert, and get the inserted row id
$id = DatabaseManager::insert($sql);		

// Do an update
DatabaseManager::update($sql);		

// Do a generic sql query and return the result object
DatabaseManager::submitQuery($sql);		

// You can manually close the connection
DatabaseManager::close();


* Logger
~~~~~~

The logger class makes php logging simple, and the output includes a stack track indicating the function/method/class and line number of the last 2 calls on the stack. Handy for tracking down bugs! You can also have the 
Logger echo the log to HTML, which gives a nice color coded display of the log to the browser.

Usage:

// Tell the logger to catch system errors
Logger::catchSysErrors();

// Tell the logger to catch system errors and store log into database
Logger::catchSysErrors(true);

// To the logger to echo the log as HTML
Logger::echoLog();

// Set the logger level
Logger::setLevelDebug();
Logger::setLevelInfo();
Logger::setLevelWarning();
Logger::setLevelError();
Logger::setLevelFatal();

// Or set the level using the Logger constants
Logger::setLevel(Logger::$DEBUG);
Logger::setLevel(Logger::$INFO);
Logger::setLevel(Logger::$WARNING);
Logger::setLevel(Logger::$ERROR);
Logger::setLevel(Logger::$FATAL);

// Log a message
Logger::debug("some message");
Logger::warning("some message");
Logger::warn("some message");
Logger::error("some message");
Logger::info("some message");
Logger::fatal("some message");


* Command Manager
~~~~~~~~~~~~

// Validation examples;

// To validate a $POST or $GET para called 'myParaName' that is required and you expect to be numeric call;
$para = CommandHelper::getPara('myParaName', true, CommandHelper::$PARA_TYPE_NUMERIC);

// To validate a $POST or $GET para called 'myParaName' that is required and you expect to be a string call;
$para = CommandHelper::getPara('myParaName', true, CommandHelper::$PARA_TYPE_STRING);

// To validate a $POST or $GET para called 'myParaName' that is NOT required and you expect to be a json encoded object call;
$para = CommandHelper::getPara('myParaName', false, CommandHelper::$PARA_TYPE_JSON);

// To validate a $POST or $GET para called 'myParaName' that is NOT required and you expect to be an array object call;
$para = CommandHelper::getPara('myParaName', false, CommandHelper::$PARA_TYPE_ARRAY);

// If validation fails, the class will send a JSON message in the form
// {"result":"fail","data":"Validation failure: Parameter not set, expecting 'cmd'"}

// An example of sending a message back to the user, from an php object, which is sent as JSON;

$data = array(4);
	
$data['stat1'] = DatabaseManager::getVar("SELECT COUNT(stat1) AS no FROM statTable");					
$data['stat2'] = DatabaseManager::getVar("SELECT COUNT(stat2) AS no FROM statTable");					
$data['stat3'] = DatabaseManager::getVar("SELECT COUNT(stat3) AS no FROM statTable");					
$data['stat4'] = DatabaseManager::getVar("SELECT COUNT(stat4) AS no FROM statTable");					
		
$msg['cmd'] = 'getStats';
$msg['result'] = 'ok';			
$msg['data'] = $data;
				
CommandHelper::sendMessage($msg);	

// Or you can send any string that you'd like
CommandHelper::sendTextMessage("Undefined command!");

// There are also pre-defined error messages that can be sent;

// Check that a user is logged in, and that they have access to this site
if (!SecurityUtils::isLoggedInForSite($site_id)){
	CommandHelper::sendAuthorizationFailMessage("You are not authorized for this site!");	
	die();
}
