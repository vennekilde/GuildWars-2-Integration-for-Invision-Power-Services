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

class _GW2Data {

    private $overviewOnly;
    public $member;
    
    /**
     *
     * @var _Application 
     */
    protected $gw2integration;
    
    public $account;
    public $characters;
    public $crafting;
    public $guilds;
    public $games;
    public $professionStats;
    public $seasonStats;
    public $pvpStats;

    public $test;
    /**
     * 
     * @param type $owner
     * @param boolean $overviewOnly
     */
    public function __construct($owner, $overviewOnly = false) {
        $this->member = $owner;
        $this->overviewOnly = $overviewOnly;
        $this->init();
    }
    
    private function init(){
		\IPS\Application::applications();
		$this->gw2integration = \IPS\Application::load( 'gw2integration' );
        if($this->overviewOnly){
            $overviewData = $this->gw2integration->getOverviewData($this->member->member_id);
            $this->account = $overviewData;
            if(isset($overviewData["ps_rank"])){
                $this->pvpStats["ps_rank"] = $overviewData["ps_rank"];
                $this->pvpStats["ps_rank_floored"] = floor($this->pvpStats["ps_rank"] / 10) * 10;
                $this->pvpStats["ps_rank_rollovers"] = $overviewData["ps_rank_rollovers"];
            }
            if(isset($overviewData["season_current_division"])){
                $this->seasonStats["season_current_division"] = $overviewData["season_current_division"];
                $this->seasonStats["division_name"] = $overviewData["division_name"];
                $this->seasonStats["season_current_repeats"] = $overviewData["season_current_repeats"];
            }
        } else {
            $this->account = $this->gw2integration->getAccountData($this->member->member_id);
            
            if(isset($this->account["a_username"])){
                $this->account["total_playtime"] = 0;
                $characters = $this->gw2integration->getCharactersData($this->member->member_id);
                $crafting = $this->gw2integration->getCharacterCraftingProfessions($this->member->member_id);
                $guilds = $this->gw2integration->getGuildMembership($this->member->member_id);
                $games = $this->gw2integration->getPVPGames($this->member->member_id, 10);
                $professionStats = $this->gw2integration->getPVPProfessionsStats($this->member->member_id);
                $seasonStats = $this->gw2integration->getPVPSeasonStandingWithSeasonData($this->member->member_id);

                $this->characters = array();
                foreach($characters AS $character){
                    $character["c_age_text"] = $this->secondsToTime($character["c_age"]);
                    $this->characters[] = $character;
                    $this->account["total_playtime"] += $character["c_age"];
                }
                $this->account["total_playtime_text"] = $this->secondsToTime($this->account["total_playtime"]);
                
                $this->crafting = array();
                foreach($crafting AS $craftProfession){
                    $this->crafting[] = $craftProfession;
                }
                $this->guilds = array();
                foreach($guilds AS $guild){
                    $this->guilds[] = $guild;
                }
                $this->games = array();
                foreach($games AS $game){
                    $game["game_result_text"] = \IPS\gw2integration\Application::getPVPGameResultStringFromId($game["game_result"]);
                    switch($game["game_result"]){
                        case 0:
                            $game["game_result_css_class"] = "gw2i_red_color";
                            break;
                        case 1:
                            $game["game_result_css_class"] = "gw2i_blue_color";
                            break;
                        case 2:
                            $game["game_result_css_class"] = "gw2i_red_color";
                            break;
                        case 5:
                            $game["game_result_css_class"] = "gw2i_red_color";
                            break;
                        case 6:
                            $game["game_result_css_class"] = "gw2i_blue_color";
                            break;
                        default:
                            $game["game_result_css_class"] = "";
                            break;
                    }
                    $game["game_duration_string"] = gmdate("i:s", $game['game_duration']);
                    $game["game_time_since_ended_string"] = $this->getTimeDDHHMMSSStringShortLossy($game['game_time_since_ended'] * 1000);
                    $game["game_rating_type_string"] = \IPS\gw2integration\Application::getPvPGameRatingTypeStringFromId($game["game_rating_type"]);
                    $game["game_profession_name"] = \IPS\gw2integration\Utils\DataConversionUtils::getProfessionName($game["game_profession"]);
                    $game["game_map_name"] = \IPS\gw2integration\Utils\DataConversionUtils::getMapName($game["game_map_id"]);
                    $this->games[] = $game;
                }
                //$this->games = json_encode($this->games);
                $this->professionStats = array();
                foreach($professionStats AS $professionStat){
                    $this->professionStats[] = $professionStat;
                }
                $this->seasonStats = array();
                foreach($seasonStats AS $seasonStat){
                    $this->seasonStats[] = $seasonStat;
                }

                $this->pvpStats = $this->gw2integration->getPVPStats($this->member->member_id);
                if($this->pvpStats != null){
                    $this->pvpStats["ps_rank_floored"] = floor($this->pvpStats["ps_rank"] / 10) * 10;
                    
                    $this->pvpStats["ps_aggregate_wins_total"] = $this->pvpStats["ps_aggregate_wins"] + $this->pvpStats["ps_aggregate_byes"];
                    $this->pvpStats["ps_aggregate_losses_total"] = $this->pvpStats["ps_aggregate_losses"] + $this->pvpStats["ps_aggregate_forfeits"] + $this->pvpStats["ps_aggregate_desertions"];
                    
                    $this->pvpStats["ps_ladder_ranked_wins_total"] = 
                            $this->pvpStats["ps_ladder_ranked_wins"] + $this->pvpStats["ps_ladder_ranked_byes"]
                            + $this->pvpStats["ps_ladder_teamarenarated_wins"] + $this->pvpStats["ps_ladder_teamarenarated_byes"]
                            + $this->pvpStats["ps_ladder_soloarenarated_wins"] + $this->pvpStats["ps_ladder_soloarenarated_byes"];
                    $this->pvpStats["ps_ladder_ranked_losses_total"] = 
                            $this->pvpStats["ps_ladder_ranked_losses"] + $this->pvpStats["ps_ladder_ranked_forfeits"] + $this->pvpStats["ps_ladder_ranked_desertions"]
                            + $this->pvpStats["ps_ladder_teamarenarated_losses"] + $this->pvpStats["ps_ladder_teamarenarated_forfeits"] + $this->pvpStats["ps_ladder_teamarenarated_desertions"]
                            + $this->pvpStats["ps_ladder_soloarenarated_losses"] + $this->pvpStats["ps_ladder_soloarenarated_forfeits"] + $this->pvpStats["ps_ladder_soloarenarated_desertions"];
                    
                    $this->pvpStats["ps_ladder_unranked_wins_total"] = 
                            $this->pvpStats["ps_ladder_unranked_wins"] + $this->pvpStats["ps_ladder_unranked_byes"]
                            + $this->pvpStats["ps_ladder_none_wins"] + $this->pvpStats["ps_ladder_none_byes"];
                    $this->pvpStats["ps_ladder_unranked_losses_total"] = 
                            $this->pvpStats["ps_ladder_unranked_losses"] + $this->pvpStats["ps_ladder_unranked_forfeits"] + $this->pvpStats["ps_ladder_unranked_desertions"]
                            + $this->pvpStats["ps_ladder_none_losses"] + $this->pvpStats["ps_ladder_none_forfeits"] + $this->pvpStats["ps_ladder_none_desertions"];
                }
            }
        }
    }
    
    private function getTimeWWDDHHMMSS($time) {     
        $seconds = (int) ceil(($time / 1000) % 60);
        $minutes = (int) ($time / 60000) % 60;
        $hours   = (int) ($time / 3600000) % 24;
        $days    = (int) ($time / 86400000) % 7;
        $weeks   = (int) ($time / 604800000);
        return array($weeks, $days, $hours, $minutes, $seconds);
    }
    private function getTimeDDHHMMSSStringShortLossy($time){
        $timeDDHHMMSS = $this->getTimeWWDDHHMMSS($time);
        $useWeeks = $timeDDHHMMSS[0] != 0;
        $useDays = $timeDDHHMMSS[1] != 0;
        $useHours = $timeDDHHMMSS[2] != 0;
        $useMinuts = $timeDDHHMMSS[3] != 0;
        $useSeconds = $timeDDHHMMSS[4] != 0;
        if($useWeeks){
            $timeString = ($useWeeks ? ($timeDDHHMMSS[0] . " week" . ($timeDDHHMMSS[0] == 1 ? " " : "s ")): "");
        } else if($useDays){
            $timeString = ($useDays ? ($timeDDHHMMSS[1] . " day" . ($timeDDHHMMSS[1] == 1 ? " " : "s ")): "");
        } else if($useHours){
            $timeString = ($useHours ? ($timeDDHHMMSS[2] . " hour" . ($timeDDHHMMSS[2] == 1 ? " " : "s ")): "");
        } else if($useMinuts){
            $timeString = ($useMinuts ? ($timeDDHHMMSS[3] . " minute" . ($timeDDHHMMSS[3] == 1 ? " " : "s ")): "");
        } else if($useSeconds){
            $timeString = ($useSeconds ? ($timeDDHHMMSS[4] . " second" . ($timeDDHHMMSS[4] == 1 ? "" : "s")) : "");
        }
        if(empty($timeString)){
            return "0 seconds";
        } 
        return $timeString;
    }
    
    
    function secondsToTime($seconds) {
        $dtF = new \DateTime('@0');
        $dtT = new \DateTime("@$seconds");
        return $dtF->diff($dtT)->format('%a days, %h hours, %i minutes and %s seconds');
    }
}