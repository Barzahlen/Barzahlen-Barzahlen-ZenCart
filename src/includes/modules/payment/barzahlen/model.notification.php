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

class BZ_Notification {

  var $notficationData = array('state','transaction_id','shop_id','customer_email','amount',
                               'currency','order_id','custom_var_0','custom_var_1',
                               'custom_var_2','hash'); //!< all necessary attributes for a valid notification

  var $intData = array('transaction_id','order_id'); //!< numeric values for database queries

  /**
   * Checks the received data step by step.
   *
   * @param array $receivedGet received get array
   * @return FALSE if an error occurred
   * @return array with received information which where validated
   */
  function checkReceivedData(array $receivedGet) {

    if(!$this->checkExistenceForAllAttributes($receivedGet) || !$this->checkNumericData($receivedGet)) {
      return false;
    }

    if(!preg_match('/^(1000(\.00?)?|\d{1,3}(\.\d\d?)?)$/', $receivedGet['amount'])) {
        $this->_bzLog('model/notification: Amount is no valid value - ' . serialize($receivedGet));
      return false;
    }

    return true;
  }

  /**
   * Checks that all numeric attributes which are used for database queries are clean.
   *
   * @param array $receivedGet received notificiation array
   * @return FALSE if at least one attribute is not numeric
   * @return TRUE if all attributes are numeric
   */
  function checkNumericData(array $receivedGet) {

    foreach($this->intData as $attribute) {
      if(!preg_match('/^\d+$/', $receivedGet[$attribute]) && array_key_exists($attribute, $receivedGet)) {
        $this->_bzLog('model/notification: At least the following attribute is no numeric value: ' .
                      $attribute . ' - ' . serialize($receivedGet));
        return false;
      }
    }
    return true;
  }

  /**
   * Checks that all necessary attributes are set in the array.
   *
   * @param array $expected the expected values
   * @param array $testArray array that should be tested
   * @return FALSE if at least one attribute is missing
   * @return TRUE if all attributes are set
   */
  function checkExistenceForAllAttributes(array $receivedGet) {

    foreach($this->notficationData as $attribute) {
      if(!array_key_exists($attribute, $receivedGet)) {
        $this->_bzLog('model/notification: At least the following attribute is missing: ' .
                      $attribute . ' - ' . serialize($receivedGet));
        return false;
      }
    }
    return true;
  }

  /**
   * Logs errors into Barzahlen log file.
   *
   * @param string $message error message
   */
  function _bzLog($message) {

    $time = date("[Y-m-d H:i:s] ");
    $logFile = DIR_WS_MODULES . 'payment/barzahlen/barzahlen.log';

    error_log($time . $message . "\r", 3, $logFile);
  }
}
?>