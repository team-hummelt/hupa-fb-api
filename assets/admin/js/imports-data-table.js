/*+
 *
 *  Jens Wiecker PHP Class
 *  @package Jens Wiecker WordPress Plugin
 *  Copyright 2021, Jens Wiecker
 *  License: Commercial - goto https://www.hummelt-werbeagentur.de/
 *  https://www.hummelt-werbeagentur.de/
 *
 */

jQuery(document).ready(function ($) {
    $('#TableImports').DataTable({
        "language": {
            "url": hupa_fb_api .data_table
        },

        "dom": 'Blfrtip',
        lengthChange: true,
        "columns": [
            {
                "width": "10%"
            },
            {
                "width": "10%"
            },
            {
                "width": "10%"
            },
            null,
            null,
            {
                "width": "7%"
            },
            {
                "width": "7%"
            },
            {
                "width": "7%"
            },
            {
                "width": "20"
            }
        ],
        columnDefs: [{
            orderable: false,
            targets: [1,5,6,7,8]
        },
            {
                targets: [1, 5, 8 ],
                className: 'text-center'
            },
            {
                targets: [1,5],
                className: 'align-middle'
            }
        ],
        "processing": true,
        "serverSide": true,
        "order": [],
        "ajax": {
            url: hupa_ajax_obj.ajax_url,
            type: 'POST',
            data : {
                action: 'HupaApiHandle',
                '_ajax_nonce': hupa_ajax_obj.nonce,
                method: 'imports_data_table'
            }
        }
    });
});

