<?php

class DI_Rewrite {
	public $server = array();
	
	protected $rewrite = array(); //$rewrite[$pattern] = $callback
	
	protected $crr_pattern;
	protected $crr_callback;
	protected $crr_methods = array();
	protected $crr_params = array();
	protected $crr_regex;
	protected $crr_splat = '';
	
	protected $debug = false;
	
	public function __construct(){
		$this->server = array(
				'schema' => $this->is_ssl() ? 'https://' : 'http://',
                'host' => self::get_server_var('HTTP_HOST'),
				'req_url' => self::get_server_var('REQUEST_URI', '/'),
                'base' => str_replace(array('\\',' '), array('/','%20'), dirname(self::get_server_var('SCRIPT_NAME'))),
                'base_url' => '',
				'method' =>self::get_server_var('HTTP_X_HTTP_METHOD_OVERRIDE', self::get_server_var('REQUEST_METHOD', 'GET')),
                'referrer' => self::get_server_var('HTTP_REFERER'),
                'ip' => self::get_server_var('REMOTE_ADDR'),
                'ajax' => self::get_server_var('HTTP_X_REQUESTED_WITH') == 'XMLHttpRequest',
                'protocol' => self::get_server_var('SERVER_PROTOCOL', 'HTTP/1.1'),
                'user_agent' => self::get_server_var('HTTP_USER_AGENT'),
                'type' => self::get_server_var('CONTENT_TYPE'),
                'length' => self::get_server_var('CONTENT_LENGTH', 0),
                //'query' => (object)$_GET,
                //'data' => (object)$_POST,
                //'cookies' => (object)$_COOKIE,
                //'files' => (object)$_FILES,
                'secure' => $this->is_ssl(),
                'accept' => self::get_server_var('HTTP_ACCEPT'),
            );
		$this->server['base_url'] = "{$this->server['schema']}{$this->server['host']}{$this->server['base']}";
		$this->reset_current();
		$this->init();
	}
	
	protected static function get_server_var($var, $default = '') {
        return isset($_SERVER[$var]) ? $_SERVER[$var] : $default;
    }
	
	protected function init($fresh = false){
		//get rewrite_rule from DB
	}
	public function reinit(){
		$this->init(true);
	}
	public function is_ssl() {
		if ( isset($_SERVER['HTTPS']) ) {
			if ( 'on' == strtolower($_SERVER['HTTPS']) )
				return true;
			if ( '1' == $_SERVER['HTTPS'] )
				return true;
		} elseif ( isset($_SERVER['SERVER_PORT']) && ( '443' == $_SERVER['SERVER_PORT'] ) ) {
			return true;
		}
		return false;
	}	
	
	public function reset_current(){
		$this->crr_pattern = '';
		$this->crr_callback = '';
		$this->crr_methods = array('*');
		$this->crr_params = array();
		$this->crr_regex = '';
		$this->crr_splat = '';
		return $this;
	}
	
	public function add_rewrite_rule($pattern, $callback,$overwrite = true) {
		$pattern = trim($pattern);
		
		if ( !$overwrite && isset($this->rewrite[$pattern]) ){
			return $this->reset_current();
		}
		
		$this->rewrite[$pattern] = $callback;
		$this->set_current($pattern, $callback); //set curent
		return $this;
    }	
	protected function set_current($pattern, $callback) {
		$pattern = trim($pattern);
		$this->crr_pattern = (string)$pattern;
        $this->crr_callback = $callback;
		return $this;
    }
	public function rewrite_rule($pattern, $callback){
		return $this->set_current($pattern, $callback);
	}
	
	protected function do_callback(){
		if ($this->debug){
			call_user_func_array(array($this,'rewrite_debug_callback'), $this->crr_params);
		}
		if(is_callable($this->crr_callback)){
			call_user_func_array($this->crr_callback, $this->crr_params);
		}
		return $this->reset_current();
	}
	public function start(){
		foreach ($this->rewrite as $pattern => $callback){
			$this->set_current($pattern, $callback)->run();
		}
	}
	public function run(){
		if ($this->matchUrl() && $this->matchMethod()){
			$this->do_callback();
		}
		return $this->reset_current();
	}
	public function matchUrl($req_url = ''){
		if ($this->is_matchUrl($req_url)) return $this;
		return $this->reset_current(); //reset object
	}
    public function is_matchUrl($req_url = '') {
        if (empty($req_url)) $req_url = str_replace($this->server['base'], '', $this->server['req_url']);
		if (strpos($this->crr_pattern, ' ') !== false) {
			list($method, $this->crr_pattern) = explode(' ', trim($this->crr_pattern), 2);
            $this->crr_methods = explode('|', $method);
        }
	if ($this->crr_pattern === '*' || $this->crr_pattern === $req_url) {
            return true;
        }

        $last_char = substr($this->crr_pattern, -1);
        // Get splat
        if ($last_char === '*') {
            $n = 0;
            $len = strlen($req_url);
            $count = substr_count($this->crr_pattern, '/');

            for ($i = 0; $i < $len; $i++) {
                if ($req_url[$i] == '/') $n++;
                if ($n == $count) break;
            }

            $this->crr_splat = (string)substr($req_url, $i+1);
        }

        // Build the regex for matching
        $regex = str_replace(array(')','/*'), array(')?','(/?|/.*?)'), $this->crr_pattern);
        $regex = preg_replace_callback(
            '#@([\w]+)(:([^/\(\)]*))?#',
            function($matches) use (&$ids) {
                $ids[$matches[1]] = null;
                if (isset($matches[3])) {
                    return '(?P<'.$matches[1].'>'.$matches[3].')';
                }
                return '(?P<'.$matches[1].'>[^/\?]+)';
            },
            $regex
        );

        // Fix trailing slash
        if ($last_char === '/') {
            $regex .= '?';
        }
        // Allow trailing slash
        else {
            $regex .= '/?';
        }

        // Attempt to match route and named parameters
        if (preg_match('#^'.$regex.'(?:\?.*)?$#i', $req_url, $matches)) {
            foreach ((array)$ids as $k => $v) {
                $this->crr_params[$k] = (array_key_exists($k, $matches)) ? urldecode($matches[$k]) : null;
            }
			
			if (!empty($this->crr_splat)) $this->crr_params[] = $this->crr_splat;
		    $this->crr_regex = $regex;

            return true;
        }
        return false;
    }
	
	protected function matchMethod() {
        return count(array_intersect(array($this->server['method'], '*'), $this->crr_methods)) > 0;
    }
	
	protected function rewrite_debug_callback(){
		$numargs = func_num_args();
		$arg_list = func_get_args();
		echo "Number of arguments: $numargs<br /><br />\n";
		for ($i = 0; $i < $numargs; $i++) {
			echo "Argument $i is: " . $arg_list[$i] . "<br />\n";
		}
	}	
	public function debug(){
		$this->debug = true;
		return $this;
	}
}
