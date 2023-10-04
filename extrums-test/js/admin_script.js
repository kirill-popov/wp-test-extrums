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
                        let resp_data = [];
                        let replace_form = '';

                        if ("undefined" != typeof resp.data
                        && resp.data.length) {
                            resp_data = resp.data;
                        }
                        if ("undefined" != typeof resp.replace_form
                        && '' != resp.replace_form) {
                            replace_form = resp.replace_form;
                        }

                        make_results_table(results_el, data, resp_data, replace_form);
                    }
                });
            });
        }

        $('body').on('submit', '.replace-form', (e) => {
            e.preventDefault();

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
                success: (resp) => {
                    let resp_data = [];

                    if ("undefined" !== typeof resp.data
                    && resp.data.length) {
                        resp_data = resp.data;
                    }

                    for (item of resp_data) {
                        refresh_row(item, data.replace);
                    }
                }
            });
        });
    });

    let make_placeholders = function(table) {
        let rows = 4;
        let columns = 3;
        let html = '<tbody class="placeholder-glow">';

        for (let i = 0; i < rows; i++) {
            html += '<tr>';
            for (let j = 0; j < columns; j++) {
                html += '<td class=""><span class="placeholder col-12"></span></td>';
            }
        }
        table.html(html);
    };

    let make_results_table = function(table, data, resp_data=[], replace_form='') {
        let titles = [
            { key: '', value: 'ID' },
            { key: 'title', value: 'Title' },
            { key: 'content', value: 'Content' },
            { key: 'meta_title', value: 'Meta Title' },
            { key: 'meta_desc', value: 'Meta Description' },
        ];
        let html = '<tbody>';
        html += '<tr>' + titles.map((title) => {
            let form = title.key != '' ? replace_form.replace('%KEY%', title.key) : '';
            return '<th class="text-center">' + title.value + form + '</th>';
        }) + '</tr>';

        if (resp_data.length) {
            for (const item of resp_data) {
                html += make_row(item, data.search_string);
            }

        } else {
            html += '<tr><td colspan="' + titles.length + '" class="text-center">Nothing found</td></tr>';
        }
        html += '</tbody>';

        table.html(html);
    };

    let make_row = function(item, search_string, replace = false) {
        let bg_class = replace ? 'text-bg-success' : 'text-bg-warning';
        const regex = new RegExp('\\b' + search_string + '\\b', "gi");
        let cols = [];

        Object.keys(item).map((key) => {
            let updated_val = item[key] == null ? '' : item[key].replace(regex, '<span class="' + bg_class + '">' + search_string + '</span>');
            cols.push('<td>' + updated_val + '</td>');
        });

        return '<tr class="post-' + item.id + '">'+cols.join()+'</tr>';
    };

    let refresh_row = function(item, search_string) {
        let row = $('#extrums_results').find('.post-' + item.id);
        if (row.length) {
            row.replaceWith(make_row(item, search_string, true));
        }
    }
})(jQuery);