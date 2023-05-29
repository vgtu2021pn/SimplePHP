<?php
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
* Sesson class. This is a wrapper class that is used to provide
* a generic session interface. The undrlying mechanism can be chosen 
* at run-time (such as a database-based custom session manager, or the
* default (php) session manager).
*
* If using a database to store the sessions, then you need to have a table
* with the following fields:
*
* id varchar(255) {primary key}
* access datetime
* data text
*
* If using a database to store the system errors, then you need to have a table
* with the following fields:
*
* log_time datetime {}
* no mediumint
* message text
* file varchar(255)
* line mediumint;
*
* If using a database to store the users, then you need to have a table
* with the following fields:
*
* id int {primary key}
* username char(100)
* password char(100)
* nickname varchar(125)
* status tinyint;
*
* @author Mike Pritchard (mike@adastrasystems.com)
* @since July 6th, 2006
*/
/**
* post scriptum.
* Ad Astra Systems code were rewriten in about ~60-70 percent grand total and (or) most of their code too old for today's PHP version.
*/
class SysSession implements SessionHandlerInterface {

    private $link;
    
    private $table_name = "lza_sessions";    

    private $databaseURL = "localhost";

    private $databaseName = "company";

    private $username = "company";

    private $password = "company";
   
    /*
        self::connect();
    */
    
    public function __construct() {
    	$savePath = ini_get('session.save_path');
    	$saveHandler = ini_get('session.save_handler');
    	$sessionName = ini_get('session.name');
	$maxlifetime = ini_get('session.gc_maxlifetime');
	$sidLength = ini_get('session.sid_length');
	$sidBitsPerCharacter = ini_get('session.sid_bits_per_character');

    	//Logger::debug("Session $sidLength");
    }
    
    public function open($savePath, $sessionName) {
        $link = mysqli_connect($this->databaseURL, $this->username, $this->password, $this->databaseName);
        if($link) {
            $this->link = $link;
            return true;
        } else {
            return false;
        }
   }
    
   /**
    * These functions are closely related. These are used to 
    * open the session data store and close it, respectively. If you are storing sessions in 
    * the filesystem, these functions open and close files (and you likely need to use a global 
    * variable for the file handler, so that the other session functions can use it).
    */
    
    public function close() {
        mysqli_close($this->link);
        if ($this->link) {
            return false;       	
    	}
        return true;
    }
    
   /**
    * The function is called whenever PHP needs to read the session data. This takes 
    * place immediately after _open(), and both are a direct result of your use of session_start().
    *
    * PHP passes this function the session identifier, as the following example demonstrates:
    */
    /*
        //Logger::debug("Session $session_id read");
    	$id = mysqli_real_escape_string($session_id);
		
	    if ($result = DatabaseManager::submitQuery("SELECT data FROM " . self::$table_name . " WHERE id = '$id'")) {
	        if (mysqli_num_rows($result)) {
	            $record = mysqli_fetch_assoc($result);
	            return $record['data'];
	        }
	    }
	 
	    return '';
    */
    
    public function read($id) {
        $result = mysqli_query($this->link, "SELECT data FROM ".$this->table_name." WHERE id = '".mysqli_real_escape_string($this->link, $id)."'");
        if($row = mysqli_fetch_assoc($result)) {
            return $row['data'];
        } else {
            return "";
        }
   }
    
   /**
    * The function is called whenever PHP needs to write the session data. This takes 
    * place at the very end of the script.
    *
    * PHP passes this function the session identifier and the session data. You don't need to 
    * worry with the format of the data - PHP serializes it, so that you can treat it like a string. 
    * However, PHP does not modify it beyond this, so you want to properly escape it before using 
    * it in a query:
    */
    /*
        //Logger::debug("Session $session_id write ($data)");
		
	    $access = time();
 
    	$id = mysqli_real_escape_string($session_id);
	    $access = mysqli_real_escape_string($access);
    	$data = mysqli_real_escape_string($data);
 		
		return DatabaseManager::submitQuery("REPLACE INTO " . self::$table_name . " VALUES ('$id', '$access', '$data')");
    */
    
