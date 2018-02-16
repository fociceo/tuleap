<?php
/**
 * Copyright (c) Enalean, 2018. All Rights Reserved.
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
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Tuleap. If not, see <http://www.gnu.org/licenses/>.
 */

namespace Tuleap\Project\Admin\Permission;

use HTTPRequest;
use Project;
use ProjectUGroup;
use Tuleap\User\UserGroup\NameTranslator;
use UGroupManager;

class PermissionPerGroupBuilder
{
    /**
     * @var UGroupManager
     */
    private $ugroup_manager;

    public function __construct(UGroupManager $ugroup_manager)
    {
        $this->ugroup_manager = $ugroup_manager;
    }

    public function buildUGroup(Project $project, HTTPRequest $request)
    {
        $selected_ugroup_id = $request->get('group');

        $ugroups = array();
        $this->addDynamicUgroups($project, $ugroups);
        $this->addStaticUgroups($project, $ugroups);

        $this->preselectGroup($ugroups, $selected_ugroup_id);

        return $ugroups;
    }

    private function addDynamicUgroups(Project $project, array &$ugroups)
    {
        $ugroups[] = array(
            'id'   => ProjectUGroup::PROJECT_MEMBERS,
            'name' => NameTranslator::getUserGroupDisplayName(NameTranslator::PROJECT_MEMBERS),
        );
        $ugroups[] = array(
            'id'   => ProjectUGroup::PROJECT_ADMIN,
            'name' => NameTranslator::getUserGroupDisplayName(NameTranslator::PROJECT_ADMINS),
        );

        $this->addWiki($project, $ugroups);
        $this->addForum($project, $ugroups);
        $this->addNews($project, $ugroups);
    }

    private function getUGroupEntry(ProjectUGroup $ugroup)
    {
        return array(
            'id'   => $ugroup->getId(),
            'name' => NameTranslator::getUserGroupDisplayName($ugroup->getName()),
        );
    }

    private function addWiki(Project $project, array &$ugroups)
    {
        if ($project->usesWiki()) {
            $ugroups[] = $this->getUGroupEntry(
                $this->ugroup_manager->getUGroup($project, ProjectUGroup::WIKI_ADMIN)
            );
        }
    }

    private function addForum(Project $project, array &$ugroups)
    {
        if ($project->usesForum()) {
            $ugroups[] = $this->getUGroupEntry(
                $this->ugroup_manager->getUGroup($project, ProjectUGroup::FORUM_ADMIN)
            );
        }
    }

    private function addNews(Project $project, array &$ugroups)
    {
        if ($project->usesNews()) {
            $ugroups[] = $this->getUGroupEntry(
                $this->ugroup_manager->getUGroup($project, ProjectUGroup::NEWS_WRITER)
            );
            $ugroups[] = $this->getUGroupEntry(
                $this->ugroup_manager->getUGroup($project, ProjectUGroup::NEWS_ADMIN)
            );
        }
    }

    private function addStaticUgroups(Project $project, array &$ugroups)
    {
        foreach ($this->ugroup_manager->getStaticUGroups($project) as $ugroup) {
            $ugroups[] = $this->getUGroupEntry($ugroup);
        }
    }

    private function preselectGroup(array &$ugroups, $selected_ugroup_id)
    {
        foreach ($ugroups as $key => $ugroup) {
            $ugroups[$key]['is_selected'] = (int) $selected_ugroup_id === (int) $ugroup['id'];
        }
    }
}
