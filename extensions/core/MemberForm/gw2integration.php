<?php
/**
 * @brief		Admin CP Member Form
 * @author		<a href='http://www.invisionpower.com'>Invision Power Services, Inc.</a>
 * @copyright	(c) 2001 - 2016 Invision Power Services, Inc.
 * @license		http://www.invisionpower.com/legal/standards/
 * @package		IPS Community Suite
 * @subpackage	GW2 Integration
 * @since		13 Jul 2016
 * @version		SVN_VERSION_NUMBER
 */

namespace IPS\gw2integration\extensions\core\MemberForm;

/* To prevent PHP errors (extending class does not exist) revealing path */
if ( !defined( '\IPS\SUITE_UNIQUE_KEY' ) )
{
	header( ( isset( $_SERVER['SERVER_PROTOCOL'] ) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.0' ) . ' 403 Forbidden' );
	exit;
}

/**
 * Admin CP Member Form
 */
class _gw2integration
{
	/**
	 * Action Buttons
	 *
	 * @param	\IPS\Member	$member	The Member
	 * @return	array
	 */
	public function actionButtons( $member )
	{
		return array(
			/*'id'	=> array(
				'title'		=> 'GuildWars 2 Integration',
				'icon'		=> 'cog',
				'link'		=> \IPS\Http\Url::internal( "app=gw2integration&module=&controller=&do=&id={$member->member_id}" ),
				'class'		=> ''
			)*/
		);
	}

	/**
	 * Process Form
	 *
	 * @param	\IPS\Helpers\Form		$form	The form
	 * @param	\IPS\Member				$member	Existing Member
	 * @return	void
	 */
	public function process( &$form, $member )
	{		
		\IPS\Application::applications();
		$gw2integration = \IPS\Application::load( 'gw2integration' );
		$apiKeyData = $gw2integration-> getAPIKey($member->member_id);
        
		$form->add( new \IPS\Helpers\Form\Text('gw2_api_key', $apiKeyData["u_api_key"] ) );
	}
	
	/**
	 * Save
	 *
	 * @param	array				$values	Values from form
	 * @param	\IPS\Member			$member	The member
	 * @return	void
	 */
	public function save( $values, &$member )
	{
        if(isset($values['gw2_api_key'])){
            try {
                \IPS\Application::applications();
                $gw2integration = \IPS\Application::load( 'gw2integration' );
                $gw2integration->setAPIKey($member->member_id, $values['gw2_api_key']);	
            } catch(\Exception $e){
                
            }
        }
	}
}