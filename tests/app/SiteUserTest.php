<?php

/**
 * webtrees: online genealogy
 * Copyright (C) 2019 webtrees development team
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Fisharebest\Webtrees;

use Fisharebest\Webtrees\Contracts\UserInterface;

/**
 * Test the SiteUser class
 */
class SiteUserTest extends TestCase
{
    protected static $uses_database = true;

    /**
     * @covers \Fisharebest\Webtrees\SiteUser::id
     * @covers \Fisharebest\Webtrees\SiteUser::email
     * @covers \Fisharebest\Webtrees\SiteUser::realName
     * @covers \Fisharebest\Webtrees\SiteUser::userName
     * @return void
     */
    public function testConstructor(): void
    {
        $user = new SiteUser();

        $this->assertInstanceOf(UserInterface::class, $user);
        $this->assertSame(0, $user->id());
        $this->assertSame('', $user->email());
        $this->assertSame('webtrees', $user->realName());
        $this->assertSame('', $user->userName());
    }

    /**
     * @covers \Fisharebest\Webtrees\SiteUser::getPreference
     * @covers \Fisharebest\Webtrees\SiteUser::setPreference
     * @return void
     */
    public function testPreferences(): void
    {
        $user = new SiteUser();

        $this->assertSame('', $user->getPreference('foo'));
        $this->assertSame('', $user->getPreference('foo', ''));
        $this->assertSame('bar', $user->getPreference('foo', 'bar'));

        // Site users do not have preferences
        $user->setPreference('foo', 'bar');

        $this->assertSame('', $user->getPreference('foo'));
    }
}