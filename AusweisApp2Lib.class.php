<?php 
/*
    AusweisApp2Lib - A PHP framework for the german AusweisApp2
                     see https://www.ausweisapp.bund.de/ for details
                     
    Copyright (C) 2018 Oliver Welter <info@bunkerschild.de>

    This program is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with this program.  If not, see <https://www.gnu.org/licenses/>.
*/

namespace AusweisApp2Lib;

define("AA2_DEFAULT",			0xff);
define("AA2_DEFAULT_SCHEME",		"http");
define("AA2_DEFAULT_USERNAME",		null);
define("AA2_DEFAULT_PASSWORD",		null);
define("AA2_DEFAULT_HOST",		"127.0.0.1");
define("AA2_DEFAULT_PORT",		24727);
define("AA2_DEFAULT_PATH",		"/eID-Client");
define("AA2_DEFAULT_TOKENURL_KEY",	"tcTokenURL");
define("AA2_DEFAULT_COOKIE_PATH",	"/tmp/aa2lib-cookie-");

define("AA2_EXCPT_WARNING",		0x01);
define("AA2_EXCPT_ERROR",		0x02);
define("AA2_EXCPT_FORBIDDEN",		0x04);
define("AA2_EXCPT_INTERNAL",		0x10);
define("AA2_EXCPT_EXTERNAL",		0x20);

define("AA2_LIB_USER_AGENT",		"AusweisApp2Lib 1.0");

final class AusweisApp2LibException extends \exception
{
    private $excpt_res = null;
    
    function __construct($excpt_res, $excpt_message, $excpt_code = null)
    {
        $this->excpt_res = $excpt_res;
        parent::__construct($excpt_message, $excpt_code);
    }
    
    public function getRes()
    {
        return $this->excpt_res;
    }
}

class AusweisApp2Lib
{
    private $session_id = null;
    
    private $aa2_scheme = null;
    private $aa2_username = null;
    private $aa2_password = null;
    private $aa2_host = null;
    private $aa2_port = null;
    private $aa2_path = null;
    private $aa2_tokenurl_key = null;
    private $aa2_tokenurl_val = null;
    private $aa2_verify_ssl = null;
    private $aa2_cookie_path = null;
    
    function __construct($tokenurl, $session_id = null)
    {
        $this->session_id = (($session_id) ? $session_id : md5(microtime(true)));
        
        $this->aa2_scheme = AA2_DEFAULT_SCHEME;
        $this->aa2_username = AA2_DEFAULT_USERNAME;
        $this->aa2_password = AA2_DEFAULT_PASSWORD;
        $this->aa2_host = AA2_DEFAULT_HOST;
        $this->aa2_port = AA2_DEFAULT_PORT;
        $this->aa2_path = AA2_DEFAULT_PATH;
        $this->aa2_tokenurl_key = AA2_DEFAULT_TOKENURL_KEY;
        $this->aa2_tokenurl_val = $tokenurl;
        $this->aa2_verify_ssl = true;
        $this->aa2_cookie_path = AA2_DEFAULT_COOKIE_PATH;
    }
    
    function __get($key)
    {
        $nkey = "aa2_".substr($key, 4);
        
        if (!isset($this->$nkey))
            
        switch ($nkey)
        {
            case "aa2_scheme":
            case "aa2_username":
            case "aa2_password":
            case "aa2_host":
            case "aa2_port":
            case "aa2_path":
            case "aa2_tokenurl_key":
            case "aa2_tokenurl_val":
            case "aa2_verify_ssl":
            case "aa2_cookie_path":
                return $this->$nkey;
            default:
                throw new AusweisApp2LibException(AA2_EXCPT_FORBIDDEN | AA2_EXCPT_INTERNAL, "Unknown variable or access denied");
                return null;
        }
    }
    
