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

require_once('src/admin/includes/modules/barzahlen/BarzahlenVersionCheck.php');

class BarzahlenVersionCheckTest extends PHPUnit_Framework_TestCase
{
    private $shopId;
    private $paymentKey;
    private $shopSystemVersion;

    private $request;
    private $configRepository;

    public function setUp()
    {
        $this->shopId = 1337;
        $this->paymentKey = "asdfdsfafd";
        $this->shopSystemVersion = "1.0";

        $this->request = $this->getMock("BarzahlenPluginCheckRequest", array("sendRequest", "getPluginVersion"), array(), "", false);
        $this->configRepository = $this->getMock("BarzahlenConfigRepository", array("getLastUpdateDate", "insertLastUpdateDate", "updateLastUpdateDate"), array(), "", false);
    }

    public function createVersionCheck()
    {
        return new BarzahlenVersionCheck($this->request, $this->configRepository);
    }

    public function testCheckedInLastWeekReturnsTrueWhenLastCheckIsLongerAgoThanAWeek()
    {
        $lastUpdateDate = strtotime("2013-01-01 00:00:00");
        $now = strtotime("2013-01-06 00:00:00");

        $this->configRepository
            ->expects($this->any())
            ->method('getLastUpdateDate')
            ->will($this->returnValue($lastUpdateDate));

        $versionCheck = $this->createVersionCheck();
        $isChecked = $versionCheck->isCheckedInLastWeek($now);

        $this->assertTrue($isChecked);
    }

    public function testCheckedInLastWeekReturnsFalseWhenLastCheckIsNotLongerAgoThanAWeek()
    {
        $lastUpdateDate = strtotime("2013-01-01 00:00:00");
        $now = strtotime("2013-01-08 00:00:00");

        $this->configRepository
            ->expects($this->any())
            ->method('getLastUpdateDate')
            ->will($this->returnValue($lastUpdateDate));

        $versionCheck = $this->createVersionCheck();
        $isChecked = $versionCheck->isCheckedInLastWeek($now);

        $this->assertFalse($isChecked);
    }

    public function testCheckedInLastWeekReturnsFalseWhenRepositoryReturnsFalse()
    {
        $now = strtotime("2013-01-08 00:00:00");

        $this->configRepository
            ->expects($this->any())
            ->method('getLastUpdateDate')
            ->will($this->returnValue(false));

        $versionCheck = $this->createVersionCheck();
        $isChecked = $versionCheck->isCheckedInLastWeek($now);

        $this->assertFalse($isChecked);
    }

    public function testRequestWillBeSendToBarzahlenApi()
    {
        $this->request
            ->expects($this->once())
            ->method('sendRequest');

        $versionCheck = $this->createVersionCheck();
        $versionCheck->check($this->shopId, $this->paymentKey, $this->shopSystemVersion);
    }

    public function testLastCheckDateWillBeInsertedIfNotExists()
    {
        $this->configRepository
            ->expects($this->any())
            ->method('getLastUpdateDate')
            ->will($this->returnValue(false));

        $this->configRepository
            ->expects($this->once())
            ->method('insertLastUpdateDate');

        $versionCheck = $this->createVersionCheck();
        $versionCheck->check($this->shopId, $this->paymentKey, $this->shopSystemVersion);
    }

    public function testLastCheckDateWillBeUpdatedIfExists()
    {
        $this->configRepository
            ->expects($this->any())
            ->method('getLastUpdateDate')
            ->will($this->returnValue(time()));

        $this->configRepository
            ->expects($this->once())
            ->method('updateLastUpdateDate');

        $versionCheck = $this->createVersionCheck();
        $versionCheck->check($this->shopId, $this->paymentKey, $this->shopSystemVersion);
    }

    public function testIsNewVersionAvailableReturnsTrueIfNewVersionIsAvailable()
    {
        $this->request
            ->expects($this->any())
            ->method('getPluginVersion')
            ->will($this->returnValue(BarzahlenVersionCheck::PLUGIN_VERSION . ".1"));

        $versionCheck = $this->createVersionCheck();
        $versionCheck->check($this->shopId, $this->paymentKey, $this->shopSystemVersion);
        $isNewVersionAvailable = $versionCheck->isNewVersionAvailable();
        $this->assertTrue($isNewVersionAvailable);
    }

    public function testIsNewVersionAvailableReturnsFalseIfNoNewVersionIsAvailable()
    {
        $this->request
            ->expects($this->any())
            ->method('getPluginVersion')
            ->will($this->returnValue(BarzahlenVersionCheck::PLUGIN_VERSION));

        $versionCheck = $this->createVersionCheck();
        $versionCheck->check($this->shopId, $this->paymentKey, $this->shopSystemVersion);
        $isNewVersionAvailable = $versionCheck->isNewVersionAvailable();
        $this->assertFalse($isNewVersionAvailable);
    }

    public function testGetNewestVersionReturnsVersionFromRequest()
    {
        $expectedVersion = "999.2.3.4.5.";

        $this->request
            ->expects($this->any())
            ->method('getPluginVersion')
            ->will($this->returnValue($expectedVersion));

        $versionCheck = $this->createVersionCheck();
        $versionCheck->check($this->shopId, $this->paymentKey, $this->shopSystemVersion);
        $newVersion = $versionCheck->getNewestVersion();
        $this->assertEquals($expectedVersion, $newVersion);
    }
}
