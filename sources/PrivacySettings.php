<?php

/*
 * The MIT License
 *
 * Copyright 2015 jeppe.
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

namespace IPS\gw2integration;

/**
 * Description of GW2APICommunicator
 *
 * @author jeppe
 */

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

class _PrivacySettings {
    public $privacySettings;

    public $pvpTabSettings = array(
        "display_pvp_stats",
        "display_pvp_profession_stats",
        "display_pvp_seasons",
        "display_pvp_games"
    );
    
    public $overviewSettings = array(
        "display_account_name",
        "display_world",
        "display_pvp_seasons",
        "display_ap",
        "display_wvw_rank",
        "display_fractal_level",
        "display_pvp_rank"
    );
    public $overviewDetailedSettings = array(
        "display_account_name",
        "display_world",
        "display_played_since",
        "display_playtime",
        "display_ap",
        "display_wvw_rank",
        "display_fractal_level",
        "display_pvp_rank",
        "display_commander",
        "display_game_version"
    );
    
    /**
     * 
     * @param type $owner
     * @param boolean $overviewOnly
     */
    public function __construct($privacySettings) {
        $this->privacySettings = $privacySettings;
    }
    
    
    public function getPrivacySetting($privacySettingName){
        if(empty($this->privacySettings)){
            return true;
        } else {
            return $this->privacySettings[$privacySettingName];
        }
    }
    
    public function displayPvP(){
        foreach($this->pvpTabSettings AS $setting){
            if($this->getPrivacySetting($setting)){
                return true;
            }
        }
        return false;
    }
    
    public function displayOverview(){
        foreach($this->overviewSettings AS $setting){
            if($this->getPrivacySetting($setting)){
                return true;
            }
        }
        return false;
    }
    public function displayDetailedOverview(){
        foreach($this->overviewDetailedSettings AS $setting){
            if($this->getPrivacySetting($setting)){
                return true;
            }
        }
        return false;
    }
}