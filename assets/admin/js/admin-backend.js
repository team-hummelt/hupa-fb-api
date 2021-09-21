jQuery(document).ready(function ($) {

    /*+++++++++++++++++++++++++++++++++++++
     *+++++++ FB-Plugin Settings ++++++++++
     *+++++++++++++++++++++++++++++++++++++
     */
    let SettingsFormTimeout;
    $('.send-ajax-plugin-settings').on('input propertychange change', function () {
        $('.statusMsg').html('<i class="fa fa-spinner fa-spin"></i>&nbsp; Saving...');
        const form_data = $(this).serializeObject();
        clearTimeout(SettingsFormTimeout);
        SettingsFormTimeout = setTimeout(function () {
            set_settings_form_input(form_data);
        }, 1000);
    });

    function set_settings_form_input(form_data) {
        $.ajax({
            'url': hupa_ajax_obj.ajax_url,
            'type': 'POST',
            'data': {
                '_ajax_nonce': hupa_ajax_obj.nonce,
                'action': 'HupaApiHandle',
                'daten': form_data
            },
            'success': function (data) {
                if (data.status) {
                    $('.statusMsg').html('<i class="text-success fa fa-check"></i>&nbsp; Saved! Last: ' + data.msg);
                    fb_api_settings_validate_formular(data.err_arr, data.success_arr);
                } else {
                    $('.statusMsg').html('<i class="text-danger fa fa-exclamation-triangle"></i>&nbsp; ' + data.msg);
                }
            },
            'error': function (request, error) {
                console.log(error);
            }
        });
        return false;
    }

    function fb_api_settings_validate_formular(error, success) {
        if (error) {
            $.each(error, function (key, val) {
                $("[name='" + val + "']").addClass('is-invalid').removeClass('is-valid');
            });
        }
        if (success) {
            $.each(success, function (key, val) {
                $("[name='" + val + "']").removeClass('is-invalid').addClass('is-valid');
            });
        }
    }

    /*+++++++++++++++++++++++++++++++++++++++
    *+++++++ FORMULAR BTN SAVE AJAX +++++++++
    *++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('submit', '.send-ajax-plugin-formular', function () {
        $('.send-ajax-plugin-formula input').removeClass('is-invalid');
        const form_data = $(this).serializeObject();

        $.ajax({
            'url': hupa_ajax_obj.ajax_url,
            'type': 'POST',
            'data': {
                '_ajax_nonce': hupa_ajax_obj.nonce,
                'action': 'HupaApiHandle',
                'daten': form_data
            },
            'success': function (data) {
                if (data.status) {
                    let FormModalEl = document.getElementById('addCategoryModal');
                    let modalForm = document.getElementById("modalFormular");
                    let modalBodyInput = document.querySelector('#modalFormular');
                    let invalidForm = modalBodyInput.querySelectorAll(".form-control");
                    invalidForm.forEach(function (userItem) {
                        userItem.classList.remove('is-invalid');
                    });
                    modalForm.reset();
                    let modal = bootstrap.Modal.getInstance(FormModalEl);
                    modal.hide();
                    let sel = '';
                    let select = `<option value="">${data.selLang} ...</option>`;

                    $.each(data.select, function (key, val) {
                        if (data.catName === val.name) {
                            sel = 'selected';
                        } else {
                            sel = '';
                        }
                        select += `<option value="${val.term_id}" ${sel}>${val.name}</option>`;
                    });
                    $("[name='post_cat']").html(select);
                    $("[name='event_cat']").html(select);
                    success_message(data.msg);
                } else {
                    warning_message(data.msg);
                    $("[name='" + data.name + "']").addClass('is-invalid').removeClass('is-valid');
                }
            },
            'error': function (request, error) {
                console.log(error);
            }
        });
        return false;
    });


    /*+++++++++++++++++++++++++++++++++++++++
    *+++++++ FORMULAR BTN SAVE AJAX +++++++++
    *++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('submit', '.send-ajax-btn-plugin-form', function () {
        $('.send-ajax-btn-plugin-form .form-control').removeClass('is-invalid');
        const form_data = $(this).serializeObject();
        $.ajax({
            'url': hupa_ajax_obj.ajax_url,
            'type': 'POST',
            'data': {
                '_ajax_nonce': hupa_ajax_obj.nonce,
                'action': 'HupaApiHandle',
                'daten': form_data
            },
            'success': function (data) {
                if (data.status) {
                    success_message(data.msg);
                    if (data.reset) {
                        $('.send-ajax-btn-plugin-form').trigger('reset');
                    }
                } else {
                    warning_message(data.msg);
                    if (data.feeld) {
                        $("[name='" + data.feeld + "']").addClass('is-invalid');
                    }
                }
            },
            'error': function (request, error) {
                console.log(error);
            }
        });
        return false;
    });


    /*+++++++++++++++++++++++++++++++++++++++
     *+++++++ TOGGLE COLLAPSE BTN  ++++++++++
    *++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('click', '.btn-coll', function () {
        $('#TableWrapper').show();
        $("#TableImports").DataTable().draw('page');
        $('#importEdit').empty();
        $('.send-ajax-btn-plugin-form').trigger('reset');
        if ($(this).hasClass('active')) {
            $(this).removeClass('active');
            $('i.fa', this).removeClass('active');
            return false;
        }
        $('.import-btn-group .btn').removeClass("active");
        $(this).addClass('active');
        $('.import-btn-group i.fa').removeClass("active");
        $('i.fa', this).addClass('active');
    });

    /*++++++++++++++++++++++++++++++++++++
    *+++++++ CHANGE TRIGGER BLUR +++++++++
    *+++++++++++++++++++++++++++++++++++++
    */
    $(document).on('change', '.form-select', function () {
        $(this).trigger("blur");
    });

    /*+++++++++++++++++++++++++++++
    *+++++++ CLICK BLUR  ++++++++++
    *++++++++++++++++++++++++++++++
    */
    $(document).on('click', '.form-check-input', function () {
        $(this).trigger("blur");
    });

    /*+++++++++++++++++++++++++++++++++++++++
    *+++++++ CHECK IMPORTS ACTIVE  ++++++++++
    *++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('click', '#TableImports .form-check-input', function () {

        $(this).trigger("blur");
        $.post(hupa_ajax_obj.ajax_url, {
            '_ajax_nonce': hupa_ajax_obj.nonce,
            'action': 'HupaApiHandle',
            'method': 'change_import_settings',
            'id': $(this).attr('data-id'),
            'type': $(this).attr('data-type')
        }, function (data) {
            if (!data.status) {
                warning_message(data.msg);
            }
        });

    });

    /*+++++++++++++++++++++++++++++++++++++++
    *+++++++ CHECK CRONJOB ACTIVE  ++++++++++
    *++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('click', '#CheckCronActive', function () {
        $(this).trigger("blur");
        $('#inputSyncInterval').attr('disabled', function (_, attr) {
            return !attr
        });
        $('#inputMaxPosts').attr('disabled', function (_, attr) {
            return !attr
        });
        $('#CronSyncBtn').attr('disabled', function (_, attr) {
            return !attr
        });
    });


    /*+++++++++++++++++++++++++++++++++++++++
    *+++++++ Show Access Token BTN ++++++++++
    *++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('click', '.show-access-token', function () {
        $(this).trigger("blur");
        if ($(this).hasClass('show')) {
            $(this).removeClass('show');
            $('.show-btn-text').html('<i class="text-danger fa fa-eye-slash"></i>&nbsp;Token ausblenden');
            $.post(hupa_ajax_obj.ajax_url, {
                    '_ajax_nonce': hupa_ajax_obj.nonce,
                    'action': 'HupaApiHandle',
                    'method': 'show_fb_access_token',
                },
                function (data) {
                    $("[name='token']").val(data.msg);
                });

        } else {
            $(this).addClass('show');
            $('.show-btn-text').html('<i class="font-blue fa fa-eye"></i>&nbsp;Token anzeigen');
            $("[name='token']").val('');
        }
    });

    /*++++++++++++++++++++++++++++++++++++++++++++++
    *+++++++ CHECK Access Token BTN MODAL ++++++++++
    *+++++++++++++++++++++++++++++++++++++++++++++++
    */
    let NotificationModal = document.getElementById('AjaxResponseModal');
    if (NotificationModal) {
        NotificationModal.addEventListener('show.bs.modal', function (event) {
            let button = event.relatedTarget
            let method = button.getAttribute('data-bs-method');
            let modalTitle = NotificationModal.querySelector('.modal-title');
            let modalHeader = NotificationModal.querySelector('.modal-header');
            let modalBody = NotificationModal.querySelector('.modal-body');
            modalHeader.classList.remove('success-bg');
            modalHeader.classList.remove('error-bg');
            $.post(hupa_ajax_obj.ajax_url, {
                    '_ajax_nonce': hupa_ajax_obj.nonce,
                    'action': 'HupaApiHandle',
                    'method': method,
                },
                function (data) {
                    modalTitle.innerHTML = data.head;
                    modalBody.innerHTML = data.msg;
                    if (data.status) {
                        modalHeader.classList.add('success-bg');
                    } else {
                        modalHeader.classList.add('error-bg');
                    }
                });
        });
    }

    /*++++++++++++++++++++++++++++++++++
    *+++++++ DELETE BTN MODAL ++++++++++
    *+++++++++++++++++++++++++++++++++++
    */
    let DangerModal = document.getElementById('FBAPIDeleteModal');
    if (DangerModal) {
        DangerModal.addEventListener('show.bs.modal', function (event) {
            let button = event.relatedTarget
            let type = button.getAttribute('data-bs-type');
            let id = button.getAttribute('data-bs-id');
            let btnId = DangerModal.querySelector('.btn-delete-modal');
            let modalTitle = DangerModal.querySelector('.header-msg');
            let modalBody = DangerModal.querySelector('.modal-body');
            switch (type) {
                case 'import':
                    btnId.setAttribute('data-id', id);
                    btnId.setAttribute('data-type', type);
                    modalTitle.innerHTML = hupa_fb_api.lang_modal.del_header_import;
                    modalBody.innerHTML = `<h5>${hupa_fb_api.lang_modal.del_import}<small class="d-block">${hupa_fb_api.lang_modal.delete_note}</small></h5>`;
                    break;
                case 'delete-posts':
                    btnId.setAttribute('data-id', id);
                    btnId.setAttribute('data-type', type);
                    modalTitle.innerHTML = hupa_fb_api.lang_modal.del_header_post;
                    modalBody.innerHTML = `<h5>${hupa_fb_api.lang_modal.del_post}<small class="d-block">${hupa_fb_api.lang_modal.delete_note}</small></h5>`;
                    break;
            }
        });
    }

    $(document).on('click', '.btn-delete-modal', function () {
        $.post(hupa_ajax_obj.ajax_url, {
            '_ajax_nonce': hupa_ajax_obj.nonce,
            'action': 'HupaApiHandle',
            'method': 'api_delete_handle',
            'id': $(this).attr('data-id'),
            'type': $(this).attr('data-type'),
        }, function (data) {
            if (data.status) {
                if (data.reload) {
                    $("#TableImports").DataTable().draw('page');
                    $('#importEdit').empty();
                    $('#TableWrapper').slideDown(400);
                }
                if(data.msg){
                    success_message(data.msg);
                }
            } else {
                warning_message(data.msg);
            }
        });
    });

    /*+++++++++++++++++++++++++++++
    *+++++++ BTN SYNC FB ++++++++++
    *++++++++++++++++++++++++++++++
    */
    $(document).on('click', '.btn-sync-fb', function () {
        $.post(hupa_ajax_obj.ajax_url, {
            '_ajax_nonce': hupa_ajax_obj.nonce,
            'action': 'HupaApiHandle',
            'method': 'syn_fb_posts',
            'id': $(this).attr('data-id'),
        }, function (data) {
            if (data.status) {
                success_message(data.msg);
            } else {
                warning_message(data.msg);
            }
        });
    });

    /*+++++++++++++++++++++++++++++
    *+++++++ BACK BUTTON ++++++++++
    *++++++++++++++++++++++++++++++
    */
    $(document).on('click', '.btn-page-back', function () {
        $(this).trigger('blur');
        let type = $(this).attr('data-type');
        switch (type) {
            case 'show-import-table':
                $("#TableImports").DataTable().draw('page');
                $('#importEdit').empty();
                $('#TableWrapper').slideDown(400);

                break;
        }
    });

    /*++++++++++++++++++++++++++++++++++++++++
    *+++++++ EDIT IMPORT FORM BY ID ++++++++++
    *+++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('click', '.btn-sync-reset-fb', function () {
        $.post(hupa_ajax_obj.ajax_url, {
            '_ajax_nonce': hupa_ajax_obj.nonce,
            'action': 'HupaApiHandle',
            'method': 'reset_import_date',
            'id': $(this).attr('data-id'),
        }, function (data) {
            if (data.status) {
                success_message(data.msg);
            } else {
                warning_message(data.msg);
            }
        });
    });


    /*++++++++++++++++++++++++++++++++++++++++
    *+++++++ EDIT IMPORT FORM BY ID ++++++++++
    *+++++++++++++++++++++++++++++++++++++++++
    */
    $(document).on('click', '.btn-import-edit', function () {
        $.post(hupa_ajax_obj.ajax_url, {
            '_ajax_nonce': hupa_ajax_obj.nonce,
            'action': 'HupaApiHandle',
            'method': 'get_import_by_id',
            'id': $(this).attr('data-id'),
        }, function (data) {
            if (data.status) {
                render_import_edit_tpl(data);
            } else {
                warning_message(data.msg);
            }
        });
    });

    function render_import_edit_tpl(data) {
        let lang = data.lang;
        let html = `
            <button data-type="show-import-table" type="button" class="btn-page-back btn btn-blue my-2"><i class="fa fa-mail-reply-all"></i>&nbsp; 
            ${lang.btn_back}
            </button>
            <button data-id="${data.id}" type="button" class="btn-sync-fb btn btn-blue my-2"><i class="fa fa-history"></i>&nbsp; 
            ${lang.btn_sync}
            </button>
            <button data-id="${data.id}" type="button" class="btn-sync-reset-fb btn btn-blue my-2"><i class="fa fa-random"></i>&nbsp; 
            ${lang.btn_reset_import}
            </button>
            <hr>
            <form class="send-ajax-btn-plugin-form" action="#" method="post">
            <input type="hidden" name="method" value="fb_api_import_form_handle">
            <input type="hidden" name="type" value="update">
            <input type="hidden" name="id" value="${data.id}">
            <div class="row pb-3">
            <div class="col-lg-6">
            <label for="ImportNameUpd" class="form-label">
            ${lang.import_name}
            <span class="text-danger"> *</span> </label>
            <input type="text" name="import_name" value="${data.bezeichnung}" autocomplete="cc-number"
            class="form-control" id="ImportNameUpd" aria-describedby="ImportNameUpdHelp" required>
            <div id="ImportNameUpdHelp" class="form-text">
            ${lang.import_name_help}
            </div>
            </div>
            </div>
            <div class="row pb-3">
            <div class="col-lg-6">
            <label for="ImportDescriptionUpd" class="form-label">
            ${lang.import_description}</label>
            <textarea class="form-control" name="post_description" id="ImportDescriptionUpd" rows="3">${data.description}</textarea>
            <div id="ImportDescriptionUpdHelp" class="form-text">
            ${lang.import_description_help}
            </div>
            </div>
            </div>
            <div class="row pb-1">
            <div class="col-lg-6">
            <label for="ImportCountUpd" class="form-label">
            ${lang.max_number_events}
            </label>
            <input type="number" name="import_count" autocomplete="cc-number"
            class="form-control" placeholder="max: 100" max="100" id="ImportCountUpd" value="${data.max_count}" aria-describedby="ImportCountUpdHelp">
            <div id="ImportCountUpdHelp" class="form-text">
            ${lang.max_number_events_help}
            </div>
            </div>
            </div>
            <hr>
            <h6 class="card-title">${lang.header_api_options}</h6>
            <hr>
            <div class="row pb-3">
            <div class="col-lg-6">
            <label for="FBPageIdUpd" class="form-label">Facebook Page ID:</label>
            <input type="text" name="page_id" value="${data.page_id}" autocomplete="cc-number"
            class="form-control" id="FBPageIdUpd" aria-describedby="FBPageIdUpdHelp">
            <div id="FBPageIdUpdHelp" class="form-text">
            ${lang.page_id_id_help}
            </div>
            </div>
            </div>
            <div class="row pb-1 align-items-center">
            <div class="col-lg-6">
            <label for="inputUserIDSUpd" class="form-label">${lang.fb_user_id}</label>
            <input type="text" name="user_id" value="${data.user_id}"  autocomplete="cc-number"
            class="form-control" id="inputUserIDSUpd">
            <div id="inputUserIDSUpdHelp" class="form-text">
            ${lang.fb_user_help}
            </div>
            </div>
            <div class="col-lg-12 pt-3">
            <div class="form-check form-switch">
            <input class="form-check-input" name="check_user_id" type="checkbox"
            id="CheckUserIdActiveUpd" ${data.user_aktiv}>
            <label class="form-check-label" for="CheckUserIdActiveUpd">
            ${lang.user_id_aktiv}</label>
            </div>
            </div>
            </div>
            <hr>
            <h6> ${lang.header_select_cat}
            <div class="form-text">${lang.header_select_cat_sm}</div>
            </h6>
            <hr>
            <div class="row pb-3">
            <div class="col-lg-5">
            <label for="SelectPostCatUpd" class="form-label">${lang.category_select}</label>
            <select class="form-select" name="post_cat" id="SelectPostCatUpd"
            aria-label="Post Category">
            <option>${lang.select} ...</option> `;
        let sel = '';
        $.each(data.select, function (key, val) {
            // noinspection EqualityComparisonWithCoercionJS
            data.post_term_id == val.term_id ? sel = 'selected' : sel = '';
            html += `<option value="${val.term_id}" ${sel}>${val.name}</option>`;
        });
        html += `</select>
            </div>
            </div>
            <div class="row pb-3">
            <div class="col-lg-5">
            <label for="SelectEventIdUpd" class="form-label">
            ${lang.category_select}</label>
            <select class="form-select" name="event_cat" id="SelectEventIdUpd"
            aria-label="Event Category">
            <option>${lang.select} ...</option>`;
        $.each(data.select, function (key, val) {
            // noinspection EqualityComparisonWithCoercionJS
            sel = data.event_term_id == val.term_id ? 'selected' : '';
            html += `<option value="${val.term_id}" ${sel}>${val.name}</option>`;
        });
        html += `</select>
            </div>
            </div>
            <hr>
            <h6 class="card-title">${lang.header_new_cat}</h6>
            <hr>
            <button type="button" class="btn btn-outline-secondary mb-3 btn-sm"
            data-bs-toggle="modal" data-bs-target="#addCategoryModal">
            <i class="fa fa-list"></i>&nbsp; ${lang.btn_new_cat}
            </button>
            <hr>
            <button type="submit" class="btn btn-blue my-2"><i class="fa fa-facebook-official"></i>&nbsp; 
            ${lang.btn_update_import}            
            </button>
            <button type="button" data-bs-type="delete-posts" data-bs-id="${data.id}" data-bs-toggle="modal" data-bs-target="#FBAPIDeleteModal" class="btn btn-warning my-2"><i class="fa fa-trash"></i>&nbsp; 
            ${lang.btn_del_posts}            
            </button>
            <button type="button" data-bs-type="import" data-bs-id="${data.id}" data-bs-toggle="modal" data-bs-target="#FBAPIDeleteModal" class="btn btn-danger my-2"><i class="fa fa-trash"></i>&nbsp; 
            ${lang.btn_del_import}            
            </button>
            </form>`;

        $('#TableWrapper').slideUp(400);
        $('#importEdit').html(html);
    }


    //Message Handle
    function success_message(msg) {
        let x = document.getElementById("snackbar-success");
        $("#snackbar-success").text(msg);
        x.className = "show";
        setTimeout(function () {
            x.className = x.className.replace("show", "");
        }, 3000);
    }

    function warning_message(msg) {
        let x = document.getElementById("snackbar-warning");
        $("#snackbar-warning").text(msg);
        x.className = "show";
        setTimeout(function () {
            x.className = x.className.replace("show", "");
        }, 3000);
    }

    $.fn.serializeObject = function () {
        let o = {};
        let a = this.serializeArray();
        $.each(a, function () {
            if (o[this.name] !== undefined) {
                if (!o[this.name].push) {
                    o[this.name] = [o[this.name]];
                }
                o[this.name].push(this.value || '');
            } else {
                o[this.name] = this.value || '';
            }
        });
        return o;
    };
});


