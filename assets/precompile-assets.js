(function($) {
    var page_url;

    $(document).ready(function() {
        page_url = Symphony.Context.get('symphony') + Symphony.Context.get('route');

        $('form').submit(function(event) {
            event.preventDefault();
            $.ajax({
                type: 'POST',
                url: page_url,
                data: $(this).serialize() + '&action[submit]',
                success: function(data) {
                    $('#compilation-log').html(data.html);
                    //$('#files-added').html(data.files_added);
                    //with_selected.options[0].selected = true;
                    //$('.actions fieldset.apply').addClass('inactive');
                },
                error: function(jqXHR, error) {
                    alert(error);
                },
                dataType: 'json',
            });
        });

    });

})(jQuery);
