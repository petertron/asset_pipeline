(function($) {
    var response_html = "";

    $(document).ready(function() {
        $('#compile').click(function(event) {
            event.preventDefault();
            $('#compilation-log').html("Contacting server...");
            $.ajax({
                type: 'GET',
                url: Symphony.Context.get('symphony') + '/extension/asset_pipeline/precompile_assets_ajax/',
                data: 'action=compile',
                success: function(html) {
                    response_html = html;
                },
                error: function(jqXHR, text_status, error_thrown) {
                    if (jqXHR.readyState == 0 || jqXHR.status == 0) {
                        response_html = "HTTP request has not been carried out.";
                    } else {
                        response_html = "Error: " + text_status;
                        if (error_thrown) {
                            response_html += "<br><br>" + error_thrown;
                        }
                    }
                },
                statusCode: {
                    '500': function() {
                        response_html = "An internal server error has occurred.";
                    }
                },
                complete: function() {
                    $('#compilation-log').html(response_html);
                }
            });
        });
    });

})(jQuery);
