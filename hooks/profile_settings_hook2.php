//<?php

/**
 * @package      iAwards
 * @author       <a href='http://www.invisionizer.com'>Invisionizer</a>
 * @copyright    (c) 2015 Invisionizer
 */
class profile_settings_hook2SettingsForm extends _HOOK_CLASS_ {

    protected function _gw2integration() {
        try {
            if (!\IPS\Application::appIsEnabled('gw2integration')) {
                return FALSE;
            }
            \IPS\Application::applications();
            $gw2integration = \IPS\Application::load('gw2integration');
            $apiKeyData = $gw2integration->getAPIKeyForUser(\IPS\Member::loggedIn()->member_id);

            $form = new \IPS\Helpers\Form;
            $form->add(new \IPS\Helpers\Form\Text('GuildWars 2 API Key', $apiKeyData["u_api_key"]));

            $member = \IPS\Member::load(\IPS\Member::loggedIn()->member_id);

            return \IPS\Theme::i()->getTemplate('profile', 'gw2integration', 'front')->apiKeySettings($form);
        } catch (\RuntimeException $e) {
            if (method_exists(get_parent_class(), __FUNCTION__)) {
                return call_user_func_array('parent::' . __FUNCTION__, func_get_args());
            } else {
                throw $e;
            }
        }
    }

}
