/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
;( function($, _, undefined){
	"use strict";
	
    ips.controller.register('gw2integration.front.gw2.accountoverview', {
        initialize: function () {
            if($(this.scope[0]).attr("data-hide-alt-acc-name") == "1"){
                $(this.scope[0]).closest(".cAuthorPane").find("li:contains('GW2 Account:')").hide();
            }
            if($(this.scope[0]).attr("data-hide-alt-guild") == "1"){
                $(this.scope[0]).closest(".cAuthorPane").find("li:contains('Guild Tag:')").hide();
            }
		}
    });

}(jQuery, _));