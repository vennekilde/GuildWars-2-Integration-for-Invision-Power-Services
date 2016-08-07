//<?php

class gw2integration_hook_profile_settings_hook extends _HOOK_CLASS_
{

    /* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'settings' => 
  array (
    0 => 
    array (
      'selector' => '#elSettingsTabs > div.ipsColumns.ipsColumns_collapsePhone.ipsColumns_bothSpacing > div.ipsColumn.ipsColumn_wide > div.ipsSideMenu > ul.ipsSideMenu_list',
      'type' => 'add_inside_end',
      'content' => '{template="profileSettings" group="profile" location="front" app="gw2integration" params="$tab"}',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */






}