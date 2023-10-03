(function($) {
    $(() => {
        let form = $('#extrums_search_form');
        if (form.length) {
            form.on('submit', (e) => {
                e.preventDefault();

                let results_el = $('#extrums_results');
                let data = form.serializeArray()
                .reduce(function(json, {name, value}) {
                    json[name] = value;
                    return json;
                }, {});

                $.ajax({
                    url: ajaxurl,
                    method: 'POST',
                    data: data,
                    dataType: 'JSON',
                    beforeSend: () => {
                        make_placeholders(results_el);
                    },
                    success: (resp) => {
                        let data = [];

                        if ("undefined" != typeof resp.data
                        && resp.data.length) {
                            data = resp.data;
                        }

                        make_results_table(results_el, data);
                    }
                });
            });
        }

        $('body').on('submit', '.replace-form', (e) => {
            e.preventDefault();
            console.log();

            let form = $(e.currentTarget);
            let data = form.serializeArray()
            .reduce(function(json, {name, value}) {
                json[name] = value;
                return json;
            }, {});
            data.find = $('#extrums_search_string').val();

            $.ajax({
                url: ajaxurl,
                method: 'POST',
                data: data,
                dataType: 'JSON',
                beforeSend: () => {
                    // make_placeholders(results_el);
                },
                success: (resp) => {
                    console.log(resp);
                }
            });
        });
    });

    let make_placeholders = function(table) {
        let columns = 3;
        let rows = 4;
        let html = '<tbody class="placeholder-glow">';

        for (let i = 0; i < columns; i++) {
            html += '<tr>';
            for (let j = 0; j < rows; j++) {
                html += '<td class=""><span class="placeholder col-12"></span></td>';
            }
            html += '</tr>';
        }
        table.html(html);
    };

    let make_results_table = function(table, data=[]) {
        let titles = [
            { key: '', value: 'ID' },
            { key: 'title', value: 'Title' },
            { key: 'content', value: 'Content' },
        ];
        let html = '<tbody>';
        html += '<tr>' + titles.map((title) => '<th class="text-center">' + title.value + replace_form(title.key) + '</th>') + '</tr>';

        if (data.length) {
            for (const item of data) {
                html += '<tr><td>' + item.id + '</td><td>' + item.title + '</td><td>' + item.content + '</td></tr>';
            }

        } else {
            html += '<tr><td colspan="' + titles.length + '" class="text-center">Nothing found</td></tr>';
        }
        html += '</tbody>';

        table.html(html);
    };

    let replace_form = function(key) {
        let html = '';

        if ('' !== key) {
            html += '<form class="replace-form">';
            html += '<input type="text" name="replace" placeholder="new keyword...">';
            html += '<input type="hidden" name="action" value="replace_form_submit">';
            html += '<input type="hidden" name="field" value="'+key+'">';
            html += '<br>';
            html += '<input type="submit" value="Replace" class="btn btn-secondary mt-1">';
            html += '</form>';
        }

        return html;
    };
})(jQuery);