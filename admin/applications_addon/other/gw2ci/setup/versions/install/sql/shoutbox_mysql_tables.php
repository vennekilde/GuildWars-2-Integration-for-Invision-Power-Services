<?php

/**
 * Product Title:		Shoutbox
 * Author:				IPB Works
 * Website URL:			http://www.ipbworks.com/forums
 * Copyright�:			IPB Works All rights Reserved 2011-2012
 */

// Tables
$TABLE[] = "CREATE TABLE gw2_api_keys (
	u_id int(11) NOT NULL auto_increment,
    u_api_key CHAR(36) NOT NULL UNIQUE,
    u_api_key_name varchar(255) NOT NULL,
    u_api_key_permissions varchar(1024) NOT NULL,
    u_last_success TIMESTAMP NOT NULL DEFAULT current_timestamp,
	PRIMARY KEY (u_id)
)";

$TABLE[] = "CREATE TABLE gw2_account (
	u_id int(11) NOT NULL,
    a_uuid CHAR(64) NOT NULL UNIQUE,
    a_username varchar(255) NOT NULL UNIQUE,
    a_world varchar(1024) NOT NULL,
    a_created TIMESTAMP NOT NULL,
    a_access TINYINT NOT NULL,
    a_commander TINYINT(1) NOT NULL,
    a_fractal_level MEDIUMINT NOT NULL DEFAULT 0
    a_daily_ap int(11) NOT NULL DEFAULT 0
    a_monthly_ap int(11) NOT NULL DEFAULT 0
	PRIMARY KEY (u_id),
    FOREIGN KEY (u_id) REFERENCES gw2_api_keys(u_id)
)";

$TABLE[] = "CREATE TABLE gw2_guild(
    g_uuid CHAR(64) NOT NULL,
    g_name CHAR(64) NOT NULL UNIQUE,
    g_tag varchar(4) NOT NULL,
	PRIMARY KEY (g_uuid)
)";

$TABLE[] = "CREATE TABLE gw2_guild_membership(
	u_id int(11) NOT NULL,
    g_uuid CHAR(64) NOT NULL UNIQUE,
	PRIMARY KEY (u_id, g_uuid),
    FOREIGN KEY (u_id) REFERENCES gw2_api_keys(u_id),
    FOREIGN KEY (g_uuid) REFERENCES gw2_guild(g_uuid)
)";

$TABLE[] = "CREATE TABLE gw2_characters(
    c_name varchar(128) NOT NULL,
    u_id int(11) NOT NULL,
    c_race TINYINT NOT NULL,
    c_gender TINYINT(1) NOT NULL COMMENT '0 = Male, 1 = Female',
    c_profession TINYINT NOT NULL,
    c_level TINYINT NOT NULL,
    g_uuid CHAR(64) NOT NULL,
    c_age int(11) NOT NULL,
    c_created TIMESTAMP NOT NULL,
    c_deaths TIMESTAMP NOT NULL,
	PRIMARY KEY (c_name),
    KEY (u_id),
    FOREIGN KEY (u_id) REFERENCES gw2_api_keys(u_id),
    FOREIGN KEY (g_uuid) REFERENCES gw2_guild(g_uuid)
)";

$TABLE[] = "CREATE TABLE gw2_character_crafting(
    c_name varchar(128) NOT NULL,
    cr_dicipline TINYINT NOT NULL,
    cr_rating varchar(128) NOT NULL,
    cr_active TINYINT(1) NOT NULL,
	PRIMARY KEY (c_name),
    FOREIGN KEY (c_name) REFERENCES gw2_characters(c_name)
)"; 

$TABLE[] = "CREATE TABLE gw2_pvp_stats(
    u_id int(11) NOT NULL,
    ps_rank int(11) NOT NULL,
    ps_rank_points int(11) NOT NULL,
    ps_rank_rollovers int(11) NOT NULL,
    
    ps_wins int(11) NOT NULL,
    ps_losses int(11) NOT NULL,
    ps_desertions int(11) NOT NULL,
    ps_byes int(11) NOT NULL,
    ps_forfeits int(11) NOT NULL,
    
    ps_ladder_none_wins int(11) NOT NULL,
    ps_ladder_none_losses int(11) NOT NULL,
    ps_ladder_none_desertions int(11) NOT NULL,
    ps_ladder_none_byes int(11) NOT NULL,
    ps_ladder_none_forfeits int(11) NOT NULL,
	
    ps_ladder_ranked_wins int(11) NOT NULL,
    ps_ladder_ranked_losses int(11) NOT NULL,
    ps_ladder_ranked_desertions int(11) NOT NULL,
    ps_ladder_ranked_byes int(11) NOT NULL,
    ps_ladder_ranked_forfeits int(11) NOT NULL,
	
    ps_ladder_soloarenarated_wins int(11) NOT NULL,
    ps_ladder_soloarenarated_losses int(11) NOT NULL,
    ps_ladder_soloarenarated_desertions int(11) NOT NULL,
    ps_ladder_soloarenarated_byes int(11) NOT NULL,
    ps_ladder_soloarenarated_forfeits int(11) NOT NULL,
    
    ps_ladder_teamarenarated_wins int(11) NOT NULL,
    ps_ladder_teamarenarated_losses int(11) NOT NULL,
    ps_ladder_teamarenarated_desertions int(11) NOT NULL,
    ps_ladder_teamarenarated_byes int(11) NOT NULL,
    ps_ladder_teamarenarated_forfeits int(11) NOT NULL,
    
    ps_ladder_unranked_wins int(11) NOT NULL,
    ps_ladder_unranked_losses int(11) NOT NULL,
    ps_ladder_unranked_desertions int(11) NOT NULL,
    ps_ladder_unranked_byes int(11) NOT NULL,
    ps_ladder_unranked_forfeits int(11) NOT NULL,
    
	PRIMARY KEY (u_id),
    FOREIGN KEY (u_id) REFERENCES gw2_api_keys(u_id)
)"; 

$TABLE[] = "CREATE TABLE gw2_pvp_profession_stats(
    u_id int(11) NOT NULL,
    pps_profession TINYINT NOT NULL,
    pps_wins int(11) NOT NULL,
    pps_losses int(11) NOT NULL,
    pps_desertions int(11) NOT NULL,
    pps_byes int(11) NOT NULL,
    pps_forfeits int(11) NOT NULL,
	PRIMARY KEY (u_id, pps_profession),
    FOREIGN KEY (u_id) REFERENCES gw2_api_keys(u_id)
)";
$TABLE[] = "CREATE TABLE gw2_pvp_games(
	game_uuid CHAR(64) NOT NULL,
    u_id int(11) NOT NULL,
	game_map_id int(11) NOT NULL,
	game_started TIMESTAMP NOT NULL,
	game_ended TIMESTAMP NOT NULL,
    game_result TINYINT NOT NULL,
    game_team TINYINT(1) NOT NULL COMMENT '0 = blue, 1 = red',
    game_profession TINYINT NOT NULL,
    game_score_red SMALLINT NOT NULL,
    game_score_blue SMALLINT NOT NULL,
	PRIMARY KEY (game_uuid),
    KEY (u_id),
    FOREIGN KEY (u_id) REFERENCES gw2_api_keys(u_id)
)";