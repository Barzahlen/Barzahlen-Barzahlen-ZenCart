<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * NOTICE OF LICENSE
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; version 2 of the License
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA 02111-1307 USA
 *
 * @copyright   Copyright (c) 2012 Zerebro Internet GmbH (http://www.barzahlen.de)
 * @author      Mathias Hertlein
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once('src/admin/includes/modules/barzahlen/BarzahlenHashCreate.php');

class BarzahlenHashCreateTest extends PHPUnit_Framework_TestCase
{
    public function testCreatedHashIsCorrect()
    {
        $expectedHash = "514d01564e29400d27886815747dc080a358029431ec9440e48d5b85e630d8fceb75daabe5624bb36e10ed72b33d8f25bac0fca9d69d2597fe7b8e12c418bc8a";

        $data = array(
            'test',
            123,
            'foo',
        );
        $key = "ed0164df332a9e3e5cc858a738439dbf";

        $hashCreate = new BarzahlenHashCreate();
        $hash = $hashCreate->getHash($data, $key);

        $this->assertEquals($expectedHash, $hash);
    }
}
