(function($) {
    var page_url,
        xsrf,
        with_selected;

    $(document).ready(function() {
        page_url = Symphony.Context.get('symphony') + Symphony.Context.get('route');
        xsrf = serialize($('form input[name="xsrf"]'));
        with_selected = $('#with-selected')[0];
        //xsrf = $('form input[name="xsrf"]').serialize();

        $('#show-file-adder').click(function() {
            $(this).hide();
            $('#hide-file-adder').show();
            $('#file-adder').slideDown();
        });

        $('#hide-file-adder').click(function() {
            $(this).hide();
            $('#show-file-adder').show();
            $('#file-adder').slideUp();
        });

        $('#add-files').click(function(event) {
            $.ajax({
                type: 'POST',
                url: page_url,
                //contentType: 'application/x-www-form-urlencoded',
                data: [
                    xsrf,
                    $('#files-available').serialize(),
                    'action=add-files'
                ].join("&"),
                success: function(data) {
                    $('#files-available').html(data.files_available);
                    $('#files-added').html(data.files_added);
                },
                dataType: 'json',
                //processData: false
            });
        });

        $('form').submit(function(event) {
            event.preventDefault();
            var items = $('#files-added input:checked');
            $.ajax({
                type: 'POST',
                url: page_url,
                //data: [xsrf, serialize(items), serialize($('#with-selected'))].join("&"),
                data: [xsrf, serialize(items), $('#with-selected').serialize()].join("&"),
                success: function(data) {
                    $('#files-available').html(data.files_available);
                    $('#files-added').html(data.files_added);
                    //with_selected.options[0].selected = true;
                    with_selected.selectedIndex = 0;
                    with_selected.disabled = true;
                    $('.actions fieldset.apply').addClass('inactive');
                },
                error: function() {
                    alert("error");
                },
                dataType: 'json',
            });
        });

    });

    function serialize(objects)
    {
        var strings = [];
        objects.each(function(i) {
            strings.push(encodeURIComponent(this.name) + "=" + this.value);
        });
        return strings.join("&");
    }

})(jQuery);
