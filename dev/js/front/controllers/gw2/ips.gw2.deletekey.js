/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
;( function($, _, undefined){
	"use strict";
	var dialog;
    ips.controller.register('gw2integration.front.gw2.deletekey', {
        initialize: function () {
            this.on( 'click', this.onButtonClick );
            var dialogOptions = {
                "content" : "<div class='ipsPad'><h3>Do you want to delete saved GW2 Data also?<h3>\n\
<button onclick='$(\"input[name=gw2integration-settings-delete-data]\").val(1);$(\"input[name=gw2_api_key]\").val(\"\");$(\"input[name=gw2integration-settings-delete-data]\").closest(\"form\").submit();' class='ipsButton ipsButton_primary'>Keep</button>\n\
<button onclick='$(\"input[name=gw2integration-settings-delete-data]\").val(2);$(\"input[name=gw2_api_key]\").val(\"\");$(\"input[name=gw2integration-settings-delete-data]\").closest(\"form\").submit();' class='ipsButton'>Delete</button></div>",
                "title" : "Delete Data Also?",
                "size" : "narrow"
            };
            console.log(dialogOptions["content"]);
            dialog = ips.ui.dialog.create( dialogOptions );
            console.log("test");
		},
        
        onButtonClick: function (e) {
            dialog.show();
            e.preventDefault();
        }
    });
}(jQuery, _));