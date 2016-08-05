<?php
/**
 * @brief		Profile extension: gw2integration
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	GW2 Integration
 * @since		13 Jul 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gw2integration\extensions\core\Profile;

use IPS\gw2integration\_Application;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * @brief	Profile extension: gw2integration
 */
class _gw2integration
{
	/**
	 * Member
	 */
	protected $member;
    
    /**
     *
     * @var _Application 
     */
    protected $gw2integration;
	
    /**
     *
     * @var \IPS\gw2integration\_GW2Data 
     */
    protected $gw2Data;
	/**
	 * Constructor
	 *
	 * @param	\IPS\Member	$member	Member whose profile we are viewing
	 * @return	void
	 */
	public function __construct( \IPS\Member $member )
	{
		$this->member = $member;
	}
	
	/**
	 * Is there content to display?
	 *
	 * @return	bool
	 */
	public function showTab()
	{
		$this->gw2Data = new \IPS\gw2integration\GW2Data($this->member );
		return $this->gw2Data->account != null && $this->gw2Data->pvpStats != null;
	}
	
	/**
	 * Display
	 *
	 * @return	string
	 */
	public function render()
            
	{
		\IPS\Output::i()->cssFiles = array_merge( \IPS\Output::i()->cssFiles, \IPS\Theme::i()->css( 'style.css', 'gw2integration', 'front' ) );
        \IPS\Output::i()->jsFiles = array_merge( \IPS\Output::i()->jsFiles, \IPS\Output::i()->js( 'front_gw2.js', 'gw2integration' ) );
        $output = \IPS\Theme::i()->getTemplate( 'global', 'gw2integration', 'front')->profileTab( $this->gw2Data );
		
		return (string) $output;
	}
}