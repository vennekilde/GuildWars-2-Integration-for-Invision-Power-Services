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

namespace IPS\gw2integration\utils;

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

class _DataConversionUtils {
    public static $PROFESSION_NAMES = array(
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
    
    public static $RACE_NAMES = array(
        "human",
        "norn",
        "asura",
        "charr",
        "sylvari",
    );

    public static $MAP_NAMES = array(
        -1 => "Unknown",
        1011 => "Battle of Champion's Dusk",
        549 => "Battle of Kyhlo",
        554 => "Forest of Niflhel",
        795 => "Legacy of the Foefire",
        875 => "Temple of the Silent Storm",
        894 => "Beta Spirit Watch",
        900 => "Beta Skyhammer",
        984 => "Courtyard",
        1163 => "Revenge of the Capricorn"
    );
    
    /**
     * 
     * @param string $name
     * @return integer Description
     */
    public static function getProfessionId($name){
        return array_search(lcfirst($name), static::$PROFESSION_NAMES);
    }
    /**
     * 
     * @param string $professionId
     * @return integer Description
     */
    public static function getProfessionName($professionId){
        return isset(static::$PROFESSION_NAMES[$professionId]) ? ucfirst(static::$PROFESSION_NAMES[$professionId]) : "Unknown";
    }
    
    public static function getMapName($mapId){
        return isset(static::$MAP_NAMES[$mapId]) ? static::$MAP_NAMES[$mapId] : "Unknown";
    }
    
    public static function getTimestampFromGW2Time($timeStr){
        $reset = date_default_timezone_get();
        date_default_timezone_set('UTC');
        $date = date('Y-m-d H:i:s', strtotime($timeStr));
        date_default_timezone_set($reset);
         
        return $date;
    }
}