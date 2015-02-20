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
class TcadminApi {
    private $log = array();
    private $host;
    private $user;
    private $pass;

    /**
     * Initialize
     *
     * @param string $hostname The TCAdmin server's hostname
     * @param string $username The administrators TCAdmin username
     * @param string $password The administrators TCAdmin password
     */
    public function __construct($hostname = '', $username = '', $password = '') {
        Loader::loadComponents($this, array("Input"));

        // Check to see if the hostname has an ending slash
        if(substr($hostname, -1) != "/") $hostname = $hostname . "/";

        // If the hostname has http or https, remove it
        //$hostname = str_replace("http://", "", $hostname);
        //$hostname = str_replace("https://", "", $hostname);

        // Format the hostname to direct to the correct page
        $this->host = $hostname . "billingapi.aspx";

        // Configure the username and password
        $this->user = $username;
        $this->pass = $password;
        $this->resetLogs();
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
     *  - active
     *  - canceled
     *  - pending
     *  - suspended
     * @return array A numerically indexed array of meta fields to be stored for this service containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function add($package, array $vars=null, $parent_package=null, $parent_service=null, $status="pending") {
        Loader::loadModels($this, array("Clients"));

        $client = $this->Clients->get($vars['client_id']);
        $tc_user = $this->generateUsername(stripslashes($client->username));
        $tc_pass = $this->generate_password();
        $tc_service_id = $this->getNextServiceID();
        $overwrite_g_slots = (isset($package->meta->master_game_slots) ? $package->meta->master_game_slots : 0);
        $game_slots = (isset($vars['configoptions']['game_slots']) ? $vars['configoptions']['game_slots'] : 0);
        $overwrite_v_slots = (isset($package->meta->master_voice_slots) ? $package->meta->master_voice_slots : 0);
        $voice_slots = (isset($vars['configoptions']['voice_slots']) ? $vars['configoptions']['voice_slots'] : 0);

        if($overwrite_g_slots != 0 || $overwrite_g_slots != "") $game_slots = $overwrite_g_slots;
        if($overwrite_v_slots != 0 || $overwrite_v_slots != "") $voice_slots = $overwrite_v_slots;

        $server_info = array(
            'tcadmin_username'      => $this->user,
            'tcadmin_password'      => $this->pass,
            'function'              => 'AddPendingSetup',
            'response_type'         => 'xml',
            'game_package_id'       => $tc_service_id,
            'voice_package_id'      => $tc_service_id,
            'client_id'             => $vars['client_id'],
            'user_email'            => $client->email,
            'user_fname'            => $client->first_name,
            'user_lname'            => $client->last_name,
            // Option options, removed to increase performance
            /*'user_address1'         => $client->address1,
            'user_address2'         => $client->address2,
            'user_city'             => $client->city,
            'user_state'            => $client->state,
            'user_zip'              => $client->zip,
            'user_country'          => $client->country,
            'user_phone1'           => null,
            'user_phone2'           => null,*/
            'user_name'             => $tc_user,
            'user_password'         => $tc_pass,
            'game_id'               => (isset($package->meta->game_id) ?$package->meta->game_id : ""),
            'game_slots'            => $game_slots,
            'game_private'          => 0,
            'game_additional_slots' => 0,
            'game_branded'          => (isset($vars['configoptions']['game_branded']) ? $vars['configoptions']['game_branded'] : 0),
            'game_priority'         => (isset($vars['configoptions']['game_priority']) ? $vars['configoptions']['game_priority'] : "Normal"),
            'game_hostname'         => (isset($vars['configoptions']['game_hostname']) ? $vars['configoptions']['game_hostname'] : $vars['game_hostname']),
            'game_rcon_password'    => (isset($vars['configoptions']['game_rcon_password']) ? $vars['configoptions']['game_rcon_password'] : $vars['game_rcon_password']),
            'game_private_password' => (isset($vars['configoptions']['game_private_password']) ? $vars['configoptions']['game_private_password'] : $vars['game_private_password']),
            'gamevar_Xms'           => (isset($package->meta->gamevar_Xms) ? $package->meta->gamevar_Xms : ""),
            'gamevar_Xmx'           => (isset($package->meta->gamevar_Xmx) ? $package->meta->gamevar_Xms : ""),
            'voice_slots'           => $voice_slots,
            'voice_package_id'      => (isset($package->meta->voice_id) ? $package->meta->voice_id : ""),
            'voice_upload_quota'    => (isset($package->meta->upload_quota) ? $package->meta->upload_quota : ""),
            'voice_download_quota'  => (isset($package->meta->download_quota) ? $package->meta->download_quota : ""),
            // TODO: Allow user to change default datacenter
            'game_datacenter'       => 1
        );

        if (isset($vars['use_module']) && $vars['use_module'] == "true") {
            // Attempt to submit the data we gathered above
            $submit = $this->submit($server_info);

            // If something went wrong, return FALSE (submit() has already logged the issue)
            if($submit === FALSE) return FALSE;

            $response = new SimpleXMLElement($submit);

            if($response->errorcode != 0)
            {
                // Something went wrong, roll back our actions
                // Remove the service we just created
                $this->deleteService($tc_service_id);

                // Log the response
                $this->log($response->errortext, "CreateServer", false);
                return;
            }
            else
            {
                $this->log("Successfully created service for user " . $response->returntext . ". (ReturnCode: " . $response->returncode . ")", "CreateServer", true);
            }
        }

        return array(
            array(
                'key' => "tcadmin_username",
                'value' => $tc_user,
                'encrypted' => 0
            ),
            array(
                'key' => "tcadmin_password",
                'value' => $tc_pass,
                'encrypted' => 1
            ),
            array(
                'key' => "tcadmin_slots",
                'value' => (($game_slots != 0) ? $game_slots : $voice_slots),
                'encrypted' => 0
            ),
            array(
                'key' => "tcadmin_service_id",
                'value' => $tc_service_id,
                'encrypted' => 0
            ),
            array(
                'key' => "game_hostname",
                'value' => (isset($vars['configoptions']['game_hostname']) ? $vars['configoptions']['game_hostname'] : $vars['game_hostname']),
                'encrypted' => 0
            ),
            array(
                'key' => "game_rcon_password",
                'value' => (isset($vars['configoptions']['game_rcon_password']) ? $vars['configoptions']['game_rcon_password'] : $vars['game_rcon_password']),
                'encrypted' => 1
            ),
            array(
                'key' => "game_private_password",
                'value' => (isset($vars['configoptions']['game_private_password']) ? $vars['configoptions']['game_private_password'] : $vars['game_private_password']),
                'encrypted' => 1
            ),
            array(
                'key' => "game_slots",
                'value' => $game_slots,
                'encrypted' => 0
            ),
            array(
                'key' => "voice_slots",
                'value' => $voice_slots,
                'encrypted' => 0
            )
        );
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
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function edit($package, $service, array $vars=array(), $parent_package=null, $parent_service=null) {
        // Update slots
        $server_info = array(
            'tcadmin_username'      => $this->user,
            'tcadmin_password'      => $this->pass,
            'function'              => 'UpdateSettings',
            'response_type'         => 'text',
            'client_package_id'     => $service->id,
            'game_slots'            => (isset($vars['configoptions']['game_slots']) ? $vars['configoptions']['game_slots'] : 0),
            'game_branded'          => (isset($vars['configoptions']['branded']) ? $vars['configoptions']['branded'] : 0),
            'game_priority'         => (isset($vars['configoptions']['game_priority']) ? $vars['configoptions']['game_priority'] : "Normal")
        );

        $this->submit($server_info);
        return null;
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
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function suspend($package, $service, $parent_package=null, $parent_service=null) {
        $server_info = array(
            'tcadmin_username'      => $this->user,
            'tcadmin_password'      => $this->pass,
            'function'              => 'SuspendGameAndVoiceByBillingID',
            'response_type'         => 'text',
            'client_package_id'     => $service->id,
        );

        $this->submit($server_info);
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
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function unsuspend($package, $service, $parent_package=null, $parent_service=null) {
        $server_info = array(
            'tcadmin_username'      => $this->user,
            'tcadmin_password'      => $this->pass,
            'function'              => 'UnSuspendGameAndVoiceByBillingID',
            'response_type'         => 'text',
            'client_package_id'     => $service->id,
        );

        $this->submit($server_info);
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
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function cancel($package, $service, $parent_package=null, $parent_service=null) {
        return $this->deleteService($service->id);
    }

    /**
     * Deletes a service from TCAdmin
     *
     * @param stdClass $billing_id A interger with the billing ID of the service to remove
     * @return null
     */
    public function deleteService($billing_id)
    {
        $server_info = array(
            'tcadmin_username'      => $this->user,
            'tcadmin_password'      => $this->pass,
            'function'              => 'DeleteGameAndVoiceByBillingID',
            'response_type'         => 'xml',
            'client_package_id'     => $billing_id,
        );

        $return = $this->submit($server_info);

        // If something went wrong, return FALSE (submit() has already logged the issue)
        if($return === FALSE) return FALSE;

        $response = new SimpleXMLElement($return);

        if($response->errorcode != 0)
        {
            // Something went wrong, log the response
            $this->log($response->errortext, "CancelService", false);
            return;
        }
        else
        {
            $this->log("Successfully removed service" . $response->returntext . ". (ReturnCode: " . $response->returncode . ")", "CreateServer", true);
        }

        return null;
    }

    /**
     * Submits an array to the hostname,
     *
     * @param array $fields An array containing TCAdmin variables
     * @return string XML
     * @see TcadminApi::add()
     */
    public function submit($fields) {
        $data = FALSE;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        curl_setopt($ch, CURLOPT_CAINFO, NULL);
        curl_setopt($ch, CURLOPT_CAPATH, NULL); 
        curl_setopt($ch, CURLOPT_URL, $this->host);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, /*http_build_query(*/$fields/*)*/);
        curl_setopt($ch, CURLOPT_HTTPHEADER, array('Expect:', 'Accept-Charset: UTF-8'));
        $data = curl_exec($ch);

        if($data === FALSE)
        {
            $this->log("0\t\t-1\tCurl error: " . curl_error($ch) . " Url: " . curl_getinfo ( $ch,  CURLINFO_EFFECTIVE_URL), "cURL Error", false);
        }

        curl_close($ch);
        return $data;
    }

    /**
     * Generates a random string to be used as a password
     *
     * @return string A random string
     */
    public function generate_password() {
        $string = "abcdefghijklmnopqrstuwxyzABCDEFGHIJKLMNOPQRSTUWXYZ0123456789";
        $pass = array(); //remember to declare $pass as an array
        $alphaLength = strlen($string) - 1; //put the length -1 in cache
        for ($i = 0; $i < 8; $i++) {
            $n = rand(0, $alphaLength);
            $pass[] = $string[$n];
        }
        return implode($pass); //turn the array into a string
    }

    /**
     * Converts an email into a username to be used with TCAdmin
     *
     * @param string $email A string containing a email address or username
     * @return string A formatted string to be used with TCAdmin
     * @see TcadminApi::add()
     */
    public function generateUsername($email) {
        $username = $email;

        if(strpos($email, '@'))
        {
            $parts = explode("@", $email);
            $username = $parts[0];
        }

        $string = str_replace(' ', '-', $username);
        $username = preg_replace('/[^A-Za-z0-9\-]/', '', $string);

        return $username;
    }

    /**
     * Get the services ID
     *
     * @return integer The service ID
     * @see TcadminApi::add()
     */
    public function getNextServiceID() {
        Loader::loadComponents($this, array("Record"));
        $this->Record->select("id")->from("services");
        return $this->Record->numResults() + 1;
    }

    /**
     * Gets all available game and voice servers
     *
     * @param string GetSupportedGames for game servers or GetSupportedVoiceServers for voice servers, default is GetSupportedGames 
     * @return array Array of supported game or voice servers
     * @see TcadminApi::add()
     */
    public function getSupportedServers($type = 'GetSupportedGames') {
        $server_info = array(
            'tcadmin_username'      => $this->user,
            'tcadmin_password'      => $this->pass,
            'function'              => $type,
            'response_type'         => 'xml'
        );

        return new SimpleXMLElement($this->submit($server_info));
    }

    /**
     * Records a set of input/output log entries for an API command
     *
     * @param array $input
     * @param array $output
     */
    protected function log($log2, $action, $success) {
        $this->log[] = array($log2, $action, $success);
    }

    /**
     * Retrieves any logs set
     *
     * @return array An array of logs containing input and output log data
     */
    public function getLogs() {
        return $this->log;
    }

    /**
     * Resets the logs
     */
    public function resetLogs() {
        $this->log = array();
    }
}
?>