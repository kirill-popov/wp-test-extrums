(function($) {
    $(() => {
        let form = $('#extrums_search_form');
        if (form.length) {
            form.on('submit', (e) => {
                e.preventDefault();

                let data = form.serializeArray()
                .reduce(function (json, {name, value}) {
                    json[name] = value;
                    return json;
                }, {});

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: data,
                    success: (resp) => {
                        console.log(resp);
                    }
                });
            });
        }
    });
})(jQuery);