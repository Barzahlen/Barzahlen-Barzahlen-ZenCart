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

require_once('src/admin/includes/modules/barzahlen/BarzahlenConfigRepository.php');

class BarzahlenConfigRepositoryTest extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->db = new db_handler();
        mysql_query("DELETE FROM configuration WHERE configuration_key = 'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK'");
    }

    private function getRepository()
    {
        global $db;
        return new BarzahlenConfigRepository($db);
    }

    public function testGetLastUpdateDateReturnsFalseOnConfigEntry()
    {
        $repository = $this->getRepository();
        $lastUpdateDate = $repository->getLastUpdateDate();

        $this->assertEquals(false, $lastUpdateDate);
    }

    public function testGetLastUpdateDateReturnsConfigEntry()
    {
        $date = "2013-04-01 12:34:56";

        $sql = <<<SQL
INSERT INTO configuration (
    configuration_key,
    configuration_value,
    configuration_group_id,
    last_modified,
    date_added
)
VALUES (
    'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK',
    '$date',
    6,
    '$date',
    '$date'
)
SQL;
        mysql_query($sql);

        $repository = $this->getRepository();
        $lastUpdateDate = $repository->getLastUpdateDate();

        $this->assertEquals(strtotime($date), $lastUpdateDate);
    }

    public function testInsertLastUpdateDateDontReturnsError()
    {
        $repository = $this->getRepository();
        $repository->insertLastUpdateDate();

        $error = mysql_error();

        $this->assertEquals("", $error);
    }

    public function testUpdateLastUpdateDateDontReturnsError()
    {
        $repository = $this->getRepository();
        $repository->updateLastUpdateDate();

        $error = mysql_error();

        $this->assertEquals("", $error);
    }
}
