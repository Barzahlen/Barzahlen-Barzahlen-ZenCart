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
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once('src/includes/modules/payment/barzahlen.php');

class ModuleBarzahlenTest extends PHPUnit_Framework_TestCase {

  /**
   * Set everything that is needed for the testing up.
   */
  public function setUp() {

    $this->db = new db_handler;
    $this->object = new barzahlen;
  }

  /**
   * Tests payment settings.
   */
  public function testPaymentSettings() {

    $this->assertEquals('https://api.barzahlen.de/v1/transactions/', barzahlen::APIDOMAIN);
    $this->assertEquals('https://api-sandbox.barzahlen.de/v1/transactions/', barzahlen::APIDOMAINSANDBOX);
    $this->assertEquals(';', barzahlen::HASHSEPARATOR);
    $this->assertEquals('sha512', barzahlen::HASHALGORITHM);
  }

  /**
   * Tests non-used functions.
   */
  public function testNonUsedFunctions() {
    $this->assertFalse($this->object->update_status());
    $this->assertFalse($this->object->javascript_validation());
    $this->assertFalse($this->object->pre_confirmation_check());
    $this->assertFalse($this->object->confirmation());
    $this->assertFalse($this->object->process_button());
    $this->assertFalse($this->object->output_error());
    $this->assertFalse($this->object->before_process());
    $this->assertFalse($this->object->after_process());
  }

  /**
   * Tests install / remove methods.
   */
  public function testInstallAndRemove() {

    mysql_query("ALTER TABLE `".TABLE_ORDERS."` DROP `barzahlen_transaction_id`");
    mysql_query("ALTER TABLE `".TABLE_ORDERS."` DROP `barzahlen_transaction_state`");

    $this->object->install();
    $this->object = new barzahlen;
    $this->assertEquals('1', $this->object->check());
    $query = mysql_query("SELECT * FROM ". TABLE_CONFIGURATION);
    $this->assertEquals(12, mysql_num_rows($query));

    $this->object->remove();
    $this->object = new barzahlen;
    $this->assertEquals('0', $this->object->check());
    $query = mysql_query("SELECT * FROM ". TABLE_CONFIGURATION);
    $this->assertEquals(0, mysql_num_rows($query));
  }

  /**
   * Tests payment method selection.
   */
  public function testSelection() {

    $image = zen_image('http://cdn.barzahlen.de/images/barzahlen_logo.png');
    $title = MODULE_PAYMENT_BARZAHLEN_TEXT_TITLE;
    $description = MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_DESCRIPTION;

    if(MODULE_PAYMENT_BARZAHLEN_SANDBOX == 'True') {
      $title .= ' [SANDBOX]';
      $description .= MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_SANDBOX;
    }

    $description .= MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_PARTNER;

    for($i = 1; $i <= 10; $i++) {
      $count = str_pad($i,2,"0",STR_PAD_LEFT);
      $description .= '<img src="http://cdn.barzahlen.de/images/barzahlen_partner_'.$count.'.png" alt="" style="vertical-align: middle; height: 25px;" />';
    }

    $expected = array('id' => 'barzahlen',
                      'module' => $title,
                      'fields' => array(array('field' => str_replace('{{image}}', $image, $description))));

    $this->assertEquals($expected, $this->object->selection());
  }

  /**
   * Tests payment method selection with an amount higher than 1000.
   */
  public function testSelectionWithTooHighAmount() {
    global $order;
    $order->info['total'] = 1000;
    $this->assertFalse($this->object->selection());
  }

  /**
   * Tests payment action with a valid xml answer.
   */
  public function testPaymentActionWithVaildXml() {

    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <response>
              <transaction-id>227840174</transaction-id>
              <payment-slip-link>https://cdn.barzahlen.de/slip/227840174/c91dc292bdb8f0ba1a83c738119ef13e652a43b8a8f261cf93d3bfbf233d7da2.pdf</payment-slip-link>
              <expiration-notice>Der Zahlschein ist 10 Tage gueltig.</expiration-notice>
              <infotext-1><![CDATA[Text mit einem <a href="https://www.barzahlen.de" target="_blank">Link</a>]]></infotext-1>
              <infotext-2><![CDATA[Text mit einem <a href="https://www.barzahlen.de" target="_blank">Link</a>]]></infotext-2>
              <result>0</result>
              <hash>dc5e9ab111eb8ba3bbef491fd61b6e3a943a0e62a4b34d0d8642e90be432b6afe93f7c3dd62117d0b260c3cb912b0948b50c87a3f3b9b8560a0d13029a0fc1c3</hash>
            </response>';

    $this->object = $this->getMock('barzahlen', array('_sendTransArray'));
    $this->object->expects($this->any())
                 ->method('_sendTransArray')
                 ->will($this->returnValue($xml));

    $this->object->after_order_create(5);
    $this->assertTrue($_SESSION['payment_method_messages'] != '');
  }

  /**
   * Tests payment action with error and valid xml answer.
   */
  public function testPaymentActionWithErrorAndVaildXml() {

    $xml1 = '<?xml version="1.0" encoding="UTF-8"?>
             <response>
               <result>10</result>
               <error-message>shop not found</error-message>
             </response>';

    $xml2 = '<?xml version="1.0" encoding="UTF-8"?>
             <response>
               <transaction-id>227840174</transaction-id>
               <payment-slip-link>https://cdn.barzahlen.de/slip/227840174/c91dc292bdb8f0ba1a83c738119ef13e652a43b8a8f261cf93d3bfbf233d7da2.pdf</payment-slip-link>
               <expiration-notice>Der Zahlschein ist 10 Tage gueltig.</expiration-notice>
               <infotext-1><![CDATA[Text mit einem <a href="https://www.barzahlen.de" target="_blank">Link</a>]]></infotext-1>
               <infotext-2><![CDATA[Text mit einem <a href="https://www.barzahlen.de" target="_blank">Link</a>]]></infotext-2>
               <result>0</result>
               <hash>dc5e9ab111eb8ba3bbef491fd61b6e3a943a0e62a4b34d0d8642e90be432b6afe93f7c3dd62117d0b260c3cb912b0948b50c87a3f3b9b8560a0d13029a0fc1c3</hash>
             </response>';

    $this->object = $this->getMock('barzahlen', array('_sendTransArray'));
    $this->object->expects($this->any())
                 ->method('_sendTransArray')
                 ->will($this->onConsecutiveCalls($xml1, $xml2));

    $this->object->after_order_create(5);
    $this->assertTrue($_SESSION['payment_method_messages'] != '');
  }

  /**
   * Tests payment action with invalid hash.
   */
  public function testPaymentActionWithInvalidHash() {

    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <response>
              <transaction-id>227840174</transaction-id>
              <payment-slip-link>https://cdn.barzahlen.de/slip/227840174/c91dc292bdb8f0ba1a83c738119ef13e652a43b8a8f261cf93d3bfbf233d7da2.pdf</payment-slip-link>
              <expiration-notice>Der Zahlschein ist 10 Tage gueltig.</expiration-notice>
              <infotext-1><![CDATA[Text mit einem <a href="https://www.barzahlen.de" target="_blank">Link</a>]]></infotext-1>
              <infotext-2><![CDATA[Text mit einem <a href="https://www.barzahlen.de" target="_blank">Link</a>]]></infotext-2>
              <result>0</result>
              <hash>dc5e9ab111eb8ba3bbef491fd61b6e3a943a0e62a4b34d0d8642e90be432b6afe93f7c3dd62117d0b260c3cb912b0948b50c87a3f3b9b8560a0d13029a0fc1c4</hash>
            </response>';

    $this->object = $this->getMock('barzahlen', array('_sendTransArray'));
    $this->object->expects($this->any())
                 ->method('_sendTransArray')
                 ->will($this->returnValue($xml));

    $this->object->after_order_create(5);
    $this->assertTrue(!isset($_SESSION['payment_method_messages']));

    $this->object->get_error();
  }

  /**
   * Tests xml parsing with corrupt xml.
   */
  public function testGetXmlResponseWithCorruptXml() {

    $xml = '<?xml version="1.0" encoding="UTF-8"?>
            <response>
              <transactionid>227840174</transaction-id>
              <expiration-notice>Der Zahlschein ist 10 Tage gueltig.</expiration-notice>
              <result>0</result>
            </response>';

    $this->assertEquals(null, $this->object->_getResponseData('create', $xml));
  }

  /**
   * Unset everything before the next test.
   */
  public function tearDown() {

    unset($this->db);
    unset($this->object);
    if(isset($_SESSION['payment_method_messages'])) {
      unset($_SESSION['payment_method_messages']);
    }
  }
}

?>