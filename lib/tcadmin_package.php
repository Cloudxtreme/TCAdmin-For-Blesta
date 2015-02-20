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
class TcadminPackage extends Module {

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
            "tcadmin_slots", 
            "tcadmin_service_id", 
            "game_hostname", 
            "game_rcon_password", 
            "game_private_password", 
            "game_slots", 
            "voice_slots"
        );
    }

    /**
     * Validates input data when attempting to add a package, returns the meta
     * data to save when adding a package. Performs any action required to add
     * the package on the remote server. Sets Input errors on failure,
     * preventing the package from being added.
     *
     * @param array An array of key/value pairs used to add the package
     * @return array A numerically indexed array of meta fields to be stored for this package containing:
     *  - key The key for this meta field
     *  - value The value for this key
     *  - encrypted Whether or not this field should be encrypted (default 0, not encrypted)
     * @see Module::getModule()
     * @see Module::getModuleRow()
     */
    public function add(array $vars=null) {
        // Set rules to validate input data
        $this->Input->setRules($this->getRules($vars));

        // Build meta data to return
        $meta = array();
        if ($this->Input->validates($vars)) {
            // Return all package meta fields
            foreach ($vars['meta'] as $key => $value) {
                $meta[] = array(
                    'key' => $key,
                    'value' => $value,
                    'encrypted' => 0
                );
            }
        }
        return $meta;
    }

    /**
     * Returns all fields used when adding/editing a package, including any
     * javascript to execute when the page is rendered with these fields.
     *
     * @param $vars stdClass A stdClass object representing a set of post fields
     * @return ModuleFields A ModuleFields object, containing the fields to render as well as any additional HTML markup to include
     */
    public function getFields($vars=null) {
        Loader::loadHelpers($this, array("Html"));

        $fields = new ModuleFields();

        // Server Type (Dropdown)
        $server_type = $fields->label(Language::_("TcadminPackage.package_fields.server_type", true), "tcadmin_server_type");
        $server_type->attach($fields->fieldSelect("meta[tcadmin_server_type]", array("Voice Server", "Game Server"), array('0', '1'), array('id'=>"tcadmin_server_type")));
        $fields->setField($server_type);

        // Game Server Options
        $game_id = $fields->label(Language::_("TcadminPackage.package_fields.game_id", true), "tcadmin_game_id");
        $game_id->attach($fields->fieldText("meta[game_id]", $this->Html->ifSet($vars->meta['game_id'], ""), array('id'=>"tcadmin_game_id")));
        $fields->setField($game_id);

        $gamevar_Xms = $fields->label(Language::_("TcadminPackage.package_fields.gamevar_Xms", true), "tcadmin_gamevar_Xms");
        $gamevar_Xms->attach($fields->fieldText("meta[gamevar_Xms]", $this->Html->ifSet($vars->meta['gamevar_Xms'], ""), array('id'=>"tcadmin_gamevar_Xms")));
        $fields->setField($gamevar_Xms);

        $gamevar_Xmx = $fields->label(Language::_("TcadminPackage.package_fields.gamevar_Xmx", true), "tcadmin_gamevar_Xmx");
        $gamevar_Xmx->attach($fields->fieldText("meta[gamevar_Xmx]", $this->Html->ifSet($vars->meta['gamevar_Xmx'], ""), array('id'=>"tcadmin_gamevar_Xmx")));
        $fields->setField($gamevar_Xmx);

        $master_game_slots = $fields->label(Language::_("TcadminPackage.package_fields.master_game_slots", true), "tcadmin_master_game_slots");
        $master_game_slots->attach($fields->fieldText("meta[master_game_slots]", $this->Html->ifSet($vars->meta['master_game_slots'], ""), array('id'=>"tcadmin_master_game_slots")));
        $fields->setField($master_game_slots);

        // Voice Server Options
        $voice_id = $fields->label(Language::_("TcadminPackage.package_fields.voice_id", true), "tcadmin_voice_id", array('style'=>"display:none"));
        $voice_id->attach($fields->fieldText("meta[voice_id]", $this->Html->ifSet($vars->meta['voice_id'], ""), array('id'=>"tcadmin_voice_id", 'style'=>"display:none;")));
        $fields->setField($voice_id);

        $master_voice_slots = $fields->label(Language::_("TcadminPackage.package_fields.master_voice_slots", true), "tcadmin_master_voice_slots", array('style'=>"display:none"));
        $master_voice_slots->attach($fields->fieldText("meta[master_voice_slots]", $this->Html->ifSet($vars->meta['master_voice_slots'], ""), array('id'=>"tcadmin_master_voice_slots", 'style'=>"display:none;")));
        $fields->setField($master_voice_slots);

        $upload_quota = $fields->label(Language::_("TcadminPackage.package_fields.upload_quota", true), "tcadmin_upload_quota", array('style'=>"display:none"));
        $upload_quota->attach($fields->fieldText("meta[upload_quota]", $this->Html->ifSet($vars->meta['upload_quota'], ""), array('id'=>"tcadmin_upload_quota", 'style'=>"display:none;")));
        $fields->setField($upload_quota);

        $download_quota = $fields->label(Language::_("TcadminPackage.package_fields.download_quota", true), "tcadmin_download_quota", array('style'=>"display:none"));
        $download_quota->attach($fields->fieldText("meta[download_quota]", $this->Html->ifSet($vars->meta['download_quota'], ""), array('id'=>"tcadmin_download_quota", 'style'=>"display:none;")));
        $fields->setField($download_quota);

        // Javascript
        $fields->setHtml("
            <script type=\"text/javascript\">
                $(document).ready(function() {
                    $(\".module_row_field\").hide();

                    if($('#tcadmin_game_id').val() != \"\")
                    {
                        ChangeTCAdminFields(1);
                    }
                    else
                    {
                        ChangeTCAdminFields(0);
                    }

                    $(\"#tcadmin_server_type\").change(function () {
                        ChangeTCAdminFields($('option:selected', this).val());
                    });

                    function ChangeTCAdminFields(option)
                    {
                        if(option == '0')
                        {
                            // Voice Server
                            $(\"#tcadmin_server_type\").val('0');
                            $('#tcadmin_game_id').hide();
                            $('#tcadmin_game_id').val(\"\");
                            $(\"label[for='tcadmin_game_id']\").hide();

                            $('#tcadmin_gamevar_Xms').hide();
                            $('#tcadmin_gamevar_Xms').val(\"\");
                            $(\"label[for='tcadmin_gamevar_Xms']\").hide();

                            $('#tcadmin_gamevar_Xmx').hide();
                            $('#tcadmin_gamevar_Xmx').val(\"\");
                            $(\"label[for='tcadmin_gamevar_Xmx']\").hide();

                            $('#tcadmin_master_game_slots').hide();
                            $('#tcadmin_master_game_slots').val(\"\");
                            $(\"label[for='tcadmin_master_game_slots']\").hide();

                            $('#tcadmin_voice_id').show();
                            $(\"label[for='tcadmin_voice_id']\").show();

                            $('#tcadmin_upload_quota').show();
                            $(\"label[for='tcadmin_upload_quota']\").show();

                            $('#tcadmin_download_quota').show();
                            $(\"label[for='tcadmin_download_quota']\").show();

                            $('#tcadmin_master_voice_slots').show();
                            $(\"label[for='tcadmin_master_voice_slots']\").show();
                        }
                        else
                        {
                            // Game Server
                            $(\"#tcadmin_server_type\").val('1');
                            $('#tcadmin_voice_id').hide();
                            $('#tcadmin_voice_id').val(\"\");
                            $(\"label[for='tcadmin_voice_id']\").hide();

                            $('#tcadmin_upload_quota').hide();
                            $('#tcadmin_upload_quota').val(\"\");
                            $(\"label[for='tcadmin_upload_quota']\").hide();

                            $('#tcadmin_download_quota').hide();
                            $('#tcadmin_download_quota').val(\"\");
                            $(\"label[for='tcadmin_download_quota']\").hide();

                            $('#tcadmin_master_voice_slots').hide();
                            $('#tcadmin_master_voice_slots').val(\"\");
                            $(\"label[for='tcadmin_master_voice_slots']\").hide();

                            $('#tcadmin_game_id').show();
                            $(\"label[for='tcadmin_game_id']\").show();

                            $('#tcadmin_gamevar_Xms').show();
                            $(\"label[for='tcadmin_gamevar_Xms']\").show();

                            $('#tcadmin_gamevar_Xmx').show();
                            $(\"label[for='tcadmin_gamevar_Xmx']\").show();

                            $('#tcadmin_master_game_slots').show();
                            $(\"label[for='tcadmin_master_game_slots']\").show();
                        }
                    }
                });
            </script>
        ");

        return $fields;
    }

    /**
     * Builds and returns the rules required to add/edit a package
     *
     * @param array $vars An array of key/value data pairs
     * @return array An array of Input rules suitable for Input::setRules()
     */
    private function getRules(array $vars) {
        $rules = array();
        return $rules;
    }
}
?>
