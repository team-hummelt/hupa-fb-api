<?php
/**
 * Jens Wiecker PHP Class
 * @package Jens Wiecker WordPress Plugin
 * Copyright 2021, Jens Wiecker
 * License: Commercial - goto https://www.hummelt-werbeagentur.de/
 * https://www.hummelt-werbeagentur.de/
 *
 */
$settings = apply_filters('get_fb_api_plugin_settings', false)->record;
$terms = apply_filters('get_custom_terms', 'facebook_category');
$terms->status ? $kategorie = $terms->terms : $kategorie = false;
?>
<div class="wp-bs-custom-wrapper">
    <div class="container">
        <?php

//do_action('fb_api_plugin_sync');

        ?>
        <div class="card shadow-sm">
            <h5 class="card-header bg-custom-blue py-4"><i class="fa fa-facebook-square"></i>&nbsp;
                <?= __('Import and manage Facebook content', 'hupa-fb-api') ?></h5>
            <div class="card-body pb-4">
                <div class="d-flex align-items-center">
                    <h5 class="card-title"><i class="font-blue fa fa-arrow-circle-right"></i>
                        <?= __('Facebook content and events', 'hupa-fb-api') ?></h5>
                    <span class="ms-auto d-inline-block mb-2 pe-2 statusMsg"></span>
                </div>
                <div class="border rounded shadow-sm p-3 bg-custom-gray">
                    <div class="import-btn-group">
                        <button type="button" data-bs-toggle="collapse" data-bs-target="#collapseOverviewFBSite"
                                aria-expanded="false" aria-controls="collapseOverviewFBSite"
                                class="btn-coll btn btn-outline-secondary btn-sm active"><i class="font-blue fa fa-th active"></i>&nbsp;
                            <?= __('all imported content', 'hupa-fb-api') ?>
                        </button>
                        <button type="button" data-bs-toggle="collapse" data-bs-target="#collapseAddFBSite"
                                aria-expanded="true" aria-controls="collapseAddFBSite"
                                class="btn-coll btn btn-outline-secondary btn-sm"><i
                                    class="font-blue fa fa-plus"></i>&nbsp; <?= __('Import new content', 'hupa-fb-api') ?>
                        </button>
                    </div>
                    <hr>
                    <div id="display_data">
                        <!--Collapse ADD Sites -->
                        <div class="collapse" id="collapseAddFBSite" data-bs-parent="#display_data">
                            <form class="send-ajax-btn-plugin-form" action="#" method="post">
                                <input type="hidden" name="method" value="fb_api_import_form_handle">
                                <input type="hidden" name="type" value="insert">
                                <div class="row pb-3">
                                    <div class="col-lg-6">
                                        <label for="ImportName" class="form-label">
                                            <?= __('Name or location for this import:', 'hupa-fb-api') ?>
                                            <span class="text-danger"> *</span> </label>
                                        <input type="text" name="import_name" autocomplete="cc-number"
                                               class="form-control"
                                               id="ImportName" aria-describedby="ImportNameHelp" required>
                                        <div id="ImportNameHelp" class="form-text">
                                            <?= __('This name is displayed on the website..', 'hupa-fb-api') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row pb-3">
                                    <div class="col-lg-6">
                                        <label for="ImportDescription" class="form-label">
                                            <?= __('Description for this import:', 'hupa-fb-api') ?> </label>
                                        <textarea class="form-control" name="post_description" id="ImportDescription"
                                                  rows="3"></textarea>
                                        <div id="ImportDescriptionHelp" class="form-text">
                                            <?= __('The description is optional.', 'hupa-fb-api') ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="row pb-1">
                                    <div class="col-lg-6">
                                        <label for="ImportCount" class="form-label">
                                            <?= __('Max. Number of posts and events Import:', 'hupa-fb-api') ?>
                                        </label>
                                        <input type="number" name="import_count" autocomplete="cc-number"
                                               class="form-control" placeholder="max: 100" max="100" id="ImportCount"
                                               aria-describedby="ImportNameHelp">
                                        <div id="ImportCountHelp" class="form-text">
                                            <?= __('The standard value is 100.', 'hupa-fb-api') ?>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="card-title"><?= __('Facebook Api and WordPress Options:', 'hupa-fb-api') ?> </h6>
                                <hr>
                                <div class="row pb-3">
                                    <div class="col-lg-6">
                                        <label for="FBPageId" class="form-label">Facebook Page ID:</label>
                                        <input type="text" name="page_id" autocomplete="cc-number"
                                               class="form-control"
                                               id="FBPageId" aria-describedby="FBPageIdHelp">
                                        <div id="FBPageIdHelp" class="form-text">
                                            <?= __('If you do not enter a Page ID or User ID, your Facebook User ID will be used.', 'hupa-fb-api') ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="row pb-1 align-items-center">
                                    <div class="col-lg-6">
                                        <label for="inputUserIDS"
                                               class="form-label"><?= __('Facebook User-ID:', 'hupa-fb-api') ?></label>
                                        <input type="text" name="user_id" autocomplete="cc-number"
                                               class="form-control" id="inputUserIDS">
                                        <div id="inputUserIDSHelp" class="form-text">
                                            <?= __('Only posts and events of the user are imported.', 'hupa-fb-api') ?>
                                        </div>
                                    </div>
                                    <div class="col-lg-12 pt-3">
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" name="check_user_id" type="checkbox"
                                                   id="CheckUserIdActive">
                                            <label class="form-check-label" for="CheckUserIdActive">
                                                <?= __('User ID active', 'hupa-fb-api') ?></label>
                                        </div>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="card-title"> <?= __('Select category for Facebook posts and events:', 'hupa-fb-api') ?>
                                    <div class="form-text"><?= __('If you do not select a category, the default categories for posts or events will be used.', 'hupa-fb-api') ?></div>
                                </h6>
                                <hr>
                                <div class="row pb-3">
                                    <div class="col-lg-5">
                                        <label for="SelectPostCat"
                                               class="form-label"><?= __('Category for posts:', 'hupa-fb-api') ?></label>
                                        <select class="form-select" name="post_cat" id="SelectPostCat"
                                                aria-label="Post Category">
                                            <option><?= __('select', 'hupa-fb-api') ?> ...</option>
                                            <?php if ($kategorie):
                                                foreach ($kategorie as $tmp): ?>
                                                    <option value="<?= $tmp->term_id ?>"><?= $tmp->name ?></option>
                                                <?php endforeach; endif; ?>
                                        </select>
                                    </div>
                                </div>

                                <div class="row pb-3">
                                    <div class="col-lg-5">
                                        <label for="SelectEventId" class="form-label">
                                            <?= __('Category for events:', 'hupa-fb-api') ?> </label>
                                        <select class="form-select" name="event_cat" id="SelectEventId"
                                                aria-label="Event Category">
                                            <option><?= __('select', 'hupa-fb-api') ?> ...</option>
                                            <?php if ($kategorie):
                                                foreach ($kategorie as $tmp): ?>
                                                    <option value="<?= $tmp->term_id ?>"><?= $tmp->name ?></option>
                                                <?php endforeach; endif; ?> </select>
                                    </div>
                                </div>
                                <hr>
                                <h6 class="card-title"> <?= __('Create a new category for Facebook posts or events:', 'hupa-fb-api') ?> </h6>
                                <hr>
                                <button type="button" class="btn btn-outline-secondary mb-3 btn-sm"
                                        data-bs-toggle="modal" data-bs-target="#addCategoryModal">
                                    <i class="fa fa-list"></i>&nbsp; <?= __('Create new category', 'hupa-fb-api') ?>
                                </button>
                                <hr>
                                <button type="submit" class="btn btn-blue my-2"><i class="fa fa-facebook-official"></i>&nbsp; <?= __('Create a new Facebook import', 'hupa-fb-api') ?>
                                </button>
                            </form>
                        </div>

                        <!--Collapse SITES OVERVIEW -->
                        <div class="collapse show" id="collapseOverviewFBSite" data-bs-parent="#display_data">
                            <div id="TableWrapper" class="table-responsive-xl py-4">
                                <table id="TableImports" class="table table-md table-striped table-bordered"
                                       style="width:100%">
                                    <thead>
                                    <tr>
                                        <th><?= __('Designation', 'hupa-fb-api'); ?></th>
                                        <th><?= __('active', 'hupa-fb-api'); ?></th>
                                        <th><?= __('Max. Import', 'hupa-fb-api'); ?></th>
                                        <th><?= __('User ID', 'hupa-fb-api'); ?></th>
                                        <th><?= __('Page ID', 'hupa-fb-api'); ?></th>
                                        <th><?= __('User active', 'hupa-fb-api'); ?></th>
                                        <th><?= __('Post Category', 'hupa-fb-api'); ?></th>
                                        <th><?= __('Event Category', 'hupa-fb-api'); ?></th>
                                        <th><?= __('Edit', 'hupa-fb-api'); ?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>

                            <div id="importEdit"></div>
                            <!------>
                        </div><!--Collapse-Parent-Data-->
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="AjaxResponseModal" tabindex="-1" aria-labelledby="AjaxResponseModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="AjaxResponseModalTitle">Response</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i
                                        class="text-danger fa fa-times"></i> <?= __('Close', 'hupa-fb-api') ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal -->
            <div class="modal fade" id="FBAPIDeleteModal" tabindex="-1" aria-labelledby="FBAPIDeleteModal"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <div class="modal-header bg-danger text-light">
                            <h5 class="modal-title"><i class="fa fa-trash"></i> <span class="header-msg"></span></h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center"></div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">
                                <i class="text-danger fa fa-times"></i> <?= __('Cancel', 'hupa-fb-api') ?></button>
                            <button id="btnDeleteModal" type="button" class="btn-delete-modal btn btn-danger" data-bs-dismiss="modal">
                                <i class="fa fa-trash"></i> <?= __('Delete', 'hupa-fb-api') ?></button>
                        </div>
                    </div>
                </div>
            </div>

            <!--ADD CATEGORY MODAL-->
            <div class="modal fade" id="addCategoryModal" tabindex="-1" aria-labelledby="addCategoryModalLabel"
                 aria-hidden="true">
                <div class="modal-dialog">
                    <div class="modal-content">
                        <form id="modalFormular" class="send-ajax-plugin-formular" action="#" method="post">
                            <input type="hidden" name="method" value="set_facebook_category">
                            <div class="modal-header bg-custom-blue">
                                <h5 class="modal-title" id="exampleModalLabel"><i
                                            class="fa fa-facebook-square"></i>&nbsp; <?= __('Create a new category', 'hupa-fb-api') ?>
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal"
                                        aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <div class="mb-3">
                                    <label for="category-name" class="col-form-label"><?= __('Name', 'hupa-fb-api') ?>:
                                        <span class="text-danger"> *</span> </label>
                                    <input type="text" name="cat_name" class="form-control" id="category-name" required>
                                    <div id="category-nameHelp"
                                         class="form-text"> <?= __('This name is then displayed on the website.', 'hupa-fb-api') ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="categorySlug"
                                           class="col-form-label"><?= __('Title form', 'hupa-fb-api') ?>:
                                        <span class="text-danger"> *</span></label>
                                    <input type="text" name="cat_slug" class="form-control" id="categorySlug" required>
                                    <div id="categorySlugHelp"
                                         class="form-text"><?= __('The "title form" is the readable URL variant of the name. It usually consists only of lower case letters, numbers and hyphens.', 'hupa-fb-api') ?>
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label for="CategoryDescription"
                                           class="col-form-label"><?= __('Description', 'hupa-fb-api') ?>
                                        :</label>
                                    <textarea class="form-control" name="description" id="CategoryDescription"
                                              rows="5"></textarea>
                                    <div id="CategoryDescriptionHelp" class="form-text">
                                        <?= __('The description is not always displayed. In some themes it may be displayed.', 'hupa-fb-api') ?>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer bg-custom-gray">
                                <button type="button" class="btn btn-light border" data-bs-dismiss="modal"><i
                                            class="text-danger fa fa-times"></i>&nbsp;<?= __('Cancel', 'hupa-fb-api') ?>
                                </button>
                                <button type="submit" class="btn btn-blue"><i
                                            class="fa fa-facebook-f"></i>&nbsp; <?= __('create', 'hupa-fb-api') ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div><!--BS-Wrapper-->
    </div>
</div>
        <div id="snackbar-success"></div>
        <div id="snackbar-warning"></div>