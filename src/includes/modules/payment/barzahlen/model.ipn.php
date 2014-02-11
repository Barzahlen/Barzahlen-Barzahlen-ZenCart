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

require_once('model.notification.php');

class BZ_Ipn {

  var $receivedData = array(); //!< array for the received and checked data

  const STATE_PENDING = 'pending';
  const STATE_PAID = 'paid';
  const STATE_EXPIRED = 'expired';

  /**
   * Checks received data and validates hash.
   *
   * @param string $uncleanData received data
   * @return TRUE if received get array is valid and hash could be confirmed
   * @return FALSE if an error occurred
   */
  public function sendResponseHeader($receivedData) {

    $notification = new BZ_Notification;

    if(!$notification->checkReceivedData($receivedData) || !$this->confirmHash($receivedData)) {
      return false;
    }

    $this->receivedData = $receivedData;
    return true;
  }

  /**
   * Generates expected hash out of received data and compares it to received hash.
   *
   * @return TURE if the received hash is valid
   * @return FALSE if the received hash is invalid
   */
  function confirmHash(array $receivedData) {

    $hashArray = array();
    $hashArray[] = $receivedData['state'];
    $hashArray[] = $receivedData['transaction_id'];
    $hashArray[] = $receivedData['shop_id'];
    $hashArray[] = $receivedData['customer_email'];
    $hashArray[] = $receivedData['amount'];
    $hashArray[] = $receivedData['currency'];
    $hashArray[] = $receivedData['order_id'];
    $hashArray[] = $receivedData['custom_var_0'];
    $hashArray[] = $receivedData['custom_var_1'];
    $hashArray[] = $receivedData['custom_var_2'];
    $hashArray[] = MODULE_PAYMENT_BARZAHLEN_NOTIFICATIONKEY;

    if($receivedData['hash'] != hash('sha512', implode(';',$hashArray))) {
      $this->_bzLog('model/ipn: Hash not valid - ' . serialize($receivedData));
      return false;
    }

    return true;
  }

  /**
   * Parent function to update the database with all information.
   */
  function updateDatabase() {

    if($this->checkDatasets() && $this->canUpdateTransaction()) {
      switch ($this->receivedData['state']) {
        case 'paid':
          $this->setOrderPaid();
          break;
        case 'expired':
          $this->setOrderExpired();
          break;
        default:
          $this->_bzLog('model/ipn: Not able to handle state - ' . serialize($this->receivedData));
          break;
      }
    }
  }

  /**
   * Checks received data against datasets for order and order total.
   *
   * @return boolean (TRUE if all values are valid, FALSE if not)
   */
  function checkDatasets() {
    global $db;

    // check order
    $query = $db->Execute("SELECT * FROM ". TABLE_ORDERS ."
                           WHERE orders_id = '". $this->receivedData['order_id'] ."'
                             AND currency = '".$this->receivedData['currency']."'
                             AND barzahlen_transaction_id = '". $this->receivedData['transaction_id'] ."'");
    if($query->RecordCount() != 1) {
      $this->_bzLog('model/ipn: No corresponding order found in database - ' . serialize($this->receivedData));
      return false;
    }

    // check order total
    $query = $db->Execute("SELECT value FROM ". TABLE_ORDERS_TOTAL ."
                           WHERE orders_id = '". $this->receivedData['order_id'] ."'
                             AND class = 'ot_total'");
    if($query->fields['value'] != $this->receivedData['amount']) {
      $this->_bzLog('model/ipn: Order total and amount don\'t match - ' . serialize($this->receivedData));
      return false;
    }

    // check shop id
    if($this->receivedData['shop_id'] != MODULE_PAYMENT_BARZAHLEN_SHOPID) {
      $this->_bzLog('model/ipn: Shop Id doesn\'t match - ' . serialize($this->receivedData));
      return false;
    }

    return true;
  }

  /**
   * Checks that transaction can be updated by notification. (Only pending ones can.)
   *
   * @return boolean (TRUE if transaction is pending, FALSE if not)
   */
  function canUpdateTransaction() {
    global $db;

    $query = $db->Execute("SELECT barzahlen_transaction_state FROM ". TABLE_ORDERS ."
                           WHERE barzahlen_transaction_id = '". $this->receivedData['transaction_id'] ."'");

    if($query->fields['barzahlen_transaction_state'] != self::STATE_PENDING) {
      $this->_bzLog('model/ipn: Transaction for this order already paid / expired - ' . serialize($this->receivedData));
      return false;
    }

    return true;
  }

  /**
   * Sets order and transaction to paid. Adds an entry to order status history table.
   */
  function setOrderPaid() {
    global $db;

    $db->Execute("UPDATE ". TABLE_ORDERS ."
                  SET orders_status = '". MODULE_PAYMENT_BARZAHLEN_PAID_STATUS ."',
                      barzahlen_transaction_state = '".self::STATE_PAID."'
                  WHERE orders_id = '". $this->receivedData['order_id'] ."'");

    $db->Execute("INSERT INTO ". TABLE_ORDERS_STATUS_HISTORY ."
                  (orders_id, orders_status_id, date_added, customer_notified, comments)
                  VALUES
                  ('". $this->receivedData['order_id'] ."', '". MODULE_PAYMENT_BARZAHLEN_PAID_STATUS ."',
                  now(), 1, '". MODULE_PAYMENT_BARZAHLEN_TEXT_TRANSACTION_PAID ."')");
  }

  /**
   * Cancels the order and sets the transaction to expired. Adds an entry to order status history table.
   */
  function setOrderExpired() {
    global $db;

    $db->Execute("UPDATE ". TABLE_ORDERS ."
                  SET orders_status = '". MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS ."',
                      barzahlen_transaction_state = '".self::STATE_EXPIRED."'
                  WHERE orders_id = '". $this->receivedData['order_id'] ."'");

    $db->Execute("INSERT INTO ". TABLE_ORDERS_STATUS_HISTORY ."
                  (orders_id, orders_status_id, date_added, customer_notified, comments)
                  VALUES
                  ('". $this->receivedData['order_id'] ."', '". MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS ."',
                  now(), 1, '". MODULE_PAYMENT_BARZAHLEN_TEXT_TRANSACTION_EXPIRED ."')");
  }

  /**
   * Logs errors into Barzahlen log file.
   *
   * @param string $message error message
   */
  function _bzLog($message) {

    $time = date("[Y-m-d H:i:s] ");
    $logFile = DIR_WS_MODULES . 'payment/barzahlen/barzahlen.log';

    error_log($time . $message . "\r\r", 3, $logFile);
  }
}
?>