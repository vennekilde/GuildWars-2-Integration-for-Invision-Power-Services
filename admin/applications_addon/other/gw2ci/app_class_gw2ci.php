<?php

/**
 * Product Title:		IPB Shoutbox
 * Author:				Pete Treanor
 * Website URL:			http://www.ipbshoutbox.com
 * Copyright�:			IPB Works All rights Reserved 2011-2013
 */

class app_class_gw2ci
{
	/**
	 * Constructor
	 *
	 * @access	public
	 * @param	object	ipsRegistry
	 * @return	void
	 */
	public function __construct( ipsRegistry $registry )
	{
		require_once( IPSLib::getAppDir( 'gw2ci' ) . "/sources/classes/library.php" );
		require_once( IPSLib::getAppDir( 'gw2ci' ) . "/sources/classes/GW2APICommunicator.php" );
		$registry->setClass( 'gw2CILibrary', new GW2CILibrary( $registry ) );
		$registry->setClass( 'gw2Communicator', new GW2APICommunicator( $registry ) );
		
		if ( IN_ACP )
		{
			$registry->getClass('class_localization')->loadLanguageFile( array( 'admin_gw2ci' ), 'gw2ci' );
		}
		else
		{
			$registry->getClass('class_localization')->loadLanguageFile( array( 'public_gw2ci' ), 'gw2ci' );
		}
	}
}