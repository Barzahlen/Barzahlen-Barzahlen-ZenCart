<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * @copyright   Copyright (c) 2014 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once 'loader.php';

class Barzahlen_IPN
{
    /**
     * array for the received and checked data
     *
     * @var array
     */
    var $receivedData = array();

    /**
     * Main function.
     *
     * @param array $receivedData
     * @return bool
     */
    function callback($receivedData)
    {
        $this->receivedData = $receivedData;

        if(!$this->validateParameters()) {
            return false;
        }

        return $this->updateDatabase();
    }

    /**
     * Checks received data and validates hash.
     *
     * @param string $receivedData received data
     * @return boolean
     */
    function validateParameters()
    {
        $notification = new Barzahlen_Notification(
            MODULE_PAYMENT_BARZAHLEN_SHOPID,
            MODULE_PAYMENT_BARZAHLEN_NOTIFICATIONKEY,
            $this->receivedData
        );

        try {
            $notification->validate();
        } catch (Exception $e) {
            $this->bzLog('barzahlen/ipn: ' . $e);
            return false;
        }

        return $notification->isValid();
    }

    /**
     * Parent function to update the database with all information.
     *
     * @return boolean
     */
    function updateDatabase()
    {
        if(!$this->checkDatabase('pending')) {
            return false;
        }

        switch ($this->receivedData['state']) {
            case 'paid':
                return $this->setOrderPaid();
            case 'expired':
                return $this->setOrderExpired();
            default:
                $this->bzLog('barzahlen/ipn: Not able to handle state - ' . serialize($this->receivedData));
                return false;
        }
    }

    /**
     * Checks received data against database for the order.
     *
     * @param string $state
     * @return boolean
     */
    function checkDatabase($state)
    {
        global $db;

        $query = $db->Execute("SELECT *
                                 FROM " . TABLE_ORDERS . "
                                WHERE orders_id = '" . $this->receivedData['order_id'] . "'
                                  AND barzahlen_transaction_id = '" . $this->receivedData['transaction_id'] . "'
                                  AND barzahlen_transaction_state = '" . $state . "'");

        if ($query->RecordCount() != 1) {
            $this->bzLog('barzahlen/ipn: No ' . $state . ' order found in database - ' . serialize($this->receivedData));
            return false;
        }

        return true;
    }

    /**
     * Sets order and transaction to paid. Adds an entry to order status history table.
     *
     * @return boolean
     */
    function setOrderPaid()
    {
        $this->updateOrder(MODULE_PAYMENT_BARZAHLEN_PAID_STATUS);
        $this->addOrderHistory(MODULE_PAYMENT_BARZAHLEN_PAID_STATUS, MODULE_PAYMENT_BARZAHLEN_TEXT_TRANSACTION_PAID);

        return $this->checkDatabase('paid');
    }

    /**
     * Cancels the order and sets the transaction to expired. Adds an entry to order status history table.
     *
     * @return boolean
     */
    function setOrderExpired()
    {
        $this->updateOrder(MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS);
        $this->addOrderHistory(MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS, MODULE_PAYMENT_BARZAHLEN_TEXT_TRANSACTION_EXPIRED);

        return $this->checkDatabase('expired');
    }

    /**
     * Update order in database.
     *
     * @param integer $statusId
     */
    public function updateOrder($statusId)
    {
        global $db;

        $db->Execute("UPDATE " . TABLE_ORDERS . "
                         SET orders_status = '" . $statusId . "',
                             barzahlen_transaction_state = '" . $this->receivedData['state'] . "'
                       WHERE orders_id = '" . $this->receivedData['order_id'] . "'");
    }

    /**
     * Add history comment to database.
     *
     * @param integer $statusId
     * @param string $comment
     */
    public function addOrderHistory($statusId, $comment)
    {
        global $db;

        $db->Execute("INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . "
                      (orders_id, orders_status_id, date_added, customer_notified, comments)
                      VALUES
                      ('" . $this->receivedData['order_id'] . "', '" . $statusId . "', NOW(), '1', '" . $comment . "')");
    }

    /**
     * Logs errors into Barzahlen log file.
     *
     * @param string $message error message
     */
    function bzLog($message)
    {
        $time = date("[Y-m-d H:i:s] ");
        $logFile = DIR_FS_CATALOG . 'logs/barzahlen.log';

        error_log($time . $message . "\r\r", 3, $logFile);
    }
}
