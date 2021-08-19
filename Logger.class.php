<?php
/**
*
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
* Logging class, use for all trace commands
*
*/
class Logger {

	public static $DEBUG	 	= 0;
	public static $INFO 		= 1;
	public static $WARNING 		= 2;
	public static $ERROR	 	= 3;
	public static $FATAL	 	= 4;
	
	private static $debugLevel = 0;
	
	private static $echoLog = false;	
	private static $dbLog = false;
							
	// //////////////////////////////////////////////////////////////////////////////////////

	/**
	* Class constructor
	*/
	public function __construct() {
		self::init();
	}

	// //////////////////////////////////////////////////////////////////////////////////////

	public static function echoLog() {
		self::$echoLog = true;
	}

    // //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Let the Logger catch any system errors
     */
    public static function catchSysErrors($dblog = false) {
        
        if($dblog) {
        	self::$dbLog = true;
        }
        
        set_error_handler("Logger::sysErrorHandler");
    }

	// //////////////////////////////////////////////////////////////////////////////////////

	/**
	* 
	*/
	private static function trace($level, $msg) {

		if ($level < self::$debugLevel) {
			return;
		}
			   
		$bt = debug_backtrace();
		
		$class = "";
		$function = "";
		
		// get class, function called by caller of caller of caller
		if (isset($bt[2]['class'])) {
			$class = $bt[2]['class'];
			$function = "." . $bt[2]['function'];
		}
				
		
		// get file, line where call to caller of caller was made
		$file = $bt[1]['file'];
		$line = $bt[1]['line'];
		
		$file_name = basename($file);
					
					
		if (self::$echoLog) {

			switch($level) {
				case self::$DEBUG: 	$levMsg = "<span style='color:009900'>debug</span>"; break;
				case self::$INFO:  	$levMsg = "<span style='color:0000FF'>info</span>"; break;
				case self::$WARNING:$levMsg = "<span style='color:FF6633'>warning</span>"; break;
				case self::$ERROR: 	$levMsg = "<span style='color:FF0101'>error</span>"; break;
				case self::$FATAL: 	$levMsg = "<span style='color:FF0000'><b>fatal</b></span>"; break;
			}

			$msg = "[$levMsg <span style='color:#000099'>$class$function</span>] <b>".htmlentities($msg)."</b>";
			$msg .= "<span style='color: #444; font-style: italic;'> on line ".(int)$line." of ".htmlentities($file_name)."</span>";
			if (isset($bt[2]['file'])){
				$fname = basename($bt[2]['file']);
				$msg .= "<span style='color: #889; font-style: italic;'>, called from line ".$bt[2]['line']." of $fname</span>";
			}
			$msg .= "<br>\n";

			echo $msg;
			
			flush();
			
		}
		else {

			// Get IP address....
	
			// Translate level into text...
			$levMsg = "????";
			switch($level) {
				case self::$DEBUG: 	$levMsg = "DEBUG"; break;
				case self::$INFO:  	$levMsg = "INFO"; break;
				case self::$WARNING:$levMsg = "WARNING"; break;
				case self::$ERROR: 	$levMsg = "ERROR"; break;
				case self::$FATAL: 	$levMsg = "FATAL"; break;
			}
					
			$msg = "[$levMsg] $class$function $msg";
			$msg .= " {on line $line of $file_name";
			if (isset($bt[2]['file'])) {
				$fname = basename($bt[2]['file']);
				$msg .= ", called from line ".$bt[2]['line']." of $fname";
			}
			if (isset($bt[3]['file'])) {
				$fname = basename($bt[3]['file']);
				$msg .= ", called from line ".$bt[3]['line']." of $fname";
			}
			$msg .= "}";

			error_log($msg);		
		}
			
	}

	// //////////////////////////////////////////////////////////////////////////////////////

    /**
     * Use to replace system error handler, if desired. Use Logger::catchSysErrors() to
     * activate
     */
    public static function sysErrorHandler($errno, $errstr, $errfile, $errline) {

        if(self::$dbLog) {
        	DatabaseManager::logMessage($errno, $errstr, $errfile, $errline);
        }
        
        switch ($errno) {
            case E_USER_ERROR:
                $levMsg = "SYS_ERROR";
                break;

            case E_USER_WARNING:
                $levMsg = "SYS_WARNING";
                break;

            case E_USER_NOTICE:
                $levMsg = "SYS_NOTICE";
                break;

            default:
                $levMsg = "SYS_UNKNOWN";
               break;
        }
        
        if (self::$echoLog) {
        
			switch($errno){
				case E_USER_NOTICE: $levMsg = "<span style='color:0000FF'>info</span>"; break;
				case E_USER_WARNING: $levMsg = "<span style='color:FF6633'>warning</span>"; break;
				case E_USER_ERROR: $levMsg = "<span style='color:FF0101'>error</span>"; break;
				default: $levMsg = "<span style='color:009900'>debug</span>"; break;
			}

			$msg = "[$levMsg <span style='color:#000099'>#".(int)$errno."</span>] <b>".htmlentities($errstr)."</b>";
			$msg .= "<span style='color: #444; font-style: italic;'> on line ".(int)$errline." of ".basename($errfile)."</span>";
			$msg .= "<br>\n";

			echo $msg;

			flush();

        }
        else {

            $msg = "[$levMsg] No: ".(int)$errno." Msg: $errstr";
            $msg .= " {on line ".(int)$errline." of ".basename($errfile)."}";

            error_log($msg);
        }

        /* Don't execute PHP internal error handler */
        return true;
    }
    
    // //////////////////////////////////////////////////////////////////////////////////////

	public static function setLevel($newLevel) { $debugLevel = $newLevel; }

	public static function setLevelDebug() { self::$debugLevel = self::$DEBUG; }
	public static function setLevelInfo() { self::$debugLevel = self::$INFO; }
	public static function setLevelWarning() { self::$debugLevel = self::$WARNING; }
	public static function setLevelError() { self::$debugLevel = self::$ERROR; }
	public static function setLevelFatal() { self::$debugLevel = self::$FATAL; }
	
	// //////////////////////////////////////////////////////////////////////////////////////

	/** Print debug message, e.g debug(__CLASS__, 'some error message', __LINE__); */
	public static function dump($var) { self::debug("<pre>" . print_r($var, true) . "</pre>"); }
	public static function debug($msg){ self::trace(self::$DEBUG, $msg);	}
	public static function warning($msg){ self::trace(self::$WARNING, $msg);	}
	public static function warn($msg){ self::trace(self::$WARNING, $msg);	}
	public static function error($msg){ self::trace(self::$ERROR, $msg);	}
	public static function info($msg){  self::trace(self::$INFO, $msg);	}
	public static function fatal($msg){ self::trace(self::$FATAL, $msg);	die(); }

	// //////////////////////////////////////////////////////////////////////////////////////
}

	
?>