    public function write($id, $data) {
        $result = mysqli_query($this->link,"REPLACE INTO ".$this->table_name." VALUES ('".mysqli_real_escape_string($this->link, $id)."', NOW(), '".mysqli_real_escape_string($this->link, $data)."');");
        if($result) {
            return true;
        } else {
            return false;
        }
    }
    
   /**
    * The function is called whenever PHP needs to destroy all session data associated 
    * with a specific session identifier. An obvious example is when you call session__destroy().
    *
    * PHP passes the session identifier to the function:
    */
    /*
        //Logger::debug("Session destroy");

	    $id = mysqli_real_escape_string($session_id);
 
		return DatabaseManager::submitQuery("DELETE FROM " . self::$table_name . " WHERE id = '$id'");
    */
    
    public function destroy($id) {
        $result = mysqli_query($this->link, "DELETE FROM ".$this->table_name." WHERE id ='".mysqli_real_escape_string($this->link, $id)."'");
        if($result) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * The function is called every once in a while in order to clean out (delete) old 
     * records in the session data store. More specifically, the frequency in which this function 
     * is called is determined by two configuration directives, session.gc_probability and 
     * session.gc_divisor. The default values for these are 1  and 1000, respectively, which 
     * means there is a 1 in 1000 (0.1%) chance for this function to be called per session initialization.
     *
     * Because the function keeps the timestamp of the last access in the access column 
     * for each record, this can be used to determine which records to delete. PHP passes the 
     * maximum number of seconds allowed before a session is to be considered expired:
     */
     /*
        Logger::debug("Session gc - max = $max_session_lifetime");

		$old = time() - $max_session_lifetime;
	 	$old = mysqli_real_escape_string($old);

		return DatabaseManager::submitQuery("DELETE FROM " . self::$table_name . " WHERE access < '$old'");
     */
    
    public function gc($maxlifetime) {
        $result = mysqli_query($this->link, "DELETE FROM ".$this->table_name." WHERE (UNIX_TIMESTAMP(access) < (UNIX_TIMESTAMP() - ".mysqli_real_escape_string($this->link, $maxlifetime)."))");
        if($result) {
            return true;
        } else {
            return false;
        }
    }
    
    public function create_sid() {
         $length = 32;

	 if (function_exists('random_bytes')) {
	    return bin2hex(random_bytes($length));
	 }
	 if (function_exists('mcrypt_create_iv')) {
	    return bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
	 }
	 if (function_exists('openssl_random_pseudo_bytes')) {
	    return bin2hex(openssl_random_pseudo_bytes($length));
	 }

	 $res = '';
	 while (strlen($res) < $lenght) {
	    $res = $res . mt_rand(0, 9);
	 }
	 return $res;
    }
    
    public function __destruct() {
    	//it would be good place for closing the opened link to the DB.
    }
}

class Session {

	private static $session_started_flag = false;
	
	/** 
	* Flag to determine if we should use the database to store sessions. This must be set *before* the init 
	* method, or any method, is called!
	*/
	private static $use_db = false;
		
	// //////////////////////////////////////////////////////////////////////////////////////

	/**
	* Initialize the session
	* @param $use_db Flag to determine if we should use the database to store sessions.
	*/
	public static function init($use_db=false) {
	
		ini_set('session.use_strict_mode', 1);
		
		self::$use_db = $use_db;
		
		if (!self::$session_started_flag) {
		
			//Logger::debug("Setting up Sesion");			
									
			self::$session_started_flag = true;
				
			if ($use_db) {
				
				$handler = new SysSession();
				
				session_set_save_handler($handler, true);
   			}
   			
			session_start();
			
			if ($use_db) {
				self::check();
			}
		}
        		
	}
	
	// //////////////////////////////////////////////////////////////////////////////////////
	
	public static function dbFlagStarted() {
	
		if (!self::$session_started_flag) {
			self::init(self::$use_db);
		}
		
		return self::$session_started_flag;
	}
	
	private static function destroy() {
		session_destroy();
		self::init(self::$use_db);
	}
	
	public static function logout() {
		self::clear('access');
		if (session_status() == PHP_SESSION_ACTIVE) {
		self::destroy();		
		}
	}
	
	private static function check() {
		
		if (self::exists('access')) {
		
			$access = self::get('access');
			$result = DatabaseManager::submitQuery("SELECT id, nickname, status FROM lza_users WHERE id = " . (int)$access['id'] );
			
			if (mysqli_num_rows($result)) {
	            		$record = mysqli_fetch_assoc($result);
	            		
	            		if ($record['status'] == 0) {
					self::logout();
					header("Location: index.php?response=Deactivated user account.");
					exit();
				}
				
				if ($access['active'] < (time() - 900)) {
					self::logout();
					header("Location: index.php?response=Logged out because of being idle.");
					exit();
				}
				
				self::clear('access');
				self::set('access', array(
			    				'id' => $record['id'], 
			    				'user' => $record['nickname'],
			    				'status' => $record['status'], 
			    				'active' => time())
			    	);
	        	}	
		}
	}
	
	public static function recreate($id = null) {
		
		// Session ID must be regenerated when
		//  - User logged in (+)
		//  - User logged out (+)
		//  - Certain period has passed (-)
		
		if (!self::$session_started_flag) {
			self::init(self::$use_db);
		}
		
		$sid = session_create_id('custom-');
		
		if(self::$use_db && $id != null) {
		
			$result = DatabaseManager::submitQuery("SELECT id, nickname, status FROM lza_users WHERE id = " . (int)$id);
			
			if (mysqli_num_rows($result)) {
	            		$record = mysqli_fetch_assoc($result);
	            		
	            		if ($record['status'] == 0) {
					self::logout();
					header("Location: index.php?response=Deactivated user account.");
					exit();
				}
				
				if (self::exists('access')) {
					$access = self::get('access');
				
					if ($access['active'] < (time() - 900)) {
						self::logout();
						header("Location: index.php?response=Logged out because of being idle.");
						exit();
					}
					self::clear('access');
				}
				
				self::set('access', array(
			    				'id' => $record['id'], 
			    				'user' => $record['nickname'],
			    				'status' => $record['status'], 
			    				'active' => time())
			    	);
	        	}			
		}
		
		session_commit();
		
		ini_set('session.use_strict_mode', 0);		
		
		session_id($sid);
		
		session_start();
	}
	
	// //////////////////////////////////////////////////////////////////////////////////////

	/**
	* Set a variable into the session
	*/
	public static function set($name, $val) {
		
		$_SESSION[$name] = $val;
	}

	// //////////////////////////////////////////////////////////////////////////////////////

	/**
	* Get a variable from the session, returns null if it doesn't exist
	* and logs a warning with the Logger
	*/
	public static function get($name) {
		
		if(isset($_SESSION[$name])) 
			return $_SESSION[$name];
		else
			Logger::warn("Session variable \"$name\" does not exist, returning null!");
		
		return null;
	}

	// //////////////////////////////////////////////////////////////////////////////////////

	/**
	* Clear a variable in the session
	*/
	public static function clear($name) {
		
		if(isset($_SESSION[$name])) unset($_SESSION[$name]);
	}

	// //////////////////////////////////////////////////////////////////////////////////////

	/**
	* Tests whether a variable is in the session (return true if it is)
	*/
	public static function exists($name) {	
		
		if(isset($_SESSION[$name])) return true; 			
		return false;
	}
	
	public static function exist($name){ return self::exists($name); }
	
	// //////////////////////////////////////////////////////////////////////////////////////
	//
	// Low-level methods to perform actual session management
	//
	// //////////////////////////////////////////////////////////////////////////////////////
	
	private static function connect() {
		DatabaseManager::connect();	
	}
	
	// //////////////////////////////////////////////////////////////////////////////////////	
	// 
	// Logging functions (for debug use only)
	//
	// //////////////////////////////////////////////////////////////////////////////////////

	private static function var_log(&$varInput, $var_name='', $reference='', $method = '=', $sub = false) {
	
	    static $output ;
	    static $depth ;
	
	    if ( $sub == false ) {
	        $output = '' ;
	        $depth = 0 ;
	        $reference = $var_name ;
	        $var = serialize( $varInput ) ;
	        $var = unserialize( $var ) ;
	    } else {
	        ++$depth ;
	        $var =& $varInput ;
	       
	    }
	       
	    // constants
	    $nl = "\n" ;
	    $block = 'a_big_recursion_protection_block';
	   
	    $c = $depth ;
	    $indent = '' ;
	    while( $c -- > 0 ) {
	        $indent .= '  ' ;
	    }
	
	    // if this has been parsed before
	    if ( is_array($var) && isset($var[$block])) {
	   
	        $real =& $var[ $block ] ;
	        $name =& $var[ 'name' ] ;
	        $type = gettype( $real ) ;
	        $output .= $indent.$var_name.' '.$method.'& '.($type=='array'?'Array':get_class($real)).' '.$name.$nl;
	   
	    // havent parsed this before
	    } else {
	
	        // insert recursion blocker
	        $var = Array( $block => $var, 'name' => $reference );
	        $theVar =& $var[ $block ] ;
	
	        // print it out
	        $type = gettype( $theVar ) ;
	        switch( $type ) {
	       
	            case 'array' :
	                //$output .= $indent . $var_name . ' '.$method.' Array ('.$nl;
	                $keys=array_keys($theVar);
	                foreach($keys as $name) {
	                    $value=&$theVar[$name];
	                    self::var_log($value, $name, $reference.'["'.$name.'"]', '=', true);
	                }
	                //$output .= $indent.')'.$nl;
	                break ;
	           
	            case 'object' :
	                $output .= $indent.$var_name.' = '.get_class($theVar).' {'.$nl;
	                foreach($theVar as $name=>$value) {
	                    self::var_log($value, $name, $reference.'->'.$name, '->', true);
	                }
	                $output .= $indent.'}'.$nl;
	                break ;
	           
	            case 'string' :
	                $output .= $indent . $var_name . ' '.$method.' "'.$theVar.'"'.$nl;
	                break ;
	               
	            default :
	                $output .= $indent . $var_name . ' '.$method.' ('.$type.') '.$theVar.$nl;
	                break ;
	               
	        }
	       
	        // $var=$var[$block];
	       
	    }
	   
	    -- $depth ;
	   
	    if( $sub == false )
	        return $output ;
	       
	}

	/////////////////////////////////////////////////////////////////////////////////////////

/*
	public static function logMessage($message, $level, $class, $function){
	
		if (!self::$session_started_flag){
			self::init(self::$use_db);
		}
		
    	$message = mysqli_real_escape_string($message);
	    $level = mysqli_real_escape_string($level);
 		
		return mysqli_query(self::$connection, "INSERT INTO lza_log (message, level, class, function) VALUES  ('$message', '$level', '$class', '$function')");
		
	}
	*/
	
	/////////////////////////////////////////////////////////////////////////////////////////


	/**
	* Returns a string that represents this object, in this case a string that 
	* contains the field values - with html new line tags
	*/
	public static function toString(){
		if (!self::$session_started_flag) self::init(self::$use_db);
		
		$temp = '';
		
		$temp .= "<br><br><b>SESSION Dump</b><pre>";
		$temp .= self::var_log($_SESSION);
		$temp .= "</pre>";
		/*
		$temp .= "<b>REQUEST (GET or POST or COOKIE) Dump</b><pre>";
		$temp .= self::var_log($_REQUEST);
		$temp .= "</pre>";

		$temp .= "<b>GET Dump</b><pre>";
		$temp .= self::var_log($_GET);
		$temp .= "</pre>";
		
		$temp .= "<b>POST Dump</b><pre>";
		$temp .= self::var_log($_POST);
		$temp .= "</pre>";
		*/		
		return $temp;
		
	}
		
	// //////////////////////////////////////////////////////////////////////////////////////
}

	
?>
