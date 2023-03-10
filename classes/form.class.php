<?php

class Form implements RenderInterface, AdminInterface {

	protected $content_id;
	protected $config;

	public function __construct($content_id, $config) {
		$this->content_id = $content_id;
		$this->config = $config;
	}

	public function __destruct() {
		// Destructor
	}

	private function CreateContentClasses($contents) {
		
		$regx = "/[a-zA-Z0-9_]+\:[a-zA-Z0-9_-]+|[a-zA-Z0-9_-]+/";
		$content_classes = array();
		
		foreach($contents as $content) {
			if (preg_match($regx, $content, $matches)) {
				$arr = split(":", $matches[0]);
				
				if (!$arr[1]) {
					$arr[1] = $arr[0];
					$arr[0] = "Content";
				}
				
				if (class_exists($arr[0])) {
					$content_classes[$content] = new $arr[0]($arr[1], $this->config);				
				}				
			}
		}
		return $content_classes;
	}
	
	private function RenderContentClasses(&$content_classes) {
		
		$rendered_classes = array();
		
		foreach(array_keys($content_classes) as $content_key) {
			if ($content_classes[$content_key] instanceof RenderInterface) {
				$rendered_classes[$content_key] = $content_classes[$content_key]->ReturnRenderedContent();
			}
		}
		
		return $rendered_classes;
	}
	
	public function ReturnRenderedContent(&$rendered_values = null, $override = false) {

		$return = "";
		$db = new Form_Database($this->config);

		if (!isset($_POST['submit'])) {
			if ($return = $db->GetFormTemplate($this->content_id)) {
				$t = new Template($return, 'string');
		
				if (isset($rendered_values)) {
					$contents = $t->GetContents();
					foreach ($contents as $key => $content) {
						if (isset($rendered_values[$content])) {
							unset($contents[$key]);
						}
					}
					
					$content_classes = $this->CreateContentClasses($contents);
					$rendered_classes = $this->RenderContentClasses($content_classes);
					$rendered_template = $t->ParseTemplate($rendered_classes, $override);
					
					$t = new Template($rendered_template, 'string');
					$return = $t->ParseTemplate($rendered_values);
				} else {
					$content_classes = $this->CreateContentClasses($t->GetContents());
					$rendered_classes = $this->RenderContentClasses($content_classes);
					$return = $t->ParseTemplate($rendered_classes);					
				}
			}
		} else {
			$class_name = $db->GetFormSubmitClass($this->content_id);

			if (class_exists($class_name)) {
				$s = new $class_name($this->content_id, $this->config);
				if ($s instanceof FormSubmitInterface) {
					$return = $s->ReturnSubmitForm();
				} else {
					$errC = new ContentPage("form-error-message", $this->config, "form");
					$e_message = array("{error-message}" => "Error: Unsupported interface for the form submit class.");
					$return = $errC->ReturnRenderedContent($e_message);
				}
			} else {
				$errC = new ContentPage("form-error-message", $this->config, "form");
				$e_message = array("{error-message}" => "Error: Form submit class not recognised.");
				$return = $errC->ReturnRenderedContent($e_message);
			}
		}

		unset($db);
		return $return;
	}

	public function ReturnAdminPage() {
		if (isset($_GET['config'])) {
			switch ($_GET['config']) {
				case 'add':
					$f = new Form("form-add-new-stage-1", $this->config);
					return $f->ReturnRenderedContent();
					break;
			}
		}

		$c = new ContentPage("form-default", $this->config);
		return $c->ReturnRenderedContent();
	}

}

class Form_Database extends Database {

	public function __construct($config) {
		if ($config instanceof Config) {
			parent::__construct($config->GetDatabaseConfig());
		} else if ($config instanceof DatabaseConfig) {
			parent::__construct($config);
		} else throw new Exception("Invalid argument");
	}

	public function __destruct() {
		parent::__destruct();
	}

	public function GetFormTemplate($form_name) {

		$return = false;

		if ($this->ExecuteMultiQuery("CALL ".$this->GetDBPrefix()."getFormTemplate('$form_name');")) {
			if ($result = $this->MultiQueryFetchResults()) {
				$row = $this->FetchRow($result);
				$return = $row[0];
			}
		}

		return $return;

	}

	public function GetFormSubmitClass($form_name) {
		$return = false;
		if ($result = $this->ExecuteQuery("SELECT f.`form_submit_class` FROM `".$this->GetDBPrefix()."forms` f WHERE f.`form_name` = '$form_name';")) {
			if ($row = $this->FetchRow($result)) {
				$return = $row[0];
			}
		}
		return $return;
	}

}

?>