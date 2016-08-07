//<?php

class gw2integration_hook_front_topics_hook extends _HOOK_CLASS_
{

/* !Hook Data - DO NOT REMOVE */
public static function hookData() {
 return array_merge_recursive( array (
  'postContainer' => 
  array (
    0 => 
    array (
      'selector' => '.cAuthorPane_LeftMargin, .cAuthorPane_info',
      'type' => 'add_inside_end',
      'content' => '{{\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( \'style.css\', \'gw2integration\', \'front\' ) );}}
{{$gw2Data = new \IPS\gw2integration\GW2Data($comment->author(), true);}}
<li style="padding-top: 10px;">{template="accountOverview" group="global" app="gw2integration" location="front" params="$gw2Data->account, $gw2Data->pvpStats, $gw2Data->seasonStats, $gw2Data->privacySettings, true"}</li>',
    ),
  ),
), parent::hookData() );
}
/* End Hook Data */




























































}