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

namespace Tuleap\AgileDashboard\BreadCrumbDropdown;

use Project;
use Tuleap\Layout\BreadCrumbDropdown\BreadCrumbItem;

class VirtualTopMilestoneCrumbBuilder
{
    /**
     * @param Project $project
     * @param string $plugin_path
     * @return BreadCrumbItem
     */
    public function build(Project $project, $plugin_path)
    {
        $url_top_parameters = array(
            'action'   => 'show-top',
            'pane'     => 'topplanning-v2',
            'group_id' => (int) $project->getGroupId()
        );
        return new BreadCrumbItem(
            $GLOBALS['Language']->getText('plugin_agiledashboard', 'top_planning_link'),
            $plugin_path .'/?'. http_build_query($url_top_parameters)
        );
    }
}