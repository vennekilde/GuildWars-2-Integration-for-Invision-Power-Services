//<?php

class gw2integration_hook_profile_settings_form_hook extends _HOOK_CLASS_
{
    private $privacySettingForms = array(
        "general",
        array(
            /*"display_account_name",*/
            "display_world",
            "display_played_since",
            "display_playtime",
            "display_ap",
            "display_fractal_level",
            "display_wvw_rank",
            "display_commander",
            "display_guilds",
            "display_game_version",
            "display_characters"
        ), 
        "pvp",
        array(
            "display_pvp_rank",
            "display_pvp_stats",
            "display_pvp_profession_stats",
            "display_pvp_seasons",
            "display_pvp_games",
            "display_pvp_game_played_with"
        )
    );
    
    protected function _gw2integration() {
        try {
            if (!\IPS\Application::appIsEnabled('gw2integration')) {
                return FALSE;
            }
            \IPS\Application::applications();
            $gw2Integration = \IPS\Application::load('gw2integration');
            $memberId = \IPS\Member::loggedIn()->member_id;
            
            $guildsData = $gw2Integration->getGuildMembership($memberId);
            //Check if trying to save
            if(isset($_POST["gw2integration-settings_submitted"]) && $_POST["gw2integration-settings_submitted"] == 1){
                $messages = $this->saveGW2IntegrationPrivacySettings($gw2Integration, $memberId, $guildsData);
            } else {
                $messages = array();
            }
            
            $apiKeyData = $gw2Integration->getAPIKey($memberId);
            $privacySettings = $gw2Integration->getPrivacySettings($memberId);

            $form = new \IPS\Helpers\Form("gw2integration-settings");
            $form->addHTML("<!-- ".print_r($apiKeyData, true)." -->");
            $form->addTab("manage_api_key");
            $form->addMessage("Integrate your Guild Wars 2 account with the forum to show off your awesomeness to the world!");
            $form->addHTML(
                    '<div class="ipsPad">
                        Create an API Key at <a target="_blank" href="https://account.arena.net/applications/create">Guild Wars 2 Create API Key</a><br />
                        <h4>Recommended API Permissions</h4> 
                        <ul>
                            <li>characters</li>
                            <li>unlocks</li>
                            <li>pvp</li>
                            <li>builds</li>
                            <li>progression</li>
                        </ul><br />
                        If any of these are missing, we wont be able to display information hidden behind the missing permissions<br />More fine grained privacy controls can be adjust in the Privacy Settings tab
                    </div>');
            
            $form->add(new \IPS\Helpers\Form\Text('gw2_api_key', mb_strlen($apiKeyData["u_api_key"]) >= 72 ? $apiKeyData["u_api_key"] : ""));
            
            $form->addHTML('<input type="hidden" name="gw2integration-settings-delete-data" value="0">');
            $form->addHTML('<button type="button" id="gw2integration-settings-delete-key" data-controller="gw2integration.front.gw2.deletekey" class="ipsButton ipsButton_primary" role="button">Delete API Key</button>');
                
            $form->addHTML('<div class="ipsPad">Saving might take a few seconds while the server fetches your data from the API</div>');
            $form->addTab('guilds');
            
            $guildOptions = array();
            $represent = null;
            foreach($guildsData AS $guildData){
                $guildOptions[$guildData["g_uuid"]] = '['.$guildData["g_tag"].'] '.$guildData["g_name"];
                if($guildData["g_representing"] == "1"){
                    $represent = $guildData["g_uuid"];
                }
            }
            
            $form->addHTML(
                    '<div class="ipsPad">
                        Which guild do you want people on the forum to associate you with<br />
                        Here you can choose which guild will be display underneath your profile picture when you post stuff on the forum
                    </div>');
            $form->add( new \IPS\Helpers\Form\Radio( 'represent_guild', $represent, TRUE, array( 'options' => array_merge(array("none" => "None"), $guildOptions) ) ) );
            
            $form->addTab('privacy_settings');
            
            $form->addHTML('<div class="ipsColumns ipsColumns_collapseTablet ipsColumns_bothSpacing">');
            $first = true;
            foreach($this->privacySettingForms AS $privacySettingForm){
                if(is_array($privacySettingForm)){
                    foreach($privacySettingForm AS $privacySettingName){
                        $form->add( new \IPS\Helpers\Form\YesNo( $privacySettingName, $this->getPrivacySetting($privacySettingName, $privacySettings) ) );
                    }
                } else {
                    if($first){
                        $form->addHTML('<div class="ipsColumn ipsColumn_fluid">');
                        $first = false;
                    } else {
                        $form->addHTML('</div><div class="ipsColumn ipsColumn_fluid">');
                    }
                    $form->addHeader($privacySettingForm);
                }
            }
            $form->addHTML('</div></div>');
            
            \IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_gw2.js', 'gw2integration' ) );
            return \IPS\Theme::i()->getTemplate('profile', 'gw2integration', 'front')->profileSettingsTab($form, $messages);
        } catch (\RuntimeException $e) {
            if (method_exists(get_parent_class(), __FUNCTION__)) {
                return call_user_func_array('parent::' . __FUNCTION__, func_get_args());
            } else {
                throw $e;
            }
        }
    }
    
    private function saveGW2IntegrationPrivacySettings($gw2Integration, $memberId, $guildsData){
        $deleteKeyVal = $_POST["gw2integration-settings-delete-data"];
        
        if(isset($deleteKeyVal) && $deleteKeyVal > 0){
            if($deleteKeyVal == 1){
                $gw2Integration->deleteAPIKey($memberId);
                $messages[] = array("type" => 0, "text" => "Deleted API Key");
            } else {
                $gw2Integration->deleteAPIKeyAndData($memberId);
                $messages[] = array("type" => 0, "text" => "Deleted API Key and Data");
            }
        } else {
            if(isset($_POST["gw2_api_key"])){
                try{
                    $apiKeyData = $gw2Integration->getAPIKey($memberId);
                    if($apiKeyData["u_api_key"] != $_POST["gw2_api_key"]){
                        $gw2Integration->setAPIKey($memberId, $_POST["gw2_api_key"]);
                    }
                } catch(\Exception $e){
                    if($e instanceof \IPS\gw2integration\Exception\_GW2APIKeyException){
                        $messages[] = array("type" => 1, "text" => "Could not save API Key\nPlease ensure you have entered a valid API Key\nAPI Error: ".$e->getResponse());
                    } else {
                        $messages[] = array("type" => 1, "text" => "Could not save API Key\nError: ".$e->getMessage());
                    }
                }
            }
            
            $representGuildUUID = $_POST["represent_guild"];
            if(isset($representGuildUUID)){
                $valid = false;
                if($representGuildUUID == "none"){
                    $valid = true;
                    $representGuildUUID = null;
                } else {
                    foreach($guildsData AS $guildData){
                        if($guildData["g_uuid"] == $representGuildUUID){
                            $valid = true;
                            break;
                        }
                    }
                }
                if($valid){
                    $gw2Integration->setRepresentGuild($memberId, $representGuildUUID);
                }
            }
            
            $privacySettingValues = array();
            foreach($this->privacySettingForms AS $privacySettingForm){
                if(is_array($privacySettingForm)){
                    foreach($privacySettingForm AS $privacySettingName){
                        $value = isset($_POST[$privacySettingName . "_checkbox"]) ? ($_POST[$privacySettingName . "_checkbox"] == "1" ? true : false) : false;
                        $privacySettingValues[$privacySettingName] = $value;
                    }
                }
            }
            if(!empty($privacySettingValues)){
                //throw new \Exception(print_r($privacySettingValues, true));
                $privacySettingValues["u_id"] = $memberId;
                $gw2Integration->persistPrivacySettings($privacySettingValues);
            }

            if(empty($messages)){
                $messages[] = array("type" => 0, "text" => "Success");
            }
        }
        return $messages;
    }
    
    private function getPrivacySetting($privacySettingName, $privacySettings){
        if($privacySettings == null) {
            return true;
        } else {
            return $privacySettings[$privacySettingName];
        }
    }
}