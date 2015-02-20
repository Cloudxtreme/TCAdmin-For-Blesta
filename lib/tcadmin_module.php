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
class TcadminModule {

    /**
     * Initialize
     */
    public function __construct() {
        // Load required components
        Loader::loadComponents($this, array("Input"));
    }

    /**
     * Retrieves a list of Input errors, if any
     */
    public function errors() {
        return $this->Input->errors();
    }

    /**
     * Fetches the module keys usable in email tags
     *
     * @return array A list of module email tags
     */
    public function getEmailTags() {
        return array(
        	"hostname", 
            "tcadmin_username", 
            "tcadmin_password"
        );
    }

    /**
	 * Adds the module row on the remote server. Sets Input errors on failure,
	 * preventing the row from being added.
	 * =
	 * @param array $vars An array of module info to add
	 * @return array A numerically indexed array of meta fields for the module row containing:
	 * 	- key The key for this meta field
	 * 	- value The value for this key
	 * 	- encrypted Whether or not this field should be encrypted (default 0, not encrypted)
	 */
    public function addRow(array &$vars) {
        $meta_fields = array("hostname", "username", "password");
		$encrypted_fields = array("username", "password");

		$this->Input->setRules($this->getRowRules($vars));

		// Validate module row
		if ($this->Input->validates($vars)) {

			// Build the meta data for this row
			$meta = array();
			foreach ($vars as $key => $value) {

				if (in_array($key, $meta_fields)) {
					$meta[] = array(
						'key' => $key,
						'value' => $value,
						'encrypted' => in_array($key, $encrypted_fields) ? 1 : 0
					);
				}
			}
			return $meta;
		}
    }

    /**
	 * Builds and returns the rules required to add/edit a module row (e.g. server)
	 *
	 * @param array $vars An array of key/value data pairs
	 * @return array An array of Input rules suitable for Input::setRules()
	 */
	private function getRowRules(&$vars) {
		return array(
			'hostname' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Tcadmin.!error.hostname", true)
				)
			),
			'username' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Tcadmin.!error.username", true)
				)
			),
			'password' => array(
				'empty' => array(
					'rule' => "isEmpty",
					'negate' => true,
					'message' => Language::_("Tcadmin.!error.password", true)
				)
			)
		);
	}
}
?>