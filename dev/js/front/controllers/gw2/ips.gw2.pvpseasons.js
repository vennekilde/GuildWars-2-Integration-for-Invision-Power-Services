/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
;( function($, _, undefined){
	"use strict";
	
    ips.controller.register('gw2integration.front.gw2.pvpseasons', {
        initialize: function () {
            this.on('click',function() {
                this.switchPvPSeasonTab(this.scope[0].id.substring(14));
            });
		},

		switchPvPSeasonTab: function (seasonUUID) {
            $(".season").hide();
            $("#season_" + seasonUUID).show();
		}
    });

}(jQuery, _));