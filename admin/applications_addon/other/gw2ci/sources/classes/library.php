<?php

/**
 * Product Title:		IPB Shoutbox
 * Author:				Pete Treanor
 * Website URL:			http://www.ipbshoutbox.com
 * Copyright�:			IPB Works All rights Reserved 2011-2013
 */

if ( !defined( 'IN_IPB' ) )
{
	print "<h1>Incorrect access</h1>You cannot access this file directly. If you have recently upgraded, make sure you upgraded all the relevant files.";
	exit();
}

class GW2CILibrary
{
	/**
	 * Registry object
	 *
	 * @access	public
	 * @var		object
	 */
	public $registry;
	
	/**
	 * Registry object
	 *
	 * @access	private
	 * @var		boolean
	 */
	private $libraryLoaded = false;
	
    /**
     *
     * @var GW2Communicator
     */
	private $gw2Com;

    const PROFESSION_NAMES = array(
        "elementalist",
        "mesmer",
        "necromancer",
        "engineer",
        "thief",
        "ranger",
        "warrior",
        "guardian",
        "revenant"
    );
    
    const RACE_NAMES = array(
        "human",
        "norn",
        "asura",
        "charr",
        "sylvari",
    );
    
	/**
    * Constructor
    *
    * @access	public
    * @param	object		ipsRegistry reference
    * @return	void
    */
	public function __construct( $registry )
	{
		/* Make registry objects */
		$this->registry   =  $registry;
		$this->DB         =  $this->registry->DB();
		$this->settings   =& $this->registry->fetchSettings();
		$this->request    =& $this->registry->fetchRequest();
		$this->lang       =  $this->registry->getClass('class_localization');
		$this->member     =  $this->registry->member();
		$this->memberData =& $this->registry->member()->fetchMemberData();
		$this->cache      =  $this->registry->cache();
		$this->caches     =& $this->registry->cache()->fetchCaches();
        $this->gw2Com     =  $this->registry->getClass('gw2Communicator');
	}
	
	
    function resyncWithGW2API(){
        $this->DB->build( array('select'   => 'api.*',
                                'from'     => array( 'gw2_api_keys' => 'api' ),
                                'where'    => 'api.u_last_success > NOW() - INTERVAL 3 WEEK '
        )		);
        $queryResult = $this->DB->execute();
        
        //Check if empty
        if ( $this->DB->getTotalRows($queryResult) )
        {
            //Loop through each API key
            while ( $row = $this->DB->fetch($queryResult) )
            {
                $userId = $row["u_id"];
                $apiKey = $row["u_api_key"];
                $permittedEndpoints = explode(",", $row["u_api_key_permissions"]);
                $success = $this->resyncWithPermittedEndpoints($userId, $apiKey, $permittedEndpoints);
                if($success){
                    $this->updateLastSuccessfulSync($userId);
                }
            }
        }
    }
    
    function updateLastSuccessfulSync($userId){
		$this->DB->query("
                UPDATE gw2_api_key
                SET u_last_success = CURRENT_TIMESTAMP
                WHERE u_id = ".$userId."
            ");
    }
    
    /**
     * 
     * @param integer $userId
     * @param string $apiKey
     * @param array $permittedEndpoints
     */
    function resyncWithPermittedEndpoints($userId, $apiKey, $permittedEndpoints){
        $success = false;
        foreach($permittedEndpoints as $endpoint){
            switch($endpoint){
                case "account":
                    $result = resyncAccountEndpoint($userId, $apiKey);
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
                    $result = $this->resyncPVPStatsEndpoint($userId, $apiKey);
                    $result2 = $this->resyncPVPGames($userId, $apiKey);
                    if($result || $result2){
                        $success = true;
                    }
                    break;
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
            $gw2Response = $this->gw2Com->requestAccountInfo($apiKey);
            $json = $gw2Response->getJsonResponse();
            
            $values = array(
                'u_id'         => $userId,
                'a_uuid'       => $json["id"],
                'a_username'   => $json["name"],
                'a_world'      => $json["world"],
                'a_created'    => $json["created"],
                'a_access'     => getAccountAccessFromString($json["access"]),
                'a_commander'     => $json["commander"] == "1",
            );
            if(isset($json["fractal_level"])){
                $values["a_fractal_level"] = $json["fractal_level"];
                $values["a_daily_ap"] = $json["daily_ap"];
                $values["a_monthly_ap"] = $json["monthly_ap"];
            }
            $this->persistAccountData($userId, $values);
            
            $this->persistGuildMemberships($userId, explode(",", $json["guilds"]));
            
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistAccountData($values){
		$this->DB->replace( 'gw2_account', $values, array("u_id"));
    }
    
    /**
     * 
     * @param integer $userId
     * @param array $guilds
     */
    function persistGuildMemberships($userId, $guilds){
        //Remove no longer valid guild memberships
        $this->DB->query("
                DELETE FROM gw2_guild_membership
                WHERE u_id = " . $userId . "
                    AND NOT IN (" . implode(",","'" . $guilds . "'") . ")
                ");
        
        //Add guild memberships for the user
        foreach($guilds as $guildUUID){
            $this->DB->replace( 'gw2_guild_membership', array(
                "u_id" => $userId,
                "g_uuid" => $guildUUID
            ));
        }
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
            $gw2Response = $this->gw2Com->requestAccountInfo($apiKey);
            $json = $gw2Response->getJsonResponse();
            
            foreach($json AS $character){
                $values = array(
                    'u_id'         => $userId,
                    'c_name'       => $character["name"],
                    'c_race'       => $this->getRaceIdFromString($character["race"]),
                    'c_gender'     => $this->getGenderIdFromString($character["gender"]),
                    'c_profession' => $this->getProfessionIdFromString($character["profession"]),
                    'g_uuid'       => $character["guild"],
                    'c_age'        => $character["age"],
                    'c_created'    => $character["created"],
                    'c_deaths'     => $character["deaths"]
                );
                $this->persistCharacterData($values);
                $this->persistCharacterCraftingProfessions($character["name"], $json["crafting"]);
            }
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistCharacterData($values){
		$this->DB->replace( 'gw2_characters', $values, array("c_name"));
    }
    
    /**
     * 
     * @param string $characterName
     * @param arrau $craftingProfessionValues
     */
    function persistCharacterCraftingProfessions($characterName, $craftingProfessionValues){
        foreach($craftingProfessionValues AS $craftingProfessionData){
            $values = array(
                "c_name" => $characterName,
                "cr_dicipline" => $craftingProfessionData["dicipline"],
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
		$this->DB->replace( 'gw2_character_crafting', $values, array("c_id"));
    }
    
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getRaceIdFromString($string){
        return array_search(lcfirst($string), GW2CILibrary::RACE_NAMES);
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
        return array_search(lcfirst($string), GW2CILibrary::PROFESSION_NAMES);
    }
    
    /**
     * 
     * @param string $apiKey
     */
    function resyncPVPStatsEndpoint($userId, $apiKey){
        try{
            $gw2Response = $this->gw2Com->requestPVPStats($apiKey);
            $json = $gw2Response->getJsonResponse();
            
            $values = array(
                'u_id'              => $userId,
                'ps_rank'           => $json["pvp_rank"],
                'ps_rank_points'    => $json["pvp_rank_points"],
                'ps_rank_rollovers' => $json["pvp_rank_rollovers"],
            );
            if(isset($json["aggregate"])){
                parsePVPStatsData("ps", $values, $json["aggregate"]);
            }
            if(isset($json["ladders"])){
                $ladder = $json["ladders"];
                parsePVPStatsData("ps_ladder_none", $values, $ladder["none"]);
                parsePVPStatsData("ps_ladder_ranked", $values, $ladder["ranked"]);
                parsePVPStatsData("ps_ladder_soloarenarated", $values, $ladder["soloarenarated"]);
                parsePVPStatsData("ps_ladder_teamarenarated", $values, $ladder["teamarenarated"]);
                parsePVPStatsData("ps_ladder_unranked", $values, $ladder["unranked"]);
            }
            $this->persistPVPStats($values);
            $this->persistProfessionsPVPStats($userId, $json["professions"]);
        } catch (Exception $e) {
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
            $this->parsePVPStatsData("pps", $values, $professionData);
            $this->persistProfessionPVPStats($values);
        }
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
		$this->DB->update( 'gw2_pvp_stats', $values, array(""));
    }
    
    /**
     * 
     * @param array $values
     */
    function persistProfessionPVPStats($values){
		$this->DB->replace( 'gw2_pvp_profession_stats', $values, array("u_id", "pps_profession"));
    }
    
    /**
     * 
     * @param integer $userId
     */
    function getLatestGameUUID($userId){
        $this->DB->build( array('select'   => 'games.game_uuid',
                                'from'     => array( 'gw2_pvp_games' => 'games' ),
                                'where'    => 'games.u_id = '.$userId,
                                'order'    => 'games.game_ended DESC',
                                'limit'    => array(1)
        )		);
        $queryResult = $this->DB->execute();
        
        //Check if empty
        if ( $this->DB->getTotalRows($queryResult) )
        {
            //Loop through each API key
            while ( $row = $this->DB->fetch($queryResult) )
            {
                $latestGameUUID = $row["game_uuid"];
                return $latestGameUUID;
            }
        }
        return null;
    }
    
    /**
     * 
     * @param string $apiKey
     */
    function resyncPVPGames($userId, $apiKey){
        try{
            $gw2ResponseGameUUIDs = $this->gw2Com->requestPVPGameUUIDs($apiKey);
            $gameUUIDsJson = $gw2ResponseGameUUIDs->getJsonResponse();
            $latestGameUUID = $this->getLatestGameUUID($userId);
            
            //Determine which games are already retrieved
            $retriveGames = false;
            $retriveGameUUIDs = array();
            if($latestGameUUID != null){
                foreach($gameUUIDsJson as $gameUUID){
                    if($gameUUID == $latestGameUUID){
                        break;
                    } else {
                        $retriveGameUUIDs[] = $gameUUID;
                        $retriveGames = true;
                    }
                }
            } else{
                //No games persisted, so persist all
                $retriveGameUUIDs = $gameUUIDsJson;
                $retriveGames = true;
            }
            
            if($retriveGames){
                $gw2Response = $this->gw2Com->requestPVPGameByUUIDs($retriveGameUUIDs, $apiKey);
                $this->persistPVPGames($gw2Response->getJsonResponse());
            }
        } catch (Exception $e) {
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
                'game_started'      => $game["started"],
                'game_ended'        => $game["ended"],
                'game_result'       => $this->getPVPGameResultIdFromString($game["result"]),
                'game_team'         => $this->getPVPTeamIdFromString($game["team"]),
                'game_profession'   => $this->getProfessionIdFromString($game["profession"]),
                'game_score_red'    => $game["scores"]["red"],
                'game_score_blue'   => $game["scores"]["blue"]
            );
            $this->persistPVPGame($values);
        }
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
        }
        return -1;
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
    
    /**
     * 
     * @param array $values
     */
    function persistPVPGame($values){
		$this->DB->insert( 'gw2_pvp_games', $values);
    }
}