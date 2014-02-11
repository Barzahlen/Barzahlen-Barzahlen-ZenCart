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

require_once('src/includes/modules/payment/barzahlen/model.notification.php');

class ModelNotificationTest extends PHPUnit_Framework_TestCase {

  /**
   * Set everything that is needed for the testing up.
   */
  public function setUp() {
    $this->object = new BZ_Notification;
  }

  /**
   * Check notification helper with valid transaction array.
   */
  public function testValidTransactionInput() {

    $_GET = array('state' => 'paid',
                  'transaction_id' => '600',
                  'shop_id' => '32',
                  'customer_email' => 'mail@domain.tld',
                  'amount' => '432.78',
                  'currency' => 'EUR',
                  'order_id' => '100003456',
                  'custom_var_0' => 'cv0',
                  'custom_var_1' => 'cv1',
                  'custom_var_2' => 'cv2',
                  'hash' => '704f06c85e9d12a62cc69084afc3cbcc3de5878ec595e0b0bc911552f278043d2441478072a217de954e3567d3b1765366378274c105eb5b2f7fabd6e58e620b');
    $this->assertTrue($this->object->checkReceivedData($_GET));
  }

  /**
   * Check notification helper with invalid transaction array. (order_id is not numeric)
   */
  public function testValidTransactionInput_NonNumeric() {

    $_GET = array('state' => 'paid',
                  'transaction_id' => '600',
                  'shop_id' => '32',
                  'customer_email' => 'mail@domain.tld',
                  'amount' => '432.78',
                  'currency' => 'EUR',
                  'order_id' => '100<tag>3456',
                  'custom_var_0' => 'cv0',
                  'custom_var_1' => 'cv1',
                  'custom_var_2' => 'cv2',
                  'hash' => '704f06c85e9d12a62cc69084afc3cbcc3de5878ec595e0b0bc911552f278043d2441478072a217de954e3567d3b1765366378274c105eb5b2f7fabd6e58e620b');
    $this->assertFalse($this->object->checkReceivedData($_GET));
  }

  /**
   * Check notification helper with invalid transaction array. (transaction_id array key is wrong)
   */
  public function testInvalidTransactionInput_ArrayKey() {

    $_GET = array('state' => 'paid',
                  'transactionid' => '23',
                  'shop_id' => '2',
                  'customer_email' => 'foo@bar.de',
                  'amount' => '34.23',
                  'currency' => 'EUR',
                  'order_id' => '222',
                  'custom_var_0' => '',
                  'custom_var_1' => '',
                  'custom_var_2' => '',
                  'hash' => 'j34b242jb');
    $this->assertFalse($this->object->checkReceivedData($_GET));
  }

  /**
   * Check notification helper with incomplete transaction array. (customer_email is missing)
   */
  public function testIncompleteTransactionInput() {

    $_GET = array('state' => 'paid',
                  'transaction_id' => '600',
                  'shop_id' => '32',
                  'amount' => '432.78',
                  'currency' => 'EUR',
                  'order_id' => '100003456',
                  'custom_var_0' => 'cv0',
                  'custom_var_1' => 'cv1',
                  'custom_var_2' => 'cv2',
                  'hash' => '956fe41ae88723de6a98c4e399c7c7f9874ea7df08b830b6dd1285df97373b605212c7f043794b5c08114d082c7d02550c2cff9ec10bd4b2295805cb848141ae');
    $this->assertFalse($this->object->checkReceivedData($_GET));
  }

  /**
   * Check notification helper with empty transaction array.
   */
  public function testCompleteButEmptyTransactionInput() {

    $_GET = array('state' => '',
                  'transaction_id' => '',
                  'shop_id' => '',
                  'customer_email' => '',
                  'amount' => '',
                  'currency' => '',
                  'order_id' => '',
                  'custom_var_0' => '',
                  'custom_var_1' => '',
                  'custom_var_2' => '',
                  'hash' => '');
    $this->assertFalse($this->object->checkReceivedData($_GET));
  }

  /**
   * Unset everything before the next test.
   */
  protected function tearDown() {

    unset($this->object);
  }
}
?>