/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
;( function($, _, undefined){
	"use strict";
	
    ips.controller.register('gw2integration.front.gw2.tabhandler', {
        initialize: function () {
            console.log("test");
            var tab = this.getUrlParameter("tab2");
            if(tab != null){
                $("#" + tab).click();
            }
		},
        
        getUrlParameter: function (sParam) {
            var sPageURL = decodeURIComponent(window.location.search.substring(1)),
                sURLVariables = sPageURL.split('&'),
                sParameterName,
                i;

            for (i = 0; i < sURLVariables.length; i++) {
                sParameterName = sURLVariables[i].split('=');

                if (sParameterName[0] === sParam) {
                    return sParameterName[1] === undefined ? true : sParameterName[1];
                }
            }
        }
    });
}(jQuery, _));