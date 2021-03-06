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
 *
 */

namespace Tuleap\CreateTestEnv;

use PHPUnit\Framework\TestCase;

require_once __DIR__.'/../../include/create_test_envPlugin.class.php';

class CreateTestUserTest extends TestCase
{
    /**
     * @dataProvider userNameProvider
     */
    public function testUserNameIsValid($submitted_string, $expected_result)
    {
        $create = new CreateTestUser('', '', '');
        $this->assertEquals($expected_result, $create->getLoginFromString($submitted_string));
    }

    public function userNameProvider()
    {
        return [
            [ 'Joperesr++`*%ù', 'joperesr' ],
            [ ' stuff it', 'stuff-it'],
            [ 'stuff it ', 'stuff-it'],
            [ 'kldfgkdflsg dsfgjdsflgkdfsgukl dfsgfdfsgdfsg', 'kldfgkdflsg-dsfgjdsflgkdfsgu']
        ];
    }
}
