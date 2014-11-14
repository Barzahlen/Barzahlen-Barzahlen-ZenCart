<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * @copyright   Copyright (c) 2014 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Mathias Hertlein
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once('src/admin/includes/modules/barzahlen/BarzahlenVersionCheck.php');

class BarzahlenVersionCheckTest extends PHPUnit_Framework_TestCase
{
    private $shopId;
    private $paymentKey;
    private $shopSystemVersion;

    private $db;
    private $request;

    public function setUp()
    {
        $this->shopId = MODULE_PAYMENT_BARZAHLEN_SHOPID;
        $this->shopSystemVersion = "1.0.6";
        $this->db = new db_handler;
        $this->request = $this->getMock("BarzahlenPluginCheckRequest", array("sendRequest", "getPluginVersion", "getPluginUrl"), array(), "", false);
    }

    public function createVersionCheck()
    {
        return new BarzahlenVersionCheck($this->request);
    }

    public function testCheckedInLastWeekReturnsTrueWhenLastCheckIsNotLongerAgoThanAWeek()
    {
        global $db;

        $now = new DateTime();
        $sql_data = array(
            'configuration_key' => 'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK',
            'configuration_value' => $now->format('Y-m-d H:i:s'),
            'configuration_group_id' => 6,
            'date_added' => 'now()'
        );
        $db->perform(TABLE_CONFIGURATION, $sql_data);

        $versionCheck = $this->createVersionCheck();
        $this->assertTrue($versionCheck->isCheckedInLastWeek());
    }

    public function testCheckedInLastWeekReturnsFalseWhenLastCheckIsLongerAgoThanAWeek()
    {
        global $db;

        $sql_data = array(
            'configuration_key' => 'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK',
            'configuration_value' => '2013-01-01 00:00:00',
            'configuration_group_id' => 6,
            'date_added' => 'now()'
        );
        $db->perform(TABLE_CONFIGURATION, $sql_data);

        $versionCheck = $this->createVersionCheck();
        $this->assertFalse($versionCheck->isCheckedInLastWeek());
    }

    public function testCheckedInLastWeekReturnsFalseWhenNeverChecked()
    {
        global $db;

        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key = 'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK'");

        $versionCheck = $this->createVersionCheck();
        $this->assertFalse($versionCheck->isCheckedInLastWeek());
    }

    public function testRequestWillBeSendToBarzahlenApi()
    {
        $this->request
            ->expects($this->once())
            ->method('sendRequest');

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
        $expectedUrl = "https://integration.barzahlen.de";

        $this->request
            ->expects($this->once())
            ->method('getPluginVersion')
            ->will($this->returnValue($expectedVersion));

        $this->request
            ->expects($this->once())
            ->method('getPluginUrl')
            ->will($this->returnValue($expectedUrl));

        $versionCheck = $this->createVersionCheck();
        $versionCheck->check($this->shopId, $this->paymentKey, $this->shopSystemVersion);
        $newVersion = $versionCheck->getNewestVersion();
        $this->assertEquals($expectedVersion, $newVersion);
        $newVersionUrl = $versionCheck->getNewestVersionUrl();
        $this->assertEquals($expectedUrl, $newVersionUrl);
    }

    public function tearDown()
    {
        unset($this->db);
        unset($this->request);
    }
}
