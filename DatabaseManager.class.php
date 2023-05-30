<?php
date_default_timezone_set('UTC');
/**
* *******************************************************************
*
* Copyright (C) 2011 by Ad Astra Systems, LLC
* 
* Permission is hereby granted, free of charge, to any person obtaining a copy
* of this software and associated documentation files (the "Software"), to deal
* in the Software without restriction, including without limitation the rights
* to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
* copies of the Software, and to permit persons to whom the Software is
* furnished to do so, subject to the following conditions:
* 
* The above copyright notice and this permission notice shall be included in
* all copies or substantial portions of the Software.
* 
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
* IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
* FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
* AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
* LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
* OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
* THE SOFTWARE.
* 
* *******************************************************************
*
* Class to handle and abstract interations to database
*
* @author Mike Pritchard (mike@adastrasystems.com)
* @since July 6th, 2006
* @version 2.1
*/
class DatabaseManager {

    /** URL of MySQL server */
    private static $databaseURL = "localhost";
    /** Name of MySQL database */
    private static $databaseName = "company";
    /** Username of user with admin rights to database */
    private static $username = "company";
    /** Password of user with admin rights to database */
    private static $password = "company";
    /** verbose flag, if true debug data is dumped */
    private static $verbose = false;
    /**
     * Array of connections, we only store a connections to the master DB and
     * and a single random slave to use for this session
     */
    private static $connections = null;
    /** Maximum number of slaves */
    public static $MAX_NO_SLAVES = 10;
    /** Store the currently used connection */
    private static $currentCon = null;

    // //////////////////////////////////////////////////////////////////////////////////////

    public static function setUsername($newVal) {
        self::$username = $newVal;
    }

    public static function setPassword($newVal) {
        self::$password = $newVal;
    }

    public static function setURL($newVal) {
        self::$databaseURL = $newVal;
    }

    public static function setDatabaseName($newVal) {
        self::$databaseName = $newVal;
    }

    public static function setVerbose($newVal) {
        self::$verbose = $newVal;
    }

