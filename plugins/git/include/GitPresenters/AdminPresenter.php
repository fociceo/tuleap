<?php
/**
 * Copyright (c) Enalean, 2013. All rights reserved
 *
 * This file is a part of Tuleap.
 *
 * Tuleap is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * Tuleap is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/
 */

class GitPresenters_AdminPresenter {

    public $project_id;

    /**
     * List of repositories belonging to the project or the parent project
     *
     * @var array
     */
    private $repository_list;

    public function __construct($repository_list, $project_id) {
        $this->repository_list = $repository_list;
        $this->project_id      = $project_id;
    }

    public function git_admin() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_title');
    }

    public function configurations_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_repos_list');
    }

    public function templates_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_templates_list');
    }

    public function edit_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_edit_configuration_label');
    }

    public function file_name_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_file_name_label');
    }

    public function save_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_submit_button');
    }

    public function config_option() {
        return array_values($this->repository_list);
    }

    public function template_section_title() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_section_title');
    }

    public function template_section_description() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_section_description');
    }

    public function please_choose() {
        return $GLOBALS['Language']->getText('plugin_git', 'please_choose');
    }

    public function create_new_template_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_create_new_template_text');
    }

    public function template_from_gerrit_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_from_gerrit_text');
    }

    public function template_from_template_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_from_template_text');
    }

    public function template_from_scratch_text() {
        return $GLOBALS['Language']->getText('plugin_git', 'view_admin_template_from_scratch_text');
    }

}
?>
