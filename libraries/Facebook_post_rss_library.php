<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');
require_once APPPATH . 'libraries/facebook-library/src/Facebook/autoload.php';

class MyPHPCIPersistentDataHandler extends Facebook\PersistentData\FacebookSessionPersistentDataHandler {
	protected $sessionPrefix = 'FBRLH_';
	public function __construct($enableSessionCheck = true) {
		parent::__construct(false);
		$this->ci = & get_instance();
		$this->ci->load->library('session');
	}
	public function get($key) {
		return $this->ci->session->userdata($this->sessionPrefix . $key);
	}
	public function set($key, $value) {
		return $this->ci->session->set_userdata($this->sessionPrefix . $key, $value);
	}
}

class Facebook_Post_RSS_Library {
	public function __construct() {
		$this->ci =& get_instance();
		$this->ci->load->config('auth', true);
		$this->app_id = $this->ci->config->item('auth/facebook/api_key', 'auth');
		$this->app_secret = $this->ci->config->item('auth/facebook/secret_key', 'auth');
		$this->permissions = $this->ci->config->item('auth/facebook/scope', 'auth');

		$fb_options = [
			'app_id' => $this->app_id,
			'app_secret' => $this->app_secret,
			'default_graph_version' => 'v2.4',
			'persistent_data_handler' => new MyPHPCIPersistentDataHandler()
		];
		$this->facebook = new Facebook\Facebook($fb_options);
		$this->oauth_token = false;
	}

	function token_validation($token) {
		if ($token === false)
			return false;
		try{
			$this->facebook->setDefaultAccessToken($token);
			$access_token = $this->facebook->getDefaultAccessToken();
			if (!$access_token->isExpired())
				return true;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		}
		return false;
	}

	public function get_long_lived_token($token) {
		if ($token === false)
			return false;
		try{
			$this->facebook->setDefaultAccessToken($token);
			$access_token = $this->facebook->getDefaultAccessToken();
			if (!$access_token->isExpired())
				return false;
			if (!$access_token->isLongLived()) {
				try{
					$new_accessToken = $this->facebook->getOAuth2Client()->getLongLivedAccessToken($access_token);
					$access_token = $new_accessToken;
				} catch (Facebook\Exceptions\FacebookSDKException $e) {
				}
			}
			return (string)$access_token;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		}
		return false;
	}

	public function oauth_check_login() {
		$helper = $this->facebook->getRedirectLoginHelper();
		try{
			$accessToken = $helper->getAccessToken();
			if (isset($accessToken)) {
				if (!$accessToken->isLongLived()) {
					try{
						$new_accessToken = $this->facebook->getOAuth2Client()->getLongLivedAccessToken($accessToken);
						$accessToken = $new_accessToken;
					} catch (Facebook\Exceptions\FacebookSDKException $e) {
					}
				}
				$this->oauth_token = $accessToken;
				return true;
			}
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
			//echo $e->getMessage();exit;
			//exit;
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
			//echo $e->getMessage();exit;
			//exit;
		}
		return false;
	}

	public function oauth_get_login_url($callback_url) {
		$helper = $this->facebook->getRedirectLoginHelper();
		return $helper->getLoginUrl($callback_url, $this->permissions);
	}

	public function query($token, $api, $method = 'GET') {
		if ($token === false)
			return false;
		try{
			$this->facebook->setDefaultAccessToken($token);
			if (!strcasecmp($method, 'GET')) {
				$response = $this->facebook->get($api);
				return $response;
			}
		} catch(Facebook\Exceptions\FacebookResponseException $e) {
		} catch(Facebook\Exceptions\FacebookSDKException $e) {
		}
		return false;
	}

}
