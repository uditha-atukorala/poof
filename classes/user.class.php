<?php

class User implements RenderInterface {
	
	protected $content_id;
	protected $config;
	
	public function __construct($content_id, $config) {
		$this->content_id = $content_id;
		$this->config = $config;
	}
	
	private function ReturnUserPage() {
		
		$return = "";
		$request = split("/", $_GET['q'], 3);
		
		/*
		 * User page requests should be in
		 * ~/user/
		 * ~/user/login
		 * ~/user/logout
		 * format
		 */
		
		switch ($request[1]) {
			case 'logout':
				unset($_SESSION['user']);
				$return = "Logged out";
				
				// FIX ME: needs to redirect to the {url_prefix}
				header("refresh:5; /");
				break;
			case 'login':
			default:
				if (isset($_SESSION['user'])) {
					$return = "User information page";
				} else {
					$f = new Form("user-login", $this->config);
					$return = $f->ReturnRenderedContent();
					
					// Session var to store the REFERER
					if (session_id() == "") session_start();
					$_SESSION['HTTP_REFERER'] = getenv("HTTP_REFERER");
				}
		}
		
		return $return;
		
	}
	
	public function ReturnRenderedContent() {
		
		$return = "";
				
		if (session_id() == "") session_start();

		switch ($this->content_id) {
			case 'user-user':
				$return = $this->ReturnUserPage();
				break;
			case 'user-users':
				$return = "Not implemented yet, this is for looking at all users...";
				break;
			case 'login_link':
				if (isset($_SESSION['user'])) {
					$c = new Content("user-link-log-out", $this->config);
					$return = $c->ReturnRenderedContent(true);
				} else {
					$c = new Content("user-link-log-in", $this->config);
					$return = $c->ReturnRenderedContent(true);
				}
				break;
			case 'info_short':
			default:
				if (isset($_SESSION['user'])) {
					$s = new SystemUser($this->config);
					$c = new Content("{url_prefix}", $this->config);
					
					$return = "You are loggin in as ";
					$return .= "<a href=\"" . $c->ReturnRenderedContent() . "user/\">";
					$return .= $s->GetUserName($_SESSION['user']) . "</a>";
				} else {
					$return = "You are not logged in";
				}		
		}

		return $return;
		
	}
	
}

?>