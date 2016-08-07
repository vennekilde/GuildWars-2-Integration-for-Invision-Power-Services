//<?php

class gw2integration_hook_profile_settings_form_hook extends _HOOK_CLASS_
{
    
    protected function _gw2integration() {
        try {
            if (!\IPS\Application::appIsEnabled('gw2integration')) {
                return FALSE;
            }
            \IPS\Application::applications();
            $gw2integration = \IPS\Application::load('gw2integration');
            $messages = array();
            $memberId = \IPS\Member::loggedIn()->member_id;
            
            //Check if trying to save
            if(isset($_POST["form_submitted"]) && $_POST["form_submitted"] == 1 && isset($_POST["gw2_api_key"])){
                try{
                    $gw2integration->setAPIKeyForUser($memberId, $_POST["gw2_api_key"]);
                    
                    $messages[] = array("type" => 0, "text" => "Success");
                } catch(\Exception $e){
                    if($e instanceof \IPS\gw2integration\Exception\_GW2APIKeyException){
                        $messages[] = array("type" => 1, "text" => "Could not save API Key\nPlease ensure you have entered a valid API Key\nAPI Error: ".$e->getResponse());
                    } else {
                        $messages[] = array("type" => 1, "text" => "Could not save API Key\nError: ".$e->getMessage());
                    }
                }
            }
            
            $apiKeyData = $gw2integration->getAPIKeyForUser($memberId);

            $form = new \IPS\Helpers\Form;
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
                        If any of these are missing, we wont be able to display information hidden behind the missing permissions<br />More fine grained privacy controls can be adjust below
                    </div>');
            $form->add(new \IPS\Helpers\Form\Text('gw2_api_key', $apiKeyData["u_api_key"]));
            $form->addHTML("<hr class='ipsHr'><h3 class='ipsPad'>Privacy Settings</h3><div class='ipsPad'>Work in progress</div>");
            
            return \IPS\Theme::i()->getTemplate('profile', 'gw2integration', 'front')->profileSettingsTab($form, $messages);
        } catch (\RuntimeException $e) {
            if (method_exists(get_parent_class(), __FUNCTION__)) {
                return call_user_func_array('parent::' . __FUNCTION__, func_get_args());
            } else {
                throw $e;
            }
        }
    }
}