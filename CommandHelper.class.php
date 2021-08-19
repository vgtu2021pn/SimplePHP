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
* This class provides a way consitent and simplified way to handle user content
* from the client (browser).
*
* Requires: a slighlty modified version of Garry Whites' browser detection class (Browser.class.php) 
* for the optional init method, if you don't use this then you don't need this file.
* 
* Usage; 
*
* [Optional] Call CommandHelper::init() to setup (this determines if the browser supports gzip)
* Alternatively, just set CommandHelper::$ZIP_MESSAGE yourself.
*
* Validation examples;
*
* To validate a $POST or $GET para called 'myParaName' that is required and you expect to be numeric call;
* CommandHelper::getPara('myParaName', true, CommandHelper::$PARA_TYPE_NUMERIC);
*
* To validate a $POST or $GET para called 'myParaName' that is required and you expect to be a string call;
* CommandHelper::getPara('myParaName', true, CommandHelper::$PARA_TYPE_STRING);
*
* To validate a $POST or $GET para called 'myParaName' that is NOT required and you expect to be a json encoded object call;
* CommandHelper::getPara('myParaName', false, CommandHelper::$PARA_TYPE_JSON);
*
* @author Mike Pritchard (mike@adastrasystems.com) 
*
*/
class CommandHelper {

	public static $PARA_TYPE_NUMERIC = 0;
	public static $PARA_TYPE_INT = 1;
	public static $PARA_TYPE_FLOAT = 2;
	public static $PARA_TYPE_STRING = 3;
	public static $PARA_TYPE_BOOL = 4;
	public static $PARA_TYPE_JSON = 5;
	public static $PARA_TYPE_ARRAY = 6;
	public static $PARA_TYPE_OBJECT = 7;
	public static $ZIP_MESSAGE = true;
	
	// ///////////////////////////////////////////////////////////////////////////////////////
	
	public static function init(){
	
		$browser = new BrowserDetect();
			 	
		// Turn off gzip for IE6 or lower
		$browser_name = $browser->getBrowser();
		$browser_version = $browser->getVersion();
		
	    if ($browser_name == BrowserDetect::BROWSER_IE && $browser_version < 7) {
	    	self::$ZIP_MESSAGE = false;
		}
		else {
	    	self::$ZIP_MESSAGE = true;
	    }
	}
	
	// ///////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Get a para from either a $_GET or $_POST.
	*
	* @return The validated para, or null if validation failed.
	* @para paraName
	* @para required if true, this will return an error message and quits from the php session
	*/
	public static function getPara($paraName, $required=false, $type=0) {
	
	    $val = false;
	    $val_set = false;
			
		if(isset($_POST[$paraName])) {
	        $val = $_POST[$paraName];
	        $val_set = true;
	    }
		if(isset($_GET[$paraName])) {
	        $val = $_GET[$paraName];
	        $val_set = true;
	    }
	
		$typestr = "Unknown";
	
	    if ($val_set) {
	
	        switch($type) {
	            
	            case self::$PARA_TYPE_NUMERIC :
	            	$typestr = 'numeric';
	                if (is_numeric($val)) {	                	
	                	return $val;
	                }
	                break;
	            
	            case self::$PARA_TYPE_INT :
	            	$typestr = 'integer';
	                if (is_integer($val)) {
	                	return $val;
	                }
	                break;
	            
	            case self::$PARA_TYPE_FLOAT :
	            	$typestr = 'float';
	                if (is_float($val)) {
	                	return $val;
	                }
	                break;
	
	            case self::$PARA_TYPE_STRING :
	                $typestr = 'string';
	                if (is_string($val)) {
	                	return $val;
	                }
	                break;
	                
	            case self::$PARA_TYPE_BOOL :
	            	$typestr = 'bool';
	                if (is_bool($a) === true) {
	                	return $val;
	                }
	                break;
			
	            case self::$PARA_TYPE_JSON :
	            	$typestr = 'json';
	            	
	            	// Test to see if it can be decoded
	            	$cr_list = array("\r\n", "\n", "\r"); // Remove new line character
	                $val = str_replace($cr_list, '', $val);
	                
	            	$test = json_decode($val, true); // JSON obj to assoc array
	            	
	            	if ($test !== FALSE) {
		                return json_encode($test, JSON_FORCE_OBJECT); // assoc array to JSON obj
	            	}
	                break;
	                
	            case self::$PARA_TYPE_ARRAY :
	            	$typestr = 'array';
	            	if (is_array($val)) {
	            		return $val;
	            	}	            	
	                break;
	                
	            case self::$PARA_TYPE_OBJECT :
	            	$typestr = 'object';
	            	if (is_object($val)) {
	            		return $val;
	            	}            	
	                break;
	        }
	        
	        if ($required) {
	        	self::sendValidateFailMessage("Validation failed, expecting '".$paraName."' to be of type '".$typestr."' (got ".gettype($val).")");
	        	exit();
	        }
	    }
	    
	    // If we get here, then could not find para in $GET or $POST
	    
	    if ($required){
	    	self::sendValidateFailMessage("Parameter not set, expecting '".$paraName."'");
	    	exit();
	    }
	    
	    return false;
	}

	// ///////////////////////////////////////////////////////////////////////////////////////
	
	public static function sendAuthorizationFailMessage($msg) {
		$data['result'] = 'fail';
		$data['data'] = 'Authorization failure: ' . $msg;
		error_log("Authorization failure!!!");
		self::sendMessage($data);
	}

	// ///////////////////////////////////////////////////////////////////////////////////////

	private static function sendValidateFailMessage($msg) {
		$data['result'] = 'fail';
		$data['data'] = 'Validation failure: ' . $msg;
		error_log($data['data']);
		self::sendMessage($data);
	}
	
	// ///////////////////////////////////////////////////////////////////////////////////////
		
	/**
	* Send a normal text (string) message back to the client.
	*/
	public static function sendTextMessage($msg) {
	
		if (self::$ZIP_MESSAGE) {
			$msg = gzencode($msg);
			header("Content-Encoding: gzip"); 
			header("Content-Type: text/plain"); 		
		}
		
		print($msg);
	}

	// ///////////////////////////////////////////////////////////////////////////////////////
	
	/**
	* Send the message back to the client, optionally gzip the message. The data is encoded
	* using json_encode, see http://php.net/manual/en/function.json-encode.php for more information
	*
	* @param data - The data to be encoded
	*/
	public static function sendMessage($data) {
	
		if (self::$ZIP_MESSAGE) {
			$msg = gzencode(json_encode($data));
			header("Content-Encoding: gzip"); 
			header("Content-Type: text/plain"); 		
		}
		else {
			$msg = json_encode($data);
		}	
		
		print($msg);
	}	
}
?>