    public static function getStatus() {
        return (self::$currentCon !== NULL)?1:0;
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Connects to the MySQL database with the parameters defined in the
     * admin class (settings.php)
     *
     * @param oSettings Site-specific paramaters, such as username, password, server etc.
     * @see settings.php
     */
    public static function connect() {

        $slaveDBs = array();
        $noSlaves = 0;

        if (self::$username == "") {

            if (defined('database_user')) {
                //Logger::debug("DB Username is not set, but found credentials stored in Session");
                self::$username = database_user;
                self::$password = database_pass;
                self::$databaseName = database_name;
                self::$verbose = database_verbose;
                self::$databaseURL = database_host;
                for ($i = 0; $i < self::$MAX_NO_SLAVES; $i++) {
                    if (defined('slave_host_' . $i))
                        eval('$slaveDBs[] = slave_host_' . $i . ';');
                }
                $noSlaves = count($slaveDBs);
            }
            else {
                Logger::fatal("DB Username is not set, and could not find credentials in Session!!!");
            }
        }

        self::$connections = array();
        $hosts = array();

        // Attempt to connect to master DB....
        self::$connections['master'] = mysqli_connect(self::$databaseURL, self::$username, self::$password);
        $hosts['master'] = self::$databaseURL;

        // Connect to a single random slave, to use for this session
        if ($noSlaves > 0) {
            $host = $slaveDBs[mt_rand(0, $noSlaves - 1)];
            self::$connections['slave'] = mysqli_connect($host, self::$username, self::$password);
            $hosts['slave'] = $host;
        }


        // Connect to database
        //for($i=0; $i<count(self::$connection); $i++)
        foreach (self::$connections as $host => $cx) {

            //$cx = self::$connection[$i];
            //$host = ($i == 0) ? $databaseURL . "(Master)" : $slaveDBs[$i-1];

            if ($cx != FALSE) {

                // Select the relevant database.........
                if (!mysqli_select_db($cx, self::$databaseName)) {
                    Logger::fatal("Connection to MySQL database '" . self::$databaseName . "' FAILED for " . $hosts[$host] . " ($host)");
                }
                //else {
                //	Logger::debug("Connected to MySQL database '".self::$databaseName."' OK for " . $hosts[$host] . " ($host)");
                //}
            } else {
                Logger::fatal("Connection to MySQL database '$host' failed with username " . self::$username . "!");
            }
        }
        // By default, set connection to the master DB
        self::useMaster();
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Close the connection to the database
     */
    public static function close() {
        // Close the connections
        foreach (self::$connections as $cx) {
            if ($cx)
                @mysqli_close($cx);
        }
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    public static function insert($query) {

	// Prepare
	$query = self::prepareQuery($query, func_get_args());

        self::useMaster();
        $result = self::internalQuery($query);
        return mysqli_insert_id(self::$currentCon);
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    public static function update($query) {

	// Prepare
	$query = self::prepareQuery($query, func_get_args());

        self::useMaster();
        $result = self::internalQuery($query);
        return $result;
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Perform a basic query, this will use the master connection is it can not know what kind
     * of query the user is attempting
     */
    public static function submitQuery($query) {

	// Prepare
	$query = self::prepareQuery($query, func_get_args());

        self::useMaster();
        return self::internalQuery($query);
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    public static function freeResult($query_result) {
        if ($query_result != false || $query_result != NULL) {
            try {
                @mysqli_free_result($query_result);
            } catch (Exception $e) {
                Logger::error('Caught exception: ', $e->getMessage());
            }
        }
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    public static function make_sql_safe($string) {
        if (self::$currentCon == NULL) self::connect();
        return mysqli_real_escape_string(self::$currentCon, $string);
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Performs the given SQL query and returns a single value, if multiple values are recieved it will 
     * simply return the first.
     *
     */
    public static function getVar($sql) {

	// Prepare
	$sql = self::prepareQuery($sql, func_get_args());
        self::useSlave();

        $results = DatabaseManager::internalQuery($sql);

        // Return null if there are no results
        if (!$results) {
            return null;
        }

        $row = mysqli_fetch_array($results, MYSQL_NUM);
        mysqli_free_result($results);

        if (isset($row) && isset($row[0])) {
            return $row[0];
        }

        return null;
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get an array for a single column, i.e. similar to getVar but returns an array of results
     * rather then a single result
     */
    public static function getColumn($sql) {

	// Prepare
	$sql = self::prepareQuery($sql, func_get_args());

        self::useSlave();

        $results = DatabaseManager::internalQuery($sql);

        if (!$results || mysqli_num_rows($results) == 0) {
            return null;
        }

        $data = array();

        // Build the output data
        while ($row = mysqli_fetch_array($results)) {
            $data[] = $row[0];
        }

        mysqli_free_result($results);

        return $data;
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Return the first row as an associative array from a result set, or null if no results found
     */
    public static function getRow($sql) {

	// Prepare
	$sql = self::prepareQuery($sql, func_get_args());

        self::useSlave();

        $results = DatabaseManager::internalQuery($sql);

        if (!$results || mysqli_num_rows($results) == 0) {
            return null;
        }
        $row = mysqli_fetch_assoc($result);
        mysqli_free_result($results);

        return $row;
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get all the results for a query as an associative array, returns null if no results found
     */
    public static function getResults($sql) {
	// Prepare
	$sql = self::prepareQuery($sql, func_get_args());
	self::useSlave();
	$results = DatabaseManager::internalQuery($sql);
	if (!$results || mysqli_num_rows($results) == 0) {
            return null;
        }
	$data = array();
	// Build the output data
        while ($row = mysqli_fetch_assoc($results)) {
            $data[] = $row;
        }
	mysqli_free_result($results);
	return $data;
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Get a single result and return as an asssociative array
     */
    public static function getSingleResult($sql) {

	// Prepare
	$sql = self::prepareQuery($sql, func_get_args());

        self::useSlave();

        $results = DatabaseManager::internalQuery($sql);

        if (!$results || mysqli_num_rows($results) == 0) {
            return null;
        }

        $data = array();

        // Build the output data
        while ($row = mysqli_fetch_assoc($results)) {
            $data[] = $row;
        }

        if (isset($data[0])) {
            $data = $data[0];
        }

        mysqli_free_result($results);

        return $data;
    }

    // //////////////////////////////////////////////////////////////////////////////////////
    //
    // Backwards compatability
    //
    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Prepares a SQL query for safe execution using sprintf style execution, it currently only
     * supports %d (decimal number), %s (string). Both %d and %s should be left unquoted in the
     * query string.
     *
     * <code>
     * DatabaseManager::prepare( "SELECT * FROM someTable WHERE 'someField' = %s AND 'anotherField' = %d", "a string", 43346 )
     * </code>
     *
     * @param string $query Query statement with sprintf()-like placeholders
     * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like {@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if being called like {@link http://php.net/sprintf sprintf()}.
     * @param mixed $args,... further variables to substitute into the query's placeholders if being called like {@link http://php.net/sprintf sprintf()}.
     * @return null|string Sanitized query string
     */
    public static function prepare($query) { // ( $query, *$args )
        $args = func_get_args();

        array_shift($args);

        // If args were passed as an array (as in vsprintf), move them up
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        $query = str_replace("'%s'", '%s', $query); // in case someone mistakenly already singlequoted it
        $query = str_replace('"%s"', '%s', $query); // doublequote unquoting
        $query = str_replace('%s', "'%s'", $query); // quote the strings

        for ($i = 0; $i < count($args); $i++) {
	        if (self::$currentCon == NULL) {
			$datetime = new DateTime('now', new DateTimeZone('Europe/Athens'));
			$date = new IntlDateFormatter(
                        'en_US',
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::FULL,
                        'Europe/Athens',
                        IntlDateFormatter::GREGORIAN,
                        'YYYY-MM-dd'
                	);
                	$now = $date->format($datetime);
			Logger::debug("Null connections " . $now);
	        	self::connect();
	        }
            $args[$i] = mysqli_real_escape_string(self::$currentCon, $args[$i]);
        }

        //array_walk($args, array(&$this, 'mysql_real_escape_string'));

        return @vsprintf($query, $args);
    }

    // //////////////////////////////////////////////////////////////////////////////////////
    //
    // Private methods
    //
    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Prepares a SQL query for safe execution using sprintf style execution, it currently only
     * supports %d (decimal number), %s (string). Both %d and %s should be left unquoted in the
     * query string.
     *
     * <code>
     * DatabaseManager::prepare( "SELECT * FROM someTable WHERE 'someField' = %s AND 'anotherField' = %d", "a string", 43346 )
     * </code>
     *
     * This function was borrowed from and inspired by the Wordpress implementation.
     *
     * @param string $query Query statement with sprintf()-like placeholders
     * @param array|mixed $args The array of variables to substitute into the query's placeholders if being called like {@link http://php.net/vsprintf vsprintf()}, or the first variable to substitute into the query's placeholders if being called like {@link http://php.net/sprintf sprintf()}.
     * @param mixed $args,... further variables to substitute into the query's placeholders if being called like {@link http://php.net/sprintf sprintf()}.
     * @return null|string Sanitized query string
     */
    private static function prepareQuery($query, $args) { // ( $query, *$args )
	//$args = func_get_args();

        array_shift($args);

        // If args were passed as an array (as in vsprintf), move them up
        if (isset($args[0]) && is_array($args[0])) {
            $args = $args[0];
        }

        $query = str_replace("'%s'", '%s', $query); // in case someone mistakenly already singlequoted it
        $query = str_replace('"%s"', '%s', $query); // doublequote unquoting
        $query = str_replace('%s', "'%s'", $query); // quote the strings

        for ($i = 0; $i < count($args); $i++) {
	        if (self::$currentCon == NULL) {
			$datetime = new DateTime('now', new DateTimeZone('Europe/Athens'));
			$date = new IntlDateFormatter(
                        'en_US',
                        IntlDateFormatter::FULL,
                        IntlDateFormatter::FULL,
                        'Europe/Athens',
                        IntlDateFormatter::GREGORIAN,
                        'YYYY-MM-dd'
                	);
                	$now = $date->format($datetime);
	        	Logger::debug("Null connections " . $now);
	        	self::connect();
	        }
            $args[$i] = mysqli_real_escape_string(self::$currentCon, $args[$i]);
        }

        //array_walk($args, array(&$this, 'mysql_real_escape_string'));

        return @vsprintf($query, $args);
    }

    // //////////////////////////////////////////////////////////////////////////////////////
    
    /**
     * Perform a basic query using the current connection
     */
    private static function internalQuery($query) {

        // Check to see if connection has been opened, if not connect to database
        if (self::$currentCon == NULL)
            self::connect();

        if (self::$verbose == true) {
            Logger::debug($query);
        }

        // Submit query....
        $result = mysqli_query(self::$currentCon, $query);

        // Handle result.....
        if (!$result) {
            Logger::fatal("Database query error = " . mysqli_error(self::$currentCon) . " Query = [$query] ");
        }
        //else
        //	Logger::debug("Database Query = [$query] ");

        return $result;
    }
    
    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Use the slave connection, if not available or set use the master DB instead
     */
    private static function useSlave() {
        if (isset(self::$connections['slave'])) {
            self::$currentCon = self::$connections['slave'];
        } else {
            self::useMaster();
        }
    }

    // //////////////////////////////////////////////////////////////////////////////////////

    /** Use the master connections */
    private static function useMaster() {
        if (isset(self::$connections['master'])) {
		self::$currentCon = self::$connections['master'];
	}
	if (self::$currentCon == NULL) {
		$datetime = new DateTime('now', new DateTimeZone('Europe/Athens'));
		$date = new IntlDateFormatter(
			'en_US',
			IntlDateFormatter::FULL,
			IntlDateFormatter::FULL,
			'Europe/Athens',
			IntlDateFormatter::GREGORIAN,
			'YYYY-MM-dd'
		);
		$now = $date->format($datetime);
		Logger::debug("Null connection " . $now);
	}
    }

    public static function logMessage($no, $message, $file, $line) {
	if (self::$currentCon == NULL)
            self::connect();

	if(Session::dbFlagStarted()) {
		$no = (int)$no;
		$message = mysqli_real_escape_string(self::$currentCon, $message);
		$file = mysqli_real_escape_string(self::$currentCon, $file);
		$line = (int)$line;
	 	return DatabaseManager::submitQuery("INSERT INTO lza_log (log_time, no, message, file, line) VALUES (NOW(), '$no', '$message', '$file', '$line')");
 	}
 	else {
 		throw new Exception("Database usage flag in the Session class is not set.");
 	}
    }
}

?>
