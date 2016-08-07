<?php
/**
 * @brief		GW2 Integration Application Class
 * @author		<a href='http://www.jeppes.info'>Jeppe Vennekilde</a>
 * @copyright	(c) 2016 Jeppe Vennekilde
 * @package		IPS Community Suite
 * @subpackage	GW2 Integration
 * @since		13 Jul 2016
 * @version		
 */
 
namespace IPS\gw2integration;

use IPS\gw2integration\GW2APICommunicator;
use IPS\gw2integration\Utils\DataConversionUtils;
use Exception;

include __DIR__ . '/gw2api.phar';
include __DIR__ . '/guzzle.phar';
/**
 * GW2 Integration Application Class
 */
class _Application extends \IPS\Application
{
    public $guildRefreshInternval = 1440; //1 day in seconds
    
    const CRAFTING_DICIPLINES = array(
        "armorsmith",
        "artificer",
        "chef",
        "huntsman",
        "jeweler",
        "leatherworker",
        "scribe",
        "tailor",
        "weaponsmith",
    );
    
    
    /**
     * @var array
     */
    const game_rating_types = array(
        "Ranked",
        "Unranked",
        "None",
        "Unknown"
    );
    
    public static $world_names = array(
        1001 => "Anvil Rock",
        1002 => "Borlis Pass",
        1003 => "Yak's Bend",
        1004 => "Henge of Denravi",
        1005 => "Maguuma",
        1006 => "Sorrow's Furnace",
        1007 => "Gate of Madness",
        1008 => "Jade Quarry",
        1009 => "Fort Aspenwood",
        1010 => "Ehmry Bay",
        1011 => "Stormbluff Isle",
        1012 => "Darkhaven",
        1013 => "Sanctum of Rall",
        1014 => "Crystal Desert",
        1015 => "Isle of Janthir",
        1016 => "Sea of Sorrows",
        1017 => "Tarnished Coast",
        1018 => "Northern Shiverpeaks",
        1019 => "Blackgate",
        1020 => "Ferguson's Crossing",
        1021 => "Dragonbrand",
        1022 => "Kaineng",
        1023 => "Devona's Rest",
        1024 => "Eredon Terrace",
        2001 => "Fissure of Woe",
        2002 => "Desolation",
        2003 => "Gandara",
        2004 => "Blacktide",
        2005 => "Ring of Fire",
        2006 => "Underworld",
        2007 => "Far Shiverpeaks",
        2008 => "Whiteside Ridge",
        2009 => "Ruins of Surmia",
        2010 => "Seafarer's Rest",
        2011 => "Vabbi",
        2012 => "Piken Square",
        2013 => "Aurora Glade",
        2014 => "Gunnar's Hold",
        2101 => "Jade Sea [FR]",
        2102 => "Fort Ranik [FR]",
        2103 => "Augury Rock [FR]",
        2104 => "Vizunah Square [FR]",
        2105 => "Arborstone [FR]",
        2201 => "Kodash [DE]",
        2202 => "Riverside [DE]",
        2203 => "Elona Reach [DE]",
        2204 => "Abaddon's Mouth [DE]",
        2205 => "Drakkar Lake [DE]",
        2206 => "Miller's Sound [DE]",
        2207 => "Dzagonur [DE]",
        2301 => "Baruch Bay [SP]",
    );
    
    /**
     *
     * @var \GW2Treasures\GW2Api\GW2Api 
     */
    public $gw2API;
    
    public function __construct() {
        $this->gw2API = new \GW2Treasures\GW2Api\GW2Api();
    }
    
    /**
     * [Node] Get Node Icon
     *
     * @return	string
     */
    protected function get__icon()
    {
        return 'legal';
    }
	
    /**
     * 
     * @param Integer $userId
     * @param String $apiKey
     */
    public function setAPIKeyForUser($userId, $apiKey){
        try{
            $gw2Response = GW2APICommunicator::requestAPIKeyInfo($apiKey);
        } catch (Exception $e) {
            /*//\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;*/
            throw $e;
        }
        $json = $gw2Response->getJsonResponse();

        //\IPS\Session::i()->log(null,  'Account response: [' . json_encode($json) .']' );
        $values = array(
            'u_id'                  => $userId,
            'u_api_key'             => $apiKey,
            'u_api_key_name'        => $json["name"],
            'u_api_key_permissions' => implode(",",$json["permissions"])
        );
        
        \IPS\Db::i()->replace( 'gw2integration_api_keys', $values, array("u_id"));
        
        $success = $this->resyncWithPermittedEndpoints($userId, $apiKey, $json["permissions"]);
        return $success;
    }
    
    public function getAPIKeyForUser($userId){
        try{
            $result = \IPS\Db::i()->select('*', 'gw2integration_api_keys', array("u_id = ?",$userId), null, 1)->first();
        } catch (\UnderflowException $e){
            $result = null;
        }
        return $result;
    }
    
    public function resyncWithGW2API($limit = 100){
        $this->resyncPVPSeasons();
        
        $apiKeyIterator = \IPS\Db::i()->select('*', 'gw2integration_api_keys', 'u_last_success > NOW() - INTERVAL 3 WEEK', null, $limit);
        
        //Loop through each API key
        foreach($apiKeyIterator as $row)
        {
            //\IPS\Session::i()->log(null, 'api_key_data: [' . implode(",", $row) .']' );
             
            $userId = $row["u_id"];
            $apiKey = $row["u_api_key"];
            $permittedEndpoints = explode(",", $row["u_api_key_permissions"]);
            $success = $this->resyncWithPermittedEndpoints($userId, $apiKey, $permittedEndpoints);
            if($success){
                $this->updateLastSuccessfulSync($userId);
            }
        }
    }
    
    function updateLastSuccessfulSync($userId){
        $dbPrefix = \IPS\Db::i()->prefix;
        \IPS\Db::i()->query('
            UPDATE '.$dbPrefix.'gw2integration_api_keys
            SET u_last_success = CURRENT_TIMESTAMP
            WHERE u_id = '.$userId.'
        ');
    }
    
    /*
     * 
     * @param integer $userId
     * @param string $apiKey
     * @param array $permittedEndpoints
     */
    function resyncWithPermittedEndpoints($userId, $apiKey, $permittedEndpoints){
        $success = false;
        foreach($permittedEndpoints as $endpoint){
            try{
                switch($endpoint){
                    case "account":
                        $result = $this->resyncAccountEndpoint($userId, $apiKey);
                        if($result){
                            $success = true;
                        }
                        break;
                    case "characters":
                        $result = $this->resyncCharactersEndpoint($userId, $apiKey);
                        if($result){
                            $success = true;
                        }
                        break;
                    case "pvp":
                        //Should probably do something about this, but for now, 
                        //any success returns success, doesn't matter if others failed
                        $result = $this->resyncPVPStatsEndpoint($userId, $apiKey);
                        $result = $this->resyncPVPGames($userId, $apiKey) ? true : $result;
                        $result = $this->resyncPVPSeasonStandings($userId, $apiKey) ? true : $result;
                        if($result){
                            $success = true;
                        }
                        break;
                }
            } catch (Exception $e) {
                //\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
                return false;
            }
        }
        return $success;
    }
    
    /**
     * 
     * @param int $userId
     * @param String $apiKey
     */
    function resyncAccountEndpoint($userId, $apiKey) {
        try{
            $gw2Response = GW2APICommunicator::requestAccountInfo($apiKey);
            $json = $gw2Response->getJsonResponse();
            
            //\IPS\Session::i()->log(null,  'Account response: [' . json_encode($json) .']' );
            $values = array(
                'u_id'         => $userId,
                'a_uuid'       => $json["id"],
                'a_username'   => $json["name"],
                'a_world'      => $json["world"],
                'a_created'    => \IPS\gw2integration\Utils\DataConversionUtils::getTimestampFromGW2Time($json["created"]),
                'a_access'     => $this->getAccountAccessIdFromString($json["access"]),
                'a_commander'     => $json["commander"] == "1",
            );
            if(isset($json["fractal_level"])){
                $values["a_fractal_level"] = $json["fractal_level"];
                $values["a_daily_ap"] = $json["daily_ap"];
                $values["a_monthly_ap"] = $json["monthly_ap"];
                $values["a_wvw_rank"] = $json["wvw_rank"];
            }
            $this->persistAccountData($values);
            
            $this->persistGuildMemberships($userId, $json["guilds"]);
            
        } catch (Exception $e) {
            //\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistAccountData($values){
        //\IPS\Session::i()->log(null,  'persistAccountData: [' . json_encode($values) .']' );
        \IPS\Db::i()->replace( 'gw2integration_account', $values, array("u_id"));
    }
    
    function getAccountData($userId){
        try{
            $result = \IPS\Db::i()->select('*', 'gw2integration_account', array("u_id = ?",$userId), 1)->first();
        } catch (\UnderflowException $e){
            $result = null;
        }
        return $result;
    }
    
    function getOverviewData($userId){
        $dbPrefix = \IPS\Db::i()->prefix;
        return \IPS\Db::i()->query(
                'SELECT * FROM '.$dbPrefix.'gw2integration_pvp_seasons AS s
                LEFT JOIN '.$dbPrefix.'gw2integration_pvp_season_standing ss ON ss.u_id = '.  intval($userId) . ' AND ss.season_uuid = s.season_uuid
                LEFT JOIN '.$dbPrefix.'gw2integration_pvp_season_divisions sd ON sd.division_id = ss.season_current_division AND sd.season_uuid = ss.season_uuid
                LEFT JOIN '.$dbPrefix.'gw2integration_account a ON a.u_id = '.  intval($userId) . '
                LEFT JOIN '.$dbPrefix.'gw2integration_pvp_stats ps ON ps.u_id = '.  intval($userId) . '
                ORDER BY s.season_end DESC LIMIT 1'
                /*Ø'SELECT a.*, ps.ps_rank, ss.season_current_division FROM gw2integration_account AS a '
                . 'LEFT JOIN gw2integration_pvp_stats ps ON a.u_id = ps.u_id '
                . 'LEFT JOIN gw2integration_pvp_season_standing ss ON a.u_id = ss.u_id '
                . 'JOIN gw2integration_pvp_seasons s ON ss.season_uuid = s.season_uuid '
                . 'WHERE a.u_id = '.  intval($userId) . " ORDER BY s.season_end DESC LIMIT 1"*/)->fetch_array();
    }
    
    /**
     * 
     * @param integer $userId
     * @param array $guilds
     */
    function persistGuildMemberships($userId, $guilds){
        //\IPS\Session::i()->log(null,  'persistGuildMemberships: userId: ' . $userId . ' [' . implode(",",$guilds) .']' );
        //Remove no longer valid guild memberships
        $dbPrefix = \IPS\Db::i()->prefix;
        \IPS\Db::i()->query('
                DELETE FROM '.$dbPrefix.'gw2integration_guild_membership
                WHERE u_id = ' . $userId . '
                    AND g_uuid NOT IN ("' . implode("','",$guilds) . '")
                ');
        
        //Add guild memberships for the user
        foreach($guilds as $guildUUID){
            \IPS\Db::i()->replace( 'gw2integration_guild_membership', array(
                "u_id" => $userId,
                "g_uuid" => $guildUUID
            ), array("u_id", "g_uuid"));
        }
        
        //Retrieve guild details
        $this->fetchGuildsData($guilds);
    }
    
    function getGuildMembership($userId){
        $dbPrefix = \IPS\Db::i()->prefix;
        return \IPS\Db::i()->query(
                'SELECT * '
                . 'FROM '.$dbPrefix.'gw2integration_guild_membership AS membership '
                . 'LEFT JOIN '.$dbPrefix.'gw2integration_guilds AS guilds on membership.g_uuid = guilds.g_uuid ' 
                . 'WHERE u_id = ' . $userId . ' ORDER BY g_tag'
            );
    }
    
    function fetchGuildsData($guildIds, $checkLastSynched = true){
        if($checkLastSynched){
            $params = array('g_last_synched > NOW() - INTERVAL ? SECOND AND g_uuid IN("' . implode('","', $guildIds) . '")', $this->guildRefreshInternval);
        } else {
            $params = 'g_uuid IN("' . implode('","', $guildIds) . '")';
        }
        $guildsAlreadySynched = iterator_to_array(\IPS\Db::i()->select('g_uuid', 'gw2integration_guilds', $params));
        
        foreach($guildIds AS $guildId){
            if(!in_array($guildId, $guildsAlreadySynched)){
                $guildDetails = _GW2APICommunicator::requestGuildDetails($guildId);
                $json = $guildDetails->getJsonResponse();
                $this->persistGuildDetails($json);
            }
        }
    }
    
    function persistGuildDetails($guildDetails){
        \IPS\Db::i()->replace( 'gw2integration_guilds', array(
            "g_uuid" => $guildDetails["guild_id"],
            "g_name" => $guildDetails["guild_name"],
            "g_tag" => $guildDetails["tag"]
        ), array("g_uuid"));
    }
    
    
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getAccountAccessIdFromString($string){
        switch($string){
            case "GuildWars2":
                return 0;
            case "HeartOfThorns":
                return 1;
            case "PlayForFree":
                return 2;
            case "None":
                return 3;
        }
        return -1;
    }
    
    /**
     * 
     * @param int $userId
     * @param String $apiKey
     */
    function resyncCharactersEndpoint($userId, $apiKey) {
        try{
            $gw2Response = GW2APICommunicator::requestCharactersData($apiKey);
            $json = $gw2Response->getJsonResponse();
            
            foreach($json AS $character){
                //\IPS\Session::i()->log(null,  'character : ' . json_encode($character));
                $values = array(
                    'u_id'         => $userId,
                    'c_name'       => $character["name"],
                    'c_race'       => $this->getRaceIdFromString($character["race"]),
                    'c_gender'     => $this->getGenderIdFromString($character["gender"]),
                    'c_profession' => $this->getProfessionIdFromString($character["profession"]),
                    'c_level'      => $character["level"],
                    'g_uuid'       => $character["guild"],
                    'c_age'        => $character["age"],
                    'c_created'    => \IPS\gw2integration\Utils\DataConversionUtils::getTimestampFromGW2Time($character["created"]),
                    'c_deaths'     => $character["deaths"]
                );
                if(isset($character["title"])){
                    $values['c_title'] = $character["title"];
                }
                $this->persistCharacterData($values);
                $this->persistCharacterCraftingProfessions($character["name"], $character["crafting"]);
            }
        } catch (Exception $e) {
            //\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistCharacterData($values){
		\IPS\Db::i()->replace( 'gw2integration_characters', $values, array("c_name"));
    }
    
    function getCharactersData($userId){
        $dbPrefix = \IPS\Db::i()->prefix;
        return \IPS\Db::i()->query(
                'SELECT * '
                . 'FROM '.$dbPrefix.'gw2integration_characters AS c '
                . 'LEFT JOIN '.$dbPrefix.'gw2integration_guilds AS g on g.g_uuid = c.g_uuid '
                . 'WHERE c.u_id = '.intval($userId).' ORDER BY c_age DESC'
            );
    }
    
    function getCharacterData($characterName){
        try{
            $dbPrefix = \IPS\Db::i()->prefix;
            return \IPS\Db::i()->query(
                    'SELECT * '
                    . 'FROM '.$dbPrefix.'gw2integration_characters AS c '
                    . 'LEFT JOIN '.$dbPrefix.'gw2integration_guilds AS g on g.g_uuid = c.g_uuid '
                    . 'WHERE c.c_name = '.mysql_real_escape_string($characterName).' LIMIT 1'
                );
        } catch (\UnderflowException $e){
            $result = null;
        }
        return $result;
    }
    /**
     * 
     * @param string $characterName
     * @param arrau $craftingProfessionValues
     */
    function persistCharacterCraftingProfessions($characterName, $craftingProfessionValues){
        foreach($craftingProfessionValues AS $craftingProfessionData){
            //\IPS\Session::i()->log(null,  "craftingProfessionValues: " . json_encode($craftingProfessionData));
            $values = array(
                "c_name" => $characterName,
                "cr_discipline" => $this->getCraftingProfIdFromString($craftingProfessionData["discipline"]),
                "cr_rating" => $craftingProfessionData["rating"],
                "cr_active" => $craftingProfessionData["active"],
            );
            $this->persistCharacterCraftingProfession($values);
        }
    }
    
    /**
     * 
     * @param array $values
     */
    function persistCharacterCraftingProfession($values){
        \IPS\Db::i()->replace( 'gw2integration_character_crafting', $values, array("c_name"));
    }
    
    function getCharacterCraftingProfessions($characterName){
        return \IPS\Db::i()->select('*', 'gw2integration_character_crafting', array("c_name = ?",$characterName));
    }
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getRaceIdFromString($string){
        return array_search(lcfirst($string), DataConversionUtils::$RACE_NAMES);
    }
    
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getCraftingProfIdFromString($string){
        return array_search(lcfirst($string), self::CRAFTING_DICIPLINES);
    }
    
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getGenderIdFromString($string){
        return strcasecmp($string, "male") == 0 ? 0 : 1;
    }
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getProfessionIdFromString($string){
        return array_search(lcfirst($string), DataConversionUtils::$PROFESSION_NAMES);
    }
    
    /**
     * 
     * @param string $apiKey
     */
    function resyncPVPStatsEndpoint($userId, $apiKey){
        try{
            $gw2Response = GW2APICommunicator::requestPVPStats($apiKey);
            $json = $gw2Response->getJsonResponse();
            
            $values = array(
                'u_id'              => $userId,
                'ps_rank'           => $json["pvp_rank"],
                'ps_rank_points'    => $json["pvp_rank_points"],
                'ps_rank_rollovers' => $json["pvp_rank_rollovers"],
            );
            if(isset($json["aggregate"])){
                $values = $this->parsePVPStatsData("ps_aggregate", $values, $json["aggregate"]);
            }
            if(isset($json["ladders"])){
                
                $ladder = $json["ladders"];
                if (isset($ladder["none"])) {
                    $values = $this->parsePVPStatsData("ps_ladder_none", $values, $ladder["none"]);
                }
                if (isset($ladder["ranked"])) {
                    $values = $this->parsePVPStatsData("ps_ladder_ranked", $values, $ladder["ranked"]);
                }
                if (isset($ladder["soloarenarated"])) {
                    $values = $this->parsePVPStatsData("ps_ladder_soloarenarated", $values, $ladder["soloarenarated"]);
                }
                if (isset($ladder["teamarenarated"])) {
                    $values = $this->parsePVPStatsData("ps_ladder_teamarenarated", $values, $ladder["teamarenarated"]);
                }
                if (isset($ladder["ranked"])) {
                    $values = $this->parsePVPStatsData("ps_ladder_unranked", $values, $ladder["unranked"]);
                }
            }
            $this->persistPVPStats($values);
            $this->persistProfessionsPVPStats($userId, $json["professions"]);
        } catch (Exception $e) {
            //\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param integer $userId
     * @param array $professionsData
     */
    function persistProfessionsPVPStats($userId, $professionsData){
        foreach($professionsData as $profession => $professionData){
            $values = array(
                "u_id" => $userId,
                "pps_profession" => $this->getProfessionIdFromString($profession)
            );
            $values = $this->parsePVPStatsData("pps", $values, $professionData);
            $this->persistProfessionPVPStats($values);
        }
    }
    
    function getPVPProfessionsStats($userId){
        return \IPS\Db::i()->select('*', 'gw2integration_pvp_profession_stats', array("u_id = ?",$userId), "pps_wins DESC");
    }
    
    /**
     * 
     * @param string $prefix
     * @param array $values
     * @param array $ladderData
     * @return array
     */
    function parsePVPStatsData($prefix, $values, $ladderData){
        if(!isset($ladderData)){
            return null;
        }
        $values[$prefix."_wins"] = $ladderData["wins"];
        $values[$prefix."_losses"] = $ladderData["losses"];
        $values[$prefix."_desertions"] = $ladderData["desertions"];
        $values[$prefix."_byes"] = $ladderData["byes"];
        $values[$prefix."_forfeits"] = $ladderData["forfeits"];
        return $values;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistPVPStats($values){
        //\IPS\Session::i()->log(null,  "persistPVPStats: " . json_encode($values));
        \IPS\Db::i()->replace( 'gw2integration_pvp_stats', $values, array("u_id"));
    }
    
    function getPVPStats($userId){
        try{
            $result = \IPS\Db::i()->select('*', 'gw2integration_pvp_stats', array("u_id = ?",$userId), 1)->first();
        } catch (\UnderflowException $e){
            $result = null;
        }
        return $result;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistProfessionPVPStats($values){
        //\IPS\Session::i()->log(null,  "persistProfessionPVPStats: " . json_encode($values));
        \IPS\Db::i()->replace( 'gw2integration_pvp_profession_stats', $values, array("u_id", "pps_profession"));
    }
    
    /**
     * 
     * @param integer $userId
     */
    function getMatchesAlreadyPersisted($userId, $gameUUIDs){
        $alreadyPersistedMatches = iterator_to_array(\IPS\Db::i()->select( 'game_uuid', 'gw2integration_pvp_games', array('u_id = ? AND game_uuid IN("' . implode('","', $gameUUIDs) . '")', $userId)));
        return $alreadyPersistedMatches;
    }
    
    /**
     * 
     * @param string $apiKey
     */
    function resyncPVPGames($userId, $apiKey){
        try{
            $gw2ResponseGameUUIDs = GW2APICommunicator::requestPVPGameUUIDs($apiKey);
            $gameUUIDsJson = $gw2ResponseGameUUIDs->getJsonResponse();
            $gamesAlreadyPersisted =  /*array();//*/$this->getMatchesAlreadyPersisted($userId, $gameUUIDsJson);
            //Determine which games are already retrieved
            $retriveGames = false;
            $retriveGameUUIDs = array();
            foreach($gameUUIDsJson as $gameUUID){
                if(in_array($gameUUID, $gamesAlreadyPersisted)){
                    //no reason to persist again
                    continue;
                } else {
                    $retriveGameUUIDs[] = $gameUUID;
                    $retriveGames = true;
                }
            }
            
            if($retriveGames){
                $gw2Response = GW2APICommunicator::requestPVPGameByUUIDs($retriveGameUUIDs, $apiKey);
                $this->persistPVPGames($userId, $gw2Response->getJsonResponse());
            }
        } catch (Exception $e) {
            //\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param integer $userId
     * @param array $pvpGames
     */
    function persistPVPGames($userId, $pvpGames){
        foreach($pvpGames as $game){
            $values = array(
                'u_id'              => $userId,
                'game_uuid'         => $game["id"],
                'game_map_id'       => $game["map_id"],
                'game_started'      => \IPS\gw2integration\Utils\DataConversionUtils::getTimestampFromGW2Time($game["started"]),
                'game_ended'        => \IPS\gw2integration\Utils\DataConversionUtils::getTimestampFromGW2Time($game["ended"]),
                'game_result'       => $this->getPVPGameResultIdFromString($game["result"]),
                'game_team'         => $this->getPVPTeamIdFromString($game["team"]),
                'game_rating_type'  => static::getPvPGameRatingTypeFromString($game["rating_type"]),
                'game_season_uuid'       => isset($game["season"]) ? $game["season"] : null,
                'game_profession'   => $this->getProfessionIdFromString($game["profession"]),
                'game_score_red'    => $game["scores"]["red"],
                'game_score_blue'   => $game["scores"]["blue"]
            );
            $this->persistPVPGame($values);
        }
    }
    
    function getPVPGames($userId, $latest){
        $dbPrefix = \IPS\Db::i()->prefix;
        return \IPS\Db::i()->query(
                'SELECT game.*, s.season_name, UNIX_TIMESTAMP(game_ended) - UNIX_TIMESTAMP(game_started) as game_duration, UNIX_TIMESTAMP(UTC_TIMESTAMP) - UNIX_TIMESTAMP(game_started) as game_time_since_ended '
                . 'FROM '.$dbPrefix.'gw2integration_pvp_games AS game '
                . 'LEFT JOIN '.$dbPrefix.'gw2integration_pvp_seasons AS s on game.game_season_uuid = s.season_uuid '
                . 'WHERE game.u_id = '.intval($userId).' ORDER BY game.game_ended DESC LIMIT '.intval($latest)
            );
    }
    
    
    /**
     * 
     * @param string $result
     * @return integer Description
     */
    function getPVPGameResultIdFromString($result){
        switch($result){
            case "Defeat":
                return 0;
            case "Victory":
                return 1;
            Case "Forfeit":
                return 2;
            case "Tie":
                return 3;
            case "Desertion":
                return 5;
            case "Bye":
            case "Byes":
                return 6;
        }
        return 4;
    }
    
    
    /**
     * 
     * @param string $result
     * @return integer Description
     */
    public static function getPVPGameResultStringFromId($result){
        switch($result){
            case 0:
                return "Defeat";
            case 1:
                return "Victory";
            case 2:
                return "Forfeit";
            case 3:
                return "Tie";
            case 5:
                return "Desertion";
            case 6:
                return "Bye";
        }
        return "Unknown";
    }
    /**
     * 
     * @param string $team
     * @return integer Description
     */
    function getPVPTeamIdFromString($team){
        switch($team){
            case "Blue":
                return 0;
            case "Red":
                return 1;
        }
        return -1;
    }
    
    public static function getPvPGameRatingTypeFromString($ratingType){
        return array_search($ratingType, static::game_rating_types);
    }
    
    public static function getPvPGameRatingTypeStringFromId($ratingTypeId){
        if($ratingTypeId == 2){
            $ratingTypeString = "Custom Arena";
        } else {
            $ratingTypes = static::game_rating_types;
            $ratingTypeString = $ratingTypes[$ratingTypeId];
        }
        return $ratingTypeString;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistPVPGame($values){
        \IPS\Db::i()->replace( 'gw2integration_pvp_games', $values, array("game_uuid", "u_id"));
    }
    
    /**
     * 
     * @param string $apiKey
     */
    function resyncPVPSeasonStandings($userId, $apiKey){
        try{
            $gw2ResponseEvent = GW2APICommunicator::requestPVPSeasonStandings($apiKey);
            $json = $gw2ResponseEvent->getJsonResponse();
            
            foreach($json AS $seasonStanding){
                $values = array(
                    "u_id"                          => $userId,
                    "season_uuid"                  => $seasonStanding["season_id"],
                    "season_current_total_points"  => $seasonStanding["current"]["total_points"],
                    "season_current_division"      => $seasonStanding["current"]["division"],
                    "season_current_tier"          => $seasonStanding["current"]["tier"],
                    "season_current_points"        => $seasonStanding["current"]["points"],
                    "season_current_repeats"       => $seasonStanding["current"]["repeats"],
                    "season_best_total_points"     => $seasonStanding["best"]["total_points"],
                    "season_best_division"         => $seasonStanding["best"]["division"],
                    "season_best_tier"             => $seasonStanding["best"]["tier"],
                    "season_best_points"           => $seasonStanding["best"]["points"],
                    "season_best_repeats"          => $seasonStanding["best"]["repeats"]
                );
                $this->persistPVPSeasonStanding($values);
            }
        } catch (Exception $e) {
            //\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistPVPSeasonStanding($values){
        \IPS\Db::i()->replace( 'gw2integration_pvp_season_standing', $values, array("u_id", "season_uuid"));
    }
    
    function getPVPSeasonStandingGames($userId){
        return \IPS\Db::i()->select('*', 'gw2integration_pvp_season_standing', array("u_id = ?",$userId));
    }
    
    function getPVPSeasonStandingWithSeasonData($userId){
        $dbPrefix = \IPS\Db::i()->prefix;
        return \IPS\Db::i()->query(
            'SELECT * FROM '.$dbPrefix.'gw2integration_pvp_season_standing AS ss
                LEFT JOIN '.$dbPrefix.'gw2integration_pvp_seasons s ON ss.season_uuid = s.season_uuid
                LEFT JOIN '.$dbPrefix.'gw2integration_pvp_season_divisions AS sd ON sd.season_uuid = s.season_uuid AND ss.season_current_division = sd.division_id 
                WHERE ss.u_id = ' .  intval($userId) . ' 
                ORDER BY s.season_end DESC');
    }
    
    
    /**
     * 
     * @param string $apiKey
     */
    public function resyncPVPSeasons(){
        try{
            $seasons = $this->gw2API->pvp()->seasons()->all();
            foreach($seasons AS $season){
                
                $values = array(
                    'season_uuid'       => $season->id,
                    'season_name'       => $season->name,
                    'season_start'      => \IPS\gw2integration\Utils\DataConversionUtils::getTimestampFromGW2Time($season->start),
                    'season_end'        => \IPS\gw2integration\Utils\DataConversionUtils::getTimestampFromGW2Time($season->end),
                    'season_active'     => $season->active
                );
                $this->persistPVPSeason($values);
                
                $divisionId = 0;
                foreach($season->divisions as $division){
                    $divisionValues = array(
                        'season_uuid'           => $season->id,
                        'division_id'           => $divisionId,
                        'division_name'         => $division->name,
                        'division_large_icon'   => $division->large_icon,
                        'division_small_icon'   => $division->small_icon,
                        'division_pip_icon'     => $division->pip_icon,
                        'division_tiers'        => count($division->tiers),
                        'division_pips_per_tier'=> $division->tiers[0]->points,
                    );
                    $this->persistPVPSeasonDivision($divisionValues);
                    $divisionId++;
                }
            }
            
        } catch (Exception $e) {
            throw $e;
            //\IPS\Session::i()->log(null,  get_class($e) . ": " . $e->getMessage() . "\n" . $e->getTraceAsString());
            return false;
        }
        return true;
    }
    
    
    /**
     * 
     * @param array $values
     */
    public function persistPVPSeason($values){
        \IPS\Db::i()->replace( 'gw2integration_pvp_seasons', $values, array("season_uuid"));
    }
    
    
    /**
     * 
     * @param array $values
     */
    public function persistPVPSeasonDivision($values){
        \IPS\Db::i()->replace( 'gw2integration_pvp_season_divisions', $values, array("season_uuid", "division_id"));
    }
}