    function __set($key, $val)
    {
        $nkey = "aa2_".substr($key, 4);
        
        if (!isset($this->$nkey))
            
        switch ($nkey)
        {
            case "aa2_scheme":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_SCHEME : $val);
                break;
            case "aa2_username":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_USERNAME : $val);
                break;
            case "aa2_password":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_PASSWORD : $val);
                break;
            case "aa2_host":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_HOST : $val);
                break;
            case "aa2_port":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_PORT : $val);
                break;
            case "aa2_path":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_PATH : $val);
                break;
            case "aa2_tokenurl_key":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_TOKENURL_KEY : $val);
                break;
            case "aa2_tokenurl_val":
                if ($val == AA2_DEFAULT)
                {
                    throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_INTERNAL, "Token URL doesnt have a default value");
                    return;
                }    
                
                $this->$nkey = $val;
                break;
            case "aa2_verify_ssl":
                $this->$nkey = (($val == AA2_DEFAULT) ? true : $val);
                break;
            case "aa2_cookie_path":
                $this->$nkey = (($val == AA2_DEFAULT) ? AA2_DEFAULT_COOKIE_PATH : $val);
                break;
            default:
                throw new AusweisApp2LibException(AA2_EXCPT_FORBIDDEN | AA2_EXCPT_INTERNAL, "Unknown variable or access denied");
                return; 
        }
        
        $this->aa2_validate_url();
        
        return;
    }
    
    private function aa2_get_url($query = null)
    {
        $url = $this->aa2_scheme."://";
        
        if ($this->aa2_username)
            $url .= $this->aa2_username;
            
        if ($this->aa2_username && $this->aa2_password)
            $url .= ":".$this->aa2_password."@";
            
        $url .= $this->aa2_host;
        
        if ($this->aa2_port)
            $url .= ":".$this->aa2_port;
        
        if ($this->aa2_path != "")
            $url .= $this->aa2_path;
        else
            $url .= "/";
        
        if ($query)
            $url .= "?".$query;
            
        return $url;
    }
    
    private function aa2_get_token_url()
    {
        return $this->aa2_get_url($this->aa2_tokenurl_key."=".rawurlencode($this->aa2_tokenurl_val));
    }
    
    private function aa2_validate_url()
    {
        $parts = parse_url($this->aa2_get_url());
        
        if ((!isset($parts["scheme"])) && ($parts["scheme"] != $this->aa2_scheme) && ($parts["scheme"] != "http") && ($parts["scheme"] != "https"))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_INTERNAL, "Missing or invalid URL scheme");
            return false;
        }
        elseif ((!isset($parts["host"])) && ($parts["host"] != $this->aa2_host))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_INTERNAL, "Missing or invalid URL hostname");
            return false;
        }
        elseif ((!isset($parts["path"])) && ($parts["path"] != $this->aa2_path))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_INTERNAL, "Missing or invalid URL path");
            return false;
        }
        elseif ((isset($parts["username"])) && ($parts["username"] != $this->aa2_username))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_WARNING | AA2_EXCPT_INTERNAL, "Invalid URL username");
            return false;
        }
        elseif ((isset($parts["password"])) && (!isset($parts["username"])))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_WARNING | AA2_EXCPT_INTERNAL, "Missing username");
            return false;
        }
        elseif ((isset($parts["password"])) && ($parts["password"] != $this->aa2_password))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_WARNING | AA2_EXCPT_INTERNAL, "Invalid URL password");
            return false;
        }
        elseif ((isset($parts["port"])) && ($parts["port"] != $this->aa2_port) || ($parts["port"] > 65535) || ($parts["port"] < 1))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_WARNING | AA2_EXCPT_INTERNAL, "Invalid URL port");
            return false;
        }
        
        return true;
    }
    
    private function aa2_send_command($command, $with_tc_token_url = false, $skip_error_handling = false)
    {
        $cookie_dir = dirname($this->aa2_cookie_path);
        $cookie_file = basename($this->aa2_cookie_path).$this->session_id;
            
        if ((file_exists($cookie_dir)) && (!is_dir($cookie_dir)))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_EXTERNAL, "Cookie directory is a file");
            return false;
        }
        elseif ((!file_exists($cookie_dir)) && (!is_dir($cookie_dir)))
        {
            if (!@mkdir($cookie_dir))
            {
                throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_INTERNAL, "Unable to create cookie directory");
                return false;
            }
        }
        
        if (!is_object($command))
        {
            $cmd = new \stdClass;
            $cmd->cmd = $command;
            
            $json = json_encode($cmd);
        }
        else
        {
            $json = json_encode($command);
        }
        
        $header = array(
            'Content-Type: application/json',
            'Content-Length: '. strlen($json)
        );
        
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, (($with_tc_token_url) ? $this->aa2_get_token_url() : $this->aa2_get_url()));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_FAILONERROR, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, $this->aa2_verify_ssl);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, $this->aa2_verify_ssl);
        curl_setopt($ch, CURLOPT_USERAGENT, AA2_LIB_USER_AGENT);
        curl_setopt($ch, CURLOPT_ENCODING, "");
        curl_setopt($ch, CURLOPT_MAXREDIRS, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_dir."/".$cookie_file);        
        curl_setopt($ch, CURL_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURL_POSTFIELDS, $json);
        curl_setopt($ch, CURL_HTTPHEADER, $header);
        
        $result = curl_exec($ch);
        $response = curl_getinfo($ch);
        
        curl_close($ch);
        
        if ($response["http_code"] != 200)
        {
            if (substr($response["http_code"], 0, 1) < 4)
                throw new AusweisApp2LibException(AA2_EXCPT_WARNING | AA2_EXCPT_EXTERNAL, "HTTP response ".$response["http_code"], $response["http_code"]);
            elseif (substr($response["http_code"], 0, 1) < 5)
                throw new AusweisApp2LibException(AA2_EXCPT_FORBIDDEN | AA2_EXCPT_EXTERNAL, "HTTP warning ".$response["http_code"], $response["http_code"]);
            else
                throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_EXTERNAL, "HTTP error ".$response["http_code"], $response["http_code"]);
                
            return false;
        }
        
        $raw_response = json_decode($result);
        
        if ($skip_error_handling)
            return $raw_response;
        
        if (!$raw_response)
        {
            throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_EXTERNAL, "Not a valid JSON response");
            return false;
        }
        
        if (!isset($raw_response->msg))
        {
            throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_EXTERNAL, "Missing msg key");
            return false;
        }
        
        switch ($raw_response->msg)
        {
            case "INTERNAL_ERROR":
            case "BAD_STATE":
            case "INVALID":
            case "UNKNOWN_COMMAND":
                throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_EXTERNAL, $raw_response->msg.": ".$raw_response->error);
                return false;
            default:
                if (isset($raw_response->error))
                {
                    throw new AusweisApp2LibException(AA2_EXCPT_ERROR | AA2_EXCPT_EXTERNAL, "Undocumented error: ".$raw_response->error);
                    return false;        
                }
                
                return $raw_response;
        }
    }
    
    public function get_info()
    {
        $obj = $this->aa2_send_command("GET_INFO");
        
        if (!isset($obj->msg) || $obj->msg != "INFO")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        return $obj->VersionInfo;
    }
    
    public function get_api_level(&$available_api_levels = null)
    {
        $obj = $this->aa2_send_command("GET_API_LEVEL");
        
        if (!isset($obj->msg) || $obj->msg != "API_LEVEL")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        $availabe_api_levels = $obj->available;
        
        return $obj->current;
    }
    
    public function set_api_level($api_level = 1)
    {
        $cmd = new \stdClass;
        $cmd->cmd = "SET_API_LEVEL";
        $cmd->level = $api_level;
        
        $obj = $this->aa2_send_command($cmd);

        if (!isset($obj->msg) || $obj->msg != "API_LEVEL")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        return ($api_level == $obj->current) ? true : false;
    }

    public function get_reader(&$name, &$attached = null)
    {
        $cmd = new \stdClass;
        $cmd->cmd = "GET_READER";
        $cmd->name = $name;
        
        $obj = $this->aa2_send_command($cmd);
        
        if (!isset($obj->msg) || $obj->msg != "READER")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        $name = $obj->name;
        $attached = $obj->attached;
        
        return $obj->card;
    }

    public function get_reader_list()
    {
        $obj = $this->aa2_send_command("GET_READER_LIST");

        if (!isset($obj->msg) || $obj->msg != "READER_LIST")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        return $obj->reader;
    }
    
    public function run_auth(&$result_url = null)
    {
        $cmd = new \stdClass;
        $cmd->cmd = "GET_READER";
        $cmd->tcTokenURL = $this->aa2_tokenurl_val;
        
        $obj = $this->aa2_send_command($cmd);

        if (!isset($obj->msg) || $obj->msg != "AUTH")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        $result_url = $obj->url;
        
        return $obj->result;
    }

    public function get_access_rights()
    {
        return $this->aa2_send_command("GET_ACCESS_RIGHTS");
    }
    
    public function set_access_rights($chat)
    {
        $cmd = new \stdClass;
        $cmd->cmd = "SET_ACCESS_RIGHTS";
        $cmd->chat = $chat;
        
        return $this->aa2_send_command($cmd);
    }

    public function get_certificate(&$certificate_validity = null)
    {
        $obj = $this->aa2_send_command("GET_CERTIFICATE");

        if (!isset($obj->msg) || $obj->msg != "CERTIFICATE")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        $certificate_validity = $obj->validity;
        
        return $obj->description;
    }
    
    public function cancel()
    {
        $obj = $this->aa2_send_command("CANCEL");
        
        if (!isset($obj->msg) || $obj->msg != "CANCEL")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        return true;
    }
    
    public function accept()
    {
        $obj = $this->aa2_send_command("ACCEPT");
        
        if (!isset($obj->msg) || $obj->msg != "ACCEPT")
        {
            throw new AusweisApp2LibException(AA2_EXCEPT_ERROR | AA2_EXCEPT_EXTERNAL, "Unexpected response");
            return false;
        }
        
        return true;
    }
    
    public function set_pin($pin)
    {
        $cmd = new \stdClass;
        $cmd->cmd = "SET_PIN";
        $cmd->value = $pin;
        
        return $this->aa2_send_command($cmd);
    }

    public function set_can($can)
    {
        $cmd = new \stdClass;
        $cmd->cmd = "SET_CAN";
        $cmd->value = $can;
        
        return $this->aa2_send_command($cmd);
    }

    public function set_puk($puk)
    {
        $cmd = new \stdClass;
        $cmd->cmd = "SET_PUK";
        $cmd->value = $puk;
        
        return $this->aa2_send_command($cmd);
    }
}