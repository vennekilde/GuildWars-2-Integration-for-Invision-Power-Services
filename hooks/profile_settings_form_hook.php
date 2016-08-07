//<?php

class gw2integration_hook_profile_settings_form_hook extends _HOOK_CLASS_
{
    private $privacySettingForms = array(
        "general",
        array(
            "display_account_name",
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
            "display_pvp_games"
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
            
            //Check if trying to save
            if(isset($_POST["form_submitted"]) && $_POST["form_submitted"] == 1){
                $messages = $this->saveGW2IntegrationPrivacySettings($gw2Integration, $memberId);
            } else {
                $messages = array();
            }
            
            $apiKeyData = $gw2Integration->getAPIKeyForUser($memberId);
            $privacySettings = $gw2Integration->getPrivacySettings($memberId);

            $form = new \IPS\Helpers\Form;
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
            $form->add(new \IPS\Helpers\Form\Text('gw2_api_key', $apiKeyData["u_api_key"]));
            
            $form->addTab('privacy_settings');
            
            foreach($this->privacySettingForms AS $privacySettingForm){
                if(is_array($privacySettingForm)){
                    foreach($privacySettingForm AS $privacySettingName){
                        $form->add( new \IPS\Helpers\Form\YesNo( $privacySettingName, $this->getPrivacySetting($privacySettingName, $privacySettings) ) );
                    }
                } else {
                    $form->addHeader($privacySettingForm);
                }
            }
            
            return \IPS\Theme::i()->getTemplate('profile', 'gw2integration', 'front')->profileSettingsTab($form, $messages);
        } catch (\RuntimeException $e) {
            if (method_exists(get_parent_class(), __FUNCTION__)) {
                return call_user_func_array('parent::' . __FUNCTION__, func_get_args());
            } else {
                throw $e;
            }
        }
    }
    
    private function saveGW2IntegrationPrivacySettings($gw2Integration, $memberId){
        if(isset($_POST["gw2_api_key"])){
            try{
                $apiKeyData = $gw2Integration->getAPIKeyForUser($memberId);
                if($apiKeyData["u_api_key"] != $_POST["gw2_api_key"]){
                    $gw2Integration->setAPIKeyForUser($memberId, $_POST["gw2_api_key"]);
                }
            } catch(\Exception $e){
                if($e instanceof \IPS\gw2integration\Exception\_GW2APIKeyException){
                    $messages[] = array("type" => 1, "text" => "Could not save API Key\nPlease ensure you have entered a valid API Key\nAPI Error: ".$e->getResponse());
                } else {
                    $messages[] = array("type" => 1, "text" => "Could not save API Key\nError: ".print_r($e, true));
                }
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