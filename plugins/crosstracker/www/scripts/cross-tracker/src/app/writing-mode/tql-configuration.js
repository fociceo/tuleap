/**
 * Copyright (c) 2017 - 2018, Enalean. All rights reserved
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

import {
    buildModeDefinition
} from 'plugin-tracker-TQL/configuration.js';

const TQL_cross_tracker_autocomplete_keywords = [
    'AND',
    'OR',
    'OPEN()',
    'NOW()',
    'BETWEEN()',
    '@title',
    '@description',
    '@status',
    '@submitted_on',
    '@last_update_date'
];

const cross_tracker_allowed_keywords    = {
    additional_keywords: ['@title', '@description', '@status', '@submitted_on', '@last_update_date']
};
const TQL_cross_tracker_mode_definition = buildModeDefinition(cross_tracker_allowed_keywords);

export {
    TQL_cross_tracker_autocomplete_keywords,
    TQL_cross_tracker_mode_definition
};
