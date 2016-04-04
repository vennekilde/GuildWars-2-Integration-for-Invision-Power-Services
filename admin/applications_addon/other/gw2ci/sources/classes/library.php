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
	 * Post Handler class
	 *
	 * @access	public
	 * @var		object
	 */
	public $editor;
	
	/**
	* Parser Class
	*
	* @access	public
	* @var		object
	*/
	public $parser;
	
	/**
	 * Ajax Routines
	 *
	 * @access	public
	 * @var		object
	 */
	public $classAjax;
	
	/**
	 * Total Shouts
	 *
	 * @access	public
	 * @var		integer
	 */
	public $shout_total = 0;
	
	/**
	 * Shouts cache
	 *
	 * @access	private
	 * @var		array
	 */
	private $shouts_cache;
	
	/**
	 * max shout length allowed in bytes
	 * 
	 * @access	public
	 * @var		integer
	 */
	public $shout_max_length;
	
	/**
	 * Inactivity cutoff (in minutes)
	 *
	 * @access	public
	 * @var		integer
	 */
	public $inactivity_cutoff;
	
	/**
	 * Shouts order (asc|desc)
	 *
	 * @access	public
	 * @var		string
	 */
	public $shouts_order;
	
	/**
	 * Global shoutbox on?
	 *
	 * @access	public
	 * @var		boolean
	 */
	public $global_on = false;
	
	/**
	 * Preferences array
	 *
	 * @access	public
	 * @var		array
	 */
	public $prefs = array( 'shoutbox_height'        => 0,
						   'shoutbox_gheight'       => 0,
						   'global_display'         => 1,
						   'enter_key_shout'        => 1,
						   'enable_quick_commands'  => 1,
						   'display_refresh_button' => 1
						  );
	
	/**
	 * javascript preferences
	 *
	 * @access	public
	 * @var		array
	 */
	public $prefs_js = array();
	
	/**
	 * Moderator ID (if any)
	 *
	 * @access	public
	 * @var		integer
	 */
	public $moderator = 0;
	
	/**
	 * Moderator permsissions for JS
	 *
	 * @access	public
	 * @var		string
	 */
	public $mod_perms_js = '';
	
	/**
	 * Moderator permissions (for PHP)
	 *
	 * @access	public
	 * @var		array
	 */
	public $mod_perms = array();
	
	/**
	 * Contains the ignored users of the member
	 *
	 * @access	public
	 * @var		array
	 * @since	1.1.0 RC1
	 */
	public $ignoredUsers = array();
	
	private $cached_members = array();
    
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
                                'where'    => 'api.u_last_success > NOW() - INTERVAL 3 WEEK ',
                                'limit'    => array( 0, $this->settings['shoutbox_shouts_limit'] )
        )		);
        $noCache = $this->DB->execute();
        
        //Check if empty
        if ( $this->DB->getTotalRows($noCache) )
        {
            //Loop through each API key
            while ( $row = $this->DB->fetch($noCache) )
            {
                $userId = $row["u_id"];
                $apiKey = $row["u_api_key"];
                $permittedEndpoints = explode(",", $row["u_api_key_permissions"]);
                $success = $this->resyncWithPermittedEndpoints($userId, $apiKey, $permittedEndpoints);
                if($success){
                    $this->updateLastSuccessfulSync($apiKey);
                }
            }
        }
    }
    
    function updateLastSuccessfulSync($apiKey){
        //@TODO make this actually work
		$this->DB->update( 'gw2_api_key', $values);
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
                case "character":
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
		$this->DB->insert( 'gw2_account', $values);
    }
    
    function persistGuildMemberships($userId, $guilds){
        foreach($guilds as $guildUUID){
            $this->DB->insert( 'gw2_guild_membership', array(
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
        //@TODO change strings to match the real ones
        switch($string){
            case "Normal":
                return 0;
            case "HeartOfThorns":
                return 1;
            case "FreeToPlay":
                return 2;
            case "Banned":
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
                //@TODO $characterId needs to actually be set
                $this->persistCharacterCraftingProfessions($characterId, $json["crafting"]);
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
		$this->DB->insert( 'gw2_characters', $values);
    }
    
    /**
     * 
     * @param integer $characterId
     * @param arrau $craftingProfessionValues
     */
    function persistCharacterCraftingProfessions($characterId, $craftingProfessionValues){
        foreach($craftingProfessionValues AS $craftingProfessionData){
            $values = array(
                "c_id" => $characterId,
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
		$this->DB->insert( 'gw2_character_crafting', $values);
    }
    
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getRaceIdFromString($string){
        return array_search($string, GW2CILibrary::RACE_NAMES);
    }
    
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getGenderIdFromString($string){
        return $string == "Male" ? 0 : 1;
    }
    /**
     * 
     * @param string $string
     * @return integer Description
     */
    function getProfessionIdFromString($string){
        return array_search($string, GW2CILibrary::PROFESSION_NAMES);
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
		$this->DB->insert( 'gw2_pvp_stats', $values);
    }
    
    /**
     * 
     * @param array $values
     */
    function persistProfessionPVPStats($values){
		$this->DB->insert( 'gw2_pvp_profession_stats', $values);
    }
    
    /**
     * 
     * @param string $apiKey
     */
    function resyncPVPGames($userId, $apiKey){
        try{
            $gw2Response = $this->gw2Com->requestPVPGames($apiKey);
            $json = $gw2Response->getJsonResponse();
            
            $values = array(
                'u_id'              => $userId,
                'game_uuid'         => $json["id"],
                'game_map_id'       => $json["map"],
                'game_started'      => $json["started"],
                'game_ended'        => $json["ended"],
                'game_result'       => $json["result"],
                'game_team'         => $json["team"],
                'game_profession'   => $json["profession"],
                'game_score_red'    => $json["score"]["red"],
                'game_score_blue'   => $json["score"]["blue"]
            );
            $this->persistPVPGame($values);
        } catch (Exception $e) {
            return false;
        }
        return true;
    }
    
    /**
     * 
     * @param array $values
     */
    function persistPVPGame($values){
		$this->DB->insert( 'gw2_pvp_games', $values);
    }
}