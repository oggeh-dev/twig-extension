<?php
	/*
	 * OGGEH Twig Extension
	 * @version 0.3
	 * 
	 * Author: Ahmed Abbas - OGGEH Cloud Computing LLC - oggeh.com
	 * License: GNU-GPL v3 (http://www.gnu.org/licenses/gpl.html)
	 * -------------------------------------------------------------------
	 * Copyright (C) 2002-2017 Ahmed Abbas - OGGEH Cloud Computing LLC - oggeh.com
	 * 
	 * OGGEH HTML Parser is free software: you can redistribute it and/or modify it
	 * under the terms of the GNU General Public License as
	 * published by the Free Software Foundation, either version 3 of the
	 * License, or (at your option) any later version.
	 * 
	 * OGGEH HTML Parser is distributed in the hope that it will be useful, but
	 * WITHOUT ANY WARRANTY; without even the implied warranty of
	 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
	 * See the GNU General Public License for more details.
	 * 
	 * You should have received a copy of the License
	 * along with OGGEH HTML Parser. If not, see
	 * <https://github.com/oggehllc/oggeh-html-parser/LICENSE.txt>.
	 * 
	 * See LICENSE.txt file for more information.
	 * -------------------------------------------------------------------
	 *
	 */
	class OGGEH {
		static $rewrite = false;
		static $sandbox = false;
		static $domain = '';
		static $api_key = '';
		static $sandbox_key = '';
		static $lang = 'en';
		static $i18n = array();
		static $locale = array();
		static $inactive = 'inactive';
		public $app = null;
		private $stack = array();
		private $endpoint = 'https://api.oggeh.com';
		function __construct() {
			date_default_timezone_set('Africa/Cairo');
			session_start();
			if (is_file('locale.json')) {
				self::$locale = array_reverse(json_decode(file_get_contents('locale.json'), true));
			}
		}
		static function configure($setting, $value=null) {
			if (is_array($setting)) {
				foreach ($setting as $key=>$value) {
					self::configure($key, $value);
				}
			} elseif (property_exists(__CLASS__, $setting)) {
				self::$$setting = $value;
			}
		}
		protected function utf8ize($mixed) {
      $mixed = (is_object($mixed)) ? (array)$mixed : $mixed;
      if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
          $mixed[$key] = $this->utf8ize($value);
        }
      } else if (is_string($mixed)) {
        return utf8_encode($mixed);
      }
      return $mixed;
    }
    private function jsonEncode($input, $pretty=false) {
      if ($pretty) {
        $encoded = json_encode($input, JSON_PRETTY_PRINT);
      } else {
        $encoded = json_encode($input);
      }
      switch (json_last_error()) {
        case JSON_ERROR_NONE:
        return $encoded;
        break;
        case JSON_ERROR_DEPTH:
        return '[Maximum stack depth exceeded]';
        break;
        case JSON_ERROR_STATE_MISMATCH:
        return '[Underflow or the modes mismatch]';
        break;
        case JSON_ERROR_CTRL_CHAR:
        return '[Unexpected control character found]';
        break;
        case JSON_ERROR_SYNTAX:
        return '[Syntax error, malformed JSON]';
        break;
        case JSON_ERROR_UTF8:
        $clean = $this->utf8ize($input);
        return $this->jsonEncode($clean, $pretty);
        break;
        default:
        return '';
        break;
      }
    }
		public function call($json='') {
			$sandbox = self::$sandbox;
			$domain = self::$domain;
			$api_key = self::$api_key;
			$sandbox_key = self::$sandbox_key;
			$lang = self::$lang;
			$inactive = self::$inactive;
			if (!isset($api_key) || empty($api_key)) {
				echo '[missing api key!]';
				exit;
			}
			$res = '';
			if ($lang != 'undefined' && !stristr($lang, '$')) {
				$vars = array(
					'api_key'=>$api_key,
					'lang'=>$lang
				);
				$query = '';
				foreach($vars as $key=>$value) {
		      if (is_array($value)) {
		        for ($i=0; $i<count($value); $i++) {
		          $query .= $key.'='.$value[$i].'&';
		        }
		      } else {
		        $query .= $key.'='.$value.'&';
		      }
		    }
		    $query = rtrim($query, '&');
		    if (!isset($this->endpoint)) {
		    	echo '[missing endpoint!]';
					exit;
		    }
		    $url = $this->endpoint;
		    if ($query != '') {
		      $url .= '/?'.$query;
		    }
		    $body = preg_replace('#(?: {2,}|[\r\n\t]+)#s', '$1', $json);
		    $cookie = sys_get_temp_dir().'oggeh';
		    $res = '';
		    try {
		    	//error_log($url);
		    	//error_log($body);
		    	$ch = curl_init();
			    curl_setopt($ch, CURLOPT_URL, $url);
			    curl_setopt($ch, CURLOPT_USERAGENT, isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'OGGEH v1.0');
			    $headers = array(
			    	'Origin: '.$domain,
			    	'Accept: application/json',
			    	'Content-Type: application/json',
			    	'Content-Length: '.strlen($body)
			    );
			    if ($sandbox) {
			    	$headers[] = 'SandBox: '.hash_hmac('sha512', $domain.$api_key, $sandbox_key);
			    }
			    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
			    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			    curl_setopt($ch, CURLOPT_POST, true);
	      	curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
			    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 0);
			    curl_setopt($ch, CURLOPT_TIMEOUT, 360);
			    curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
			    curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
			    $res = curl_exec($ch);
			    //error_log($res);
			    if ($res === false) {
			    	error_log('curl error: '. curl_error($ch));
			    	curl_close($ch);
			    	exit;
			    } else {
			    	curl_close($ch);
			    	$res = json_decode($res, true);
				    if ($res) {
              if ($res['error'] != '') {
                if ($res['error'] != 'app not published' && $res['error'] != 'account suspended') {
                  error_log('api error: '. $res['error']);
                } else {
                  if (self::$rewrite) {
                    if ($_SERVER['REQUEST_URI'] != '/'.$lang.'/'.$inactive) {
                      header('Location: /'.$lang.'/'.$inactive);
                      exit;
                    }
                  } else {
                    if (!isset($_REQUEST['module']) || $_REQUEST['module'] != $inactive) {
                      header('Location: /?lang='.$lang.'&module='.$inactive);
                      exit;
                    }
                  }
                }
              } else {
                if (self::$rewrite) {
                  if ($_SERVER['REQUEST_URI'] == '/'.$lang.'/'.$inactive) {
                    header('Location: /'.$lang);
                    exit;
                  }
                } else {
                  if (isset($_REQUEST['module']) && $_REQUEST['module'] == $inactive) {
                    header('Location: /?lang='.$lang);
                    exit;
                  }
                }
                $res = $res['stack'];
                $this->stack = $res;
              }
            } else {
              error_log('response error: '. $res);
            }
			    }
		    } catch (Exception $e) {
		    	error_log('request error: '. $e->getMessage());
		    }
			}
	    return $res;
		}
		public function get($alias='') {
			$output = '';
			$exists = false;
			foreach ($this->stack as $res) {
				if ($res['alias'] == $alias) {
					$exists = true;
					$output = $res['output'];
				}
			}
			$output = ($exists) ? $output : 'unable to find method alias!';
			return $output;
		}
		public function trans($phrase) {
			$translation = $phrase;
			$i18n = self::$i18n;
			$lang = self::$lang;
			if (isset($i18n[$phrase])) {
				if (isset($i18n[$phrase][$lang])) {
					$translation = $i18n[$phrase][$lang];
				}
			}
			return $translation;
		}
		public function flag($code) {
			$locale = self::$locale;
	    foreach ($locale as $loc) {
        if (stristr($loc['lang'], strtolower($code).'-') && !is_numeric($loc['territory'])) {
          return strtolower($loc['territory']);
        }
	    }
	    return '';
		}
		public function urldecode($input) {
			return urldecode($input);
		}
	}
	class OGGEH_Twig_Extension extends Twig_Extension {
		public function getGlobals() {
			return array(
				'host' => $_SERVER['HTTP_HOST'],
				'uri' => $_SERVER['REQUEST_URI']
			);
    }
    public function getFilters() {
      return array(
        'url_decode' => new Twig_SimpleFilter('url_decode', array('OGGEH_Twig_RuntimeExtension', 'urldecode'))
      );
    }
		public function getFunctions() {
			return array(
				new Twig_SimpleFunction('call', array('OGGEH_Twig_RuntimeExtension', 'call')),
				new Twig_SimpleFunction('get', array('OGGEH_Twig_RuntimeExtension', 'get')),
				new Twig_SimpleFunction('trans', array('OGGEH_Twig_RuntimeExtension', 'trans')),
				new Twig_SimpleFunction('flag', array('OGGEH_Twig_RuntimeExtension', 'flag'))
			);
		}
	}
	class OGGEH_Twig_RuntimeExtension {
    private $oggeh;
    public function __construct($oggeh) {
      $this->oggeh = $oggeh;
    }
    public function call($value) {
      return $this->oggeh->call($value);
    }
    public function get($value) {
      return $this->oggeh->get($value);
    }
    public function trans($value) {
      return $this->oggeh->trans($value);
    }
    public function flag($value) {
      return $this->oggeh->flag($value);
    }
    public function urldecode($value) {
      return $this->oggeh->urldecode($value);
    }
	}
	class RuntimeLoader implements Twig_RuntimeLoaderInterface {
    public function load($class) {
      if ('OGGEH_Twig_RuntimeExtension' === $class) {
        return new $class(new OGGEH());
      }
    }
	}
?>
