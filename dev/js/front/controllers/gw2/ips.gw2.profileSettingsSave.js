/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
;
(function ($, _, undefined) {
    "use strict";

    ips.controller.register('gw2integration.front.gw2.tabhandler', {
        initialize: function () {
            $('#some-form').submit(function (e) {
                e.preventDefault();

                $('#more-inputs input').each(function () {
                    var el = $(this);
                    $('<input type="hidden" name="' + el.attr('name') + '" />')
                            .val(el.val())
                            .appendTo('#some-form');
                });

                $.get('http://yoururl.com', $('#some-form').serialize(), function (data) {
                    alert('handle your data here: ' + data);
                });

            });
        },
    });
}(jQuery, _));