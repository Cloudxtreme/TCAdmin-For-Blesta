<?php
/**
 * TCAdmin - The Game Hosting Control Panel
 * 
 * @author Jamie Sage
 * @link https://www.jamies-servers.co.uk
 *
 * With thanks to the Blesta team and TCAdmin
 * @link http://www.blesta.com/
 * @link http://www.tcadmin.com/
 * @link http://help.tcadmin.com/Billing_API_Examples
 */
class Tcadmin extends Module {
	/**
	 * Initializes the module
	 */
	public function __construct() {
		if (!isset($this->ModuleManager))
			Loader::loadModels($this, array("ModuleManager"));

		// Load components required by this module
		Loader::loadComponents($this, array("Input"));

		// Load the language required by this module
		Language::loadLang("tcadmin", null, dirname(__FILE__) . DS . "language" . DS);

        // Load module config
        $this->loadConfig(dirname(__FILE__) . DS . "config.json");

        // Load additional config settings
        Configure::load("tcadmin", dirname(__FILE__) . DS . "config" . DS);
	}

    /**
     * Loads a library class
     *
     * @param string $command The filename of the class to load
     */
    private function loadLib($command) {
        Loader::load(dirname(__FILE__) . DS . "lib" . DS . $command . ".php");
    }

    /**
	 * Initializes the TcadminApiActions and returns an instance of that object
	 *
	 * @param string $hostname The tcadmin hostname
	 * @param string $user The admin's username
	 * @param string $password the admin's password
	 * @return Tcadmin The TcadminApi instance
	 */
    private function getApi($hostname, $user, $password) {
        Loader::load(dirname(__FILE__) . DS . "apis" . DS . "tcadmin_api.php");
		return new TcadminApi($hostname, $user, $password);
    }

    /**
     * Returns an array of key values for fields stored for a module, package,
     * and service under this module, used to substitute those keys with their
     * actual module, package, or service meta values in related emails.
     *
     * @return array A multi-dimensional array of key/value pairs where each key is one of 'module', 'package', or 'service' and each value is a numerically indexed array of key values that match meta fields under that category.
     * @see Modules::addModuleRow()
     * @see Modules::editModuleRow()
     * @see Modules::addPackage()
     * @see Modules::editPackage()
     * @see Modules::addService()
     * @see Modules::editService()
     */
    public function getEmailTags() {
    	$this->loadLib("tcadmin_module");
    	$module = new TcadminModule();

    	$this->loadLib("tcadmin_package");
    	$package = new TcadminPackage();

    	return array(
    		'module' => $module->getEmailTags(),
    		'package' => $package->getEmailTags(),
    		'service' => array(
                "tcadmin_username", // The clients username
                "tcadmin_password", // The clients password
                "tcadmin_slots", 
                "tcadmin_service_id", 
                "game_hostname", 
                "game_rcon_password", 
                "game_private_password", 
                "game_slots", 
                "voice_slots"
            )
    	);
    }

	/**
	 * Returns the rendered view of the manage module page
	 *
	 * @param mixed $module A stdClass object representing the module and its rows
	 * @param array $vars An array of post data submitted to or on the manage module page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the manager module page
	 */
	public function manageModule($module, array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("manage", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "tcadmin" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		$this->view->set("module", $module);

		return $this->view->fetch();
	}

	/**
	 * Returns the rendered view of the add module row page
	 *
	 * @param array $vars An array of post data submitted to or on the add module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the add module row page
	 */
	public function manageAddRow(array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("add_row", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "tcadmin" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		$this->view->set("vars", (object)$vars);
		return $this->view->fetch();
	}

	/**
	 * Returns the rendered view of the edit module row page
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of post data submitted to or on the edit module row page (used to repopulate fields after an error)
	 * @return string HTML content containing information to display when viewing the edit module row page
	 */
	public function manageEditRow($module_row, array &$vars) {
		// Load the view into this object, so helpers can be automatically added to the view
		$this->view = new View("edit_row", "default");
		$this->view->base_uri = $this->base_uri;
		$this->view->setDefaultView("components" . DS . "modules" . DS . "tcadmin" . DS);

		// Load the helpers required for this view
		Loader::loadHelpers($this, array("Form", "Html", "Widget"));

		if (empty($vars))
			$vars = $module_row->meta;

		$this->view->set("vars", (object)$vars);
		return $this->view->fetch();
	}

	/**
	 * Adds the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being added.
	 *
	 * @param array $vars An array of module info to add
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function addModuleRow(array &$vars) {
        // Add the module row
        $this->loadLib("tcadmin_module");
        $module = new TcadminModule();
        $meta = $module->addRow($vars);

        if (($errors = $module->errors()))
            $this->Input->setErrors($errors);
        else
            return $meta;
	}

    /**
     * Deletes the module row on the remote server. Sets Input errors on failure,
     * preventing the row from being deleted.
     *
     * @param stdClass $module_row The stdClass representation of the existing module row
     */
    public function deleteModuleRow($module_row) {
        return null;
    }

	/**
	 * Edits the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being updated.
	 *
	 * @param stdClass $module_row The stdClass representation of the existing module row
	 * @param array $vars An array of module info to update
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
	public function editModuleRow($module_row, array &$vars) {
		return $this->addModuleRow($vars);
	}

    /**
	 * Validates input data when attempting to add a package, returns the meta
	 * data to save when adding a package. Performs any action required to add
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being added.
	 *
	 * @param array An array of key/value pairs used to add the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addPackage(array $vars=null) {
        // Add a package
        $this->loadLib("tcadmin_package");
        $package = new TcadminPackage();
        $meta = $package->add($vars);

        if (($errors = $package->errors()))
            $this->Input->setErrors($errors);
        else
            return $meta;
	}

	/**
	 * Validates input data when attempting to edit a package, returns the meta
	 * data to save when editing a package. Performs any action required to edit
	 * the package on the remote server. Sets Input errors on failure,
	 * preventing the package from being edited.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array An array of key/value pairs used to edit the package
	 * @return array A numerically indexed array of meta fields to be stored for this package containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editPackage($package, array $vars=null) {
        // Same as adding
        return $this->addPackage($vars);
	}

    /**
	 * Returns all fields used when adding/editing a package, including any
	 * javascript to execute when the page is rendered with these fields.
	 *
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containing the fields to render as well as any additional HTML markup to include
	 */
   public function getPackageFields($vars=null) {
    	// Fetch the package fields
        $this->loadLib("tcadmin_package");
        $package = new TcadminPackage();
        return $package->getFields($vars);
    }

    /**
     * Returns a noun used to refer to a module row (e.g. "Server")
     *
     * @return string The noun used to refer to a module row
     */
    public function moduleRowName() {
    	return "Server";
    }
    
    /**
     * Returns a noun used to refer to a module row in plural form (e.g. "Servers", "VPSs", "Reseller Accounts", etc.)
     *
     * @return string The noun used to refer to a module row in plural form
     */
    public function moduleRowNamePlural() {
    	return "Servers";
    }
    
    /**
     * Returns a noun used to refer to a module group (e.g. "Server Group")
     *
     * @return string The noun used to refer to a module group
     */
    public function moduleGroupName() {
    	return "Server Group";
    }
    
    /**
     * Returns the key used to identify the primary field from the set of module row meta fields.
     *
     * @return string The key used to identify the primary field from the set of module row meta fields
     */
    public function moduleRowMetaKey() {
    	return "hostname";
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * admin interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getAdminServiceInfo($service, $package) {
    	$row = $this->getModuleRow();
    	
    	// Load the view into this object, so helpers can be automatically added to the view
    	$this->view = new View("admin_service_info", "default");
    	$this->view->base_uri = $this->base_uri;
    	$this->view->setDefaultView("components" . DS . "modules" . DS . "tcadmin" . DS);
    	
    	// Load the helpers required for this view
    	Loader::loadHelpers($this, array("Form", "Html"));

    	$this->view->set("module_row", $row);
    	$this->view->set("package", $package);
    	$this->view->set("service", $service);
    	$this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));
    	
    	return $this->view->fetch();
    }

    /**
     * Fetches the HTML content to display when viewing the service info in the
     * client interface.
     *
     * @param stdClass $service A stdClass object representing the service
     * @param stdClass $package A stdClass object representing the service's package
     * @return string HTML content containing information to display when viewing the service info
     */
    public function getClientServiceInfo($service, $package) {
    	$row = $this->getModuleRow();
    	
    	// Load the view into this object, so helpers can be automatically added to the view
    	$this->view = new View("client_service_info", "default");
    	$this->view->base_uri = $this->base_uri;
    	$this->view->setDefaultView("components" . DS . "modules" . DS . "tcadmin" . DS);
    	
    	// Load the helpers required for this view
    	Loader::loadHelpers($this, array("Form", "Html"));

    	$this->view->set("module_row", $row);
    	$this->view->set("package", $package);
    	$this->view->set("service", $service);
    	$this->view->set("service_fields", $this->serviceFieldsToObject($service->fields));
    	
    	return $this->view->fetch();
    }

    /**
     * Returns all tabs to display to a client when managing a service whose
     * package uses this module
     *
     * @param stdClass $package A stdClass object representing the selected package
     * @return array An array of tabs in the format of method => title. Example: array('methodName' => "Title", 'methodName2' => "Title2")
     */
    public function getClientTabs($package) {
    	return array(
    		'tabClientTCAdmin' => Language::_("Tcadmin.tab.tcadmin", true)
    	);
    }

    /**
     * Client Actions tab (boot, reboot, shutdown, etc.)
     *
     * @param stdClass $package A stdClass object representing the current package
     * @param stdClass $service A stdClass object representing the current service
     * @param array $get Any GET parameters
     * @param array $post Any POST parameters
     * @param array $files Any FILES parameters
     * @return string The string representing the contents of this tab
     */
    public function tabClientTCAdmin($package, $service, array $get=null, array $post=null, array $files=null) {
    	$this->view = new View("tab_tcadmin", "default");
    	$this->view->base_uri = $this->base_uri;
    	$this->view->setDefaultView("components" . DS . "modules" . DS . "tcadmin" . DS);

    	// Load the helpers required for this view
    	Loader::loadHelpers($this, array("Form", "Html"));
    	
    	$row = $this->getModuleRow();
    	$this->view->set("module_row", $row);

    	return $this->view->fetch();
    }

	/**
	 * Adds the service to the remote server. Sets Input errors on failure,
	 * preventing the service from being added.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being added (if the current service is an addon service service and parent service has already been provisioned)
	 * @param string $status The status of the service being added. These include:
	 * 	- active
	 * 	- canceled
	 * 	- pending
	 * 	- suspended
	 * @return array A numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function addService($package, array $vars=null, $parent_package=null, $parent_service=null, $status="pending") {
        // Fetch the module row
        $row = $this->getModuleRow();

		if (!$row) {
			$this->Input->setErrors(array('module_row' => array('missing' => Language::_("Tcadmin.!error.module_row.missing", true))));
			return;
		}

        // Get the API
        $api = $this->getApi((isset($row->meta->hostname) ? $row->meta->hostname : ""), (isset($row->meta->username) ? $row->meta->username : ""), (isset($row->meta->password) ? $row->meta->password : ""));

        // Add the service
        $meta = $api->add($package, $vars, $parent_package, $parent_service, $status);
        $this->logResponses($api->getLogs());

        return $meta;
	}

	/**
	 * Suspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being suspended.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being suspended (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function suspendService($package, $service, $parent_package=null, $parent_service=null) {
		/// Fetch the module row
        $row = $this->getModuleRow();

		if (!$row) {
			$this->Input->setErrors(array('module_row' => array('missing' => Language::_("Tcadmin.!error.module_row.missing", true))));
			return;
		}

        // Get the API
        $api = $this->getApi((isset($row->meta->hostname) ? $row->meta->hostname : ""), (isset($row->meta->username) ? $row->meta->username : ""), (isset($row->meta->password) ? $row->meta->password : ""));

        // Suspend the service
        $meta = $api->suspend($package, $service, $parent_package, $parent_service);
		
		return null;
	}

	/**
	 * Unsuspends the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being unsuspended.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being unsuspended (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function unsuspendService($package, $service, $parent_package=null, $parent_service=null) {
		/// Fetch the module row
        $row = $this->getModuleRow();

		if (!$row) {
			$this->Input->setErrors(array('module_row' => array('missing' => Language::_("Tcadmin.!error.module_row.missing", true))));
			return;
		}

        // Get the API
        $api = $this->getApi((isset($row->meta->hostname) ? $row->meta->hostname : ""), (isset($row->meta->username) ? $row->meta->username : ""), (isset($row->meta->password) ? $row->meta->password : ""));

        // Suspend the service
        $meta = $api->unsuspend($package, $service, $parent_package, $parent_service);
		return null;
	}

	/**
	 * Cancels the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being canceled.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being canceled (if the current service is an addon service)
	 * @return mixed null to maintain the existing meta fields or a numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function cancelService($package, $service, $parent_package=null, $parent_service=null) {
		/// Fetch the module row
        $row = $this->getModuleRow();

		if (!$row) {
			$this->Input->setErrors(array('module_row' => array('missing' => Language::_("Tcadmin.!error.module_row.missing", true))));
			return;
		}

        // Get the API
        $api = $this->getApi((isset($row->meta->hostname) ? $row->meta->hostname : ""), (isset($row->meta->username) ? $row->meta->username : ""), (isset($row->meta->password) ? $row->meta->password : ""));

        // Suspend the service
        $meta = $api->cancel($package, $service, $parent_package, $parent_service);
		return null;
	}

	/**
	 * Edits the service on the remote server. Sets Input errors on failure,
	 * preventing the service from being edited.
	 *
	 * @param stdClass $package A stdClass object representing the current package
	 * @param stdClass $service A stdClass object representing the current service
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param stdClass $parent_package A stdClass object representing the parent service's selected package (if the current service is an addon service)
	 * @param stdClass $parent_service A stdClass object representing the parent service of the service being edited (if the current service is an addon service)
	 * @return array A numerically indexed array of meta fields to be stored for this service containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 * @see Module::getModule()
	 * @see Module::getModuleRow()
	 */
	public function editService($package, $service, array $vars=array(), $parent_package=null, $parent_service=null) {
		/// Fetch the module row
        $row = $this->getModuleRow();

		if (!$row) {
			$this->Input->setErrors(array('module_row' => array('missing' => Language::_("Tcadmin.!error.module_row.missing", true))));
			return;
		}

        // Get the API
        $api = $this->getApi((isset($row->meta->hostname) ? $row->meta->hostname : ""), (isset($row->meta->username) ? $row->meta->username : ""), (isset($row->meta->password) ? $row->meta->password : ""));

        // Suspend the service
        $meta = $api->edit($package, $service, $vars, $parent_package, $parent_service);
		return null;
	}

	/**
	 * Returns all fields to display to a client attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */	
	public function getAdminAddFields($package, $vars=null) {
		return $this->getConfigFields($package, $vars);
	}


	/**
	 * Returns all fields to display to a client attempting to add a service with the module
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param $vars stdClass A stdClass object representing a set of post fields
	 * @return ModuleFields A ModuleFields object, containg the fields to render as well as any additional HTML markup to include
	 */	
	public function getClientAddFields($package, $vars=null) {
		return $this->getConfigFields($package, $vars);
	}

	public function getConfigFields($package, $vars)
	{
		Loader::loadHelpers($this, array("Html"));
		
		$fields = new ModuleFields();
		
		// Create hostname label
		$hostname = $fields->label(Language::_("Tcadmin.config.hostname", true), "game_hostname");
		// Create hostname field and attach to hostname label
		$hostname->attach($fields->fieldText("game_hostname", $this->Html->ifSet($vars->game_hostname, $this->Html->ifSet($vars->domain)), array('id'=>"game_hostname")));
		// Set the label as a field
		$fields->setField($hostname);
		
		// Create rcon_pass label
		$rcon_pass = $fields->label(Language::_("Tcadmin.config.rcon_pass", true), "game_rcon_password");
		// Create rcon_pass field and attach to rcon_pass label
		$rcon_pass->attach($fields->fieldText("game_rcon_password", $this->Html->ifSet($vars->game_rcon_password, $this->Html->ifSet($vars->domain)), array('id'=>"game_rcon_password")));
		// Set the label as a field
		$fields->setField($rcon_pass);

		// Create private_pass label
		$private_pass = $fields->label(Language::_("Tcadmin.config.private_pass", true), "game_private_password");
		// Create private_pass field and attach to private_pass label
		$private_pass->attach($fields->fieldText("game_private_password", $this->Html->ifSet($vars->game_private_password, $this->Html->ifSet($vars->domain)), array('id'=>"game_private_password")));
		// Set the label as a field
		$fields->setField($private_pass);

		return $fields;
	}

	/**
	 * Attempts to validate service info. This is the top-level error checking method. Sets Input errors on failure.
	 *
	 * @param stdClass $package A stdClass object representing the selected package
	 * @param array $vars An array of user supplied info to satisfy the request
	 * @param boolean $edit True if this is an edit, false otherwise
	 * @return boolean True if the service validates, false otherwise. Sets Input errors when false.
	 */
	public function validateService($package, array $vars=null, $edit=false) {
        // Set any input rules to validate against
        $rules = array(
            'game_hostname' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("Tcadmin.!error.config.hostname", true)
                )
            ),
            'game_rcon_password' => array(
                'empty' => array(
                    'rule' => "isEmpty",
                    'negate' => true,
                    'message' => Language::_("Tcadmin.!error.config.rcon_pass", true)
                )
            )
        );
 
        $this->Input->setRules($rules);
 
        // Determine whether the input validates
        return $this->Input->validates($vars);
	}

	/**
	 * Logs a set of input/output responses
	 *
	 * @param array $logs An array of logs, each containing an array keyed by direction (input/output), i.e.:
	 *  - input/output
	 *      url The URL of the request
	 *      data The serialized data to be logged
	 *      success True or false, whether the request was successful
	 */
	private function logResponses($log) {
		/// Fetch the module row
        $row = $this->getModuleRow();
	    $this->log($row->meta->hostname . "|" . $log[0][1], $log[0][0], "input", $log[0][2]);
	}
}
?>