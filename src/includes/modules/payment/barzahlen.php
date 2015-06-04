<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * @copyright   Copyright (c) 2014 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once dirname(__FILE__) . '/barzahlen/loader.php';

class barzahlen
{
    /**
     * Constructor class, sets the settings.
     */
    function barzahlen()
    {
        $this->code = 'barzahlen';
        $this->version = '1.2.0';
        $this->title = MODULE_PAYMENT_BARZAHLEN_TEXT_TITLE;
        $this->description = '<div align="center">' . zen_image('https://cdn.barzahlen.de/images/barzahlen_logo.png', MODULE_PAYMENT_BARZAHLEN_TEXT_TITLE) . '</div><br>' . MODULE_PAYMENT_BARZAHLEN_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_BARZAHLEN_SORT_ORDER;
        $this->enabled = (MODULE_PAYMENT_BARZAHLEN_STATUS == 'True') ? true : false;
        $this->defaultCurrency = 'EUR';

        $this->logFile = DIR_FS_CATALOG . 'logs/barzahlen.log';
        $this->currencies = array('EUR');
        $this->countries = array('DE');

        //if ($this->check() && $this->checkLastAutoCancel()) {
          //  $this->autoCancel();
       // }
    }

    /**
     * Settings update. Not used in this module.
     *
     * @return boolean
     */
    function update_status()
    {
        return false;
    }

    /**
     * Javascript code. Not used in this module.
     *
     * @return boolean
     */
    function javascript_validation()
    {
        return false;
    }

    /**
     * Sets information for checkout payment selection page.
     *
     * @return array with payment module information
     */
    function selection()
    {
        global $order;

        if(!in_array($order->customer['country']['iso_code_2'], $this->countries)) {
            return false;
        }

        if(!in_array($order->info['currency'], $this->currencies)) {
            return false;
        }

        if (!preg_match('/^[0-9]{1,3}(\.[0-9][0-9]?)?$/', MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL)) {
            $this->bzLog('barzahlen/selection: Maximum order amount (' . MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL . ') is not valid. Should be between 0.00 and 999.99 Euros.');
            return false;
        }

        if ($order->info['total'] <= MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL) {
            $title = $this->title;

            $description = MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_DESCRIPTION . MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_PARTNER;
            for ($i = 1; $i <= 10; $i++) {
                $count = str_pad($i, 2, "0", STR_PAD_LEFT);
                $description .= '<img src="https://cdn.barzahlen.de/images/barzahlen_partner_' . $count . '.png" alt="" style="height: 1em; vertical-align: -0.1em;">';
            }
            $description .= '<script src="https://cdn.barzahlen.de/js/selection.js"></script>';

            return array('id' => $this->code, 'module' => $title, 'fields' => array(array('title' => '', 'field' => $description)));
        } else {
            return false;
        }
    }

    /**
     * Actions before confirmation. Not used in this module.
     *
     * @return boolean
     */
    function pre_confirmation_check()
    {
        return false;
    }

    /**
     * Payment method confirmation. Not used in this module.
     *
     * @return boolean
     */
    function confirmation()
    {
        return false;
    }

    /**
     * Module start via button. Not used in this module.
     *
     * @return boolean
     */
    function process_button()
    {
        return false;
    }

    /**
     * Before checkout process. Not used in this module
     */
    function before_process()
    {
        return false;
    }

    /**
     * Payment process after order creation.
     */
    function after_order_create($insert_id)
    {
        global $db, $order, $messageStack;

        $payment = new Barzahlen_Request_Payment(
            $order->customer['email_address'],
            $order->customer['street_address'],
            $order->customer['postcode'],
            $order->customer['city'],
            $order->customer['country']['iso_code_2'],
            $order->info['total'],
            $order->info['currency'],
            $insert_id
        );

        $api = $this->createApi();

        try {
            $api->handleRequest($payment);
        } catch (Exception $e) {
            $this->bzLog('barzahlen/payment: ' . $e);
        }

        if($payment->isValid()) {
            $_SESSION['payment_method_messages'] = $this->convertISO($payment->getInfotext1());

            $db->Execute("UPDATE " . TABLE_ORDERS . "
                             SET barzahlen_transaction_id = '" . $payment->getTransactionId() . "' ,
                                 barzahlen_transaction_state = 'pending',
                                 orders_status = '" . MODULE_PAYMENT_BARZAHLEN_NEW_STATUS . "'
                           WHERE orders_id = '" . $insert_id . "'");

            $db->Execute("INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . "
                          (orders_id, orders_status_id, date_added, customer_notified, comments)
                          VALUES
                          ('" . $insert_id . "', '" . MODULE_PAYMENT_BARZAHLEN_NEW_STATUS . "', NOW(), '1', '" . MODULE_PAYMENT_BARZAHLEN_TEXT_X_ATTEMPT_SUCCESS . "')");
        } else {
            $db->Execute("UPDATE " . TABLE_ORDERS . "
                             SET orders_status = '" . MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS . "'
                           WHERE orders_id = '" . $insert_id . "'");

            $db->Execute("INSERT INTO " . TABLE_ORDERS_STATUS_HISTORY . "
                          (orders_id, orders_status_id, date_added, customer_notified, comments)
                          VALUES
                          ('" . $insert_id . "', '" . MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS . "', NOW(), '1', '" . MODULE_PAYMENT_BARZAHLEN_TEXT_PAYMENT_ATTEMPT_FAILED . "')");

            $messageStack->add_session('checkout_payment', $this->convertISO(MODULE_PAYMENT_BARZAHLEN_TEXT_PAYMENT_ERROR), 'error');
            zen_redirect(zen_href_link(FILENAME_CHECKOUT_PAYMENT, '', 'SSL', true, false));
        }
    }

    /**
     * After checkout process. Not used in this module
     */
    function after_process()
    {
        return false;
    }

    /**
     * Extracts and returns error.
     *
     * @return array with error information
     */
    function get_error()
    {
        return false;
    }

    /**
     * Error output. Not used in this module.
     *
     * @return boolean
     */
    function output_error()
    {
        return false;
    }

    /**
     * Checks if Barzahlen payment module is installed.
     *
     * @return integer
     */
    function check()
    {
        global $db;

        if (!isset($this->_check)) {
            $check_query = $db->Execute("SELECT configuration_value
                                           FROM " . TABLE_CONFIGURATION . "
                                          WHERE configuration_key = 'MODULE_PAYMENT_BARZAHLEN_STATUS'");
            $this->_check = $check_query->RecordCount();
        }
        return $this->_check;
    }

    /**
     * Install sql queries.
     */
    function install()
    {
        global $db;

        if (!defined('MODULE_PAYMENT_BARZAHLEN_TEXT_TITLE')) {
            require_once('../' . DIR_WS_LANGUAGES . $_SESSION['language'] . '/modules/payment/barzahlen.php');
        }

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, date_added)
            VALUES
            ('" . MODULE_PAYMENT_BARZAHLEN_STATUS_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_STATUS', 'False', '" . MODULE_PAYMENT_BARZAHLEN_STATUS_DESC . "', '6', '1', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_SANDBOX_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_SANDBOX', 'True', '" . MODULE_PAYMENT_BARZAHLEN_SANDBOX_DESC . "', '6', '2', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_DEBUG_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_DEBUG', 'False', '" . MODULE_PAYMENT_BARZAHLEN_DEBUG_DESC . "', '6', '12', 'zen_cfg_select_option(array(\'True\', \'False\'), ', now())");

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, date_added)
            VALUES
            ('" . MODULE_PAYMENT_BARZAHLEN_ALLOWED_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_ALLOWED', 'DE', '" . MODULE_PAYMENT_BARZAHLEN_ALLOWED_DESC . "', '6', '0', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_SHOPID_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_SHOPID', '', '" . MODULE_PAYMENT_BARZAHLEN_SHOPID_DESC . "', '6', '3', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_PAYMENTKEY_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_PAYMENTKEY', '', '" . MODULE_PAYMENT_BARZAHLEN_PAYMENTKEY_DESC . "', '6', '4', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_NOTIFICATIONKEY_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_NOTIFICATIONKEY', '', '" . MODULE_PAYMENT_BARZAHLEN_NOTIFICATIONKEY_DESC . "', '6', '5', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL', '999.99', '" . MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL_DESC . "', '6', '6', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_SORT_ORDER_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_SORT_ORDER', '-1', '" . MODULE_PAYMENT_BARZAHLEN_SORT_ORDER_DESC . "', '6', '11', now())");

        $db->Execute(
            "INSERT INTO " . TABLE_CONFIGURATION . "
            (configuration_title, configuration_key, configuration_value, configuration_description, configuration_group_id, sort_order, set_function, use_function, date_added)
            VALUES
            ('" . MODULE_PAYMENT_BARZAHLEN_NEW_STATUS_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_NEW_STATUS', '0', '" . MODULE_PAYMENT_BARZAHLEN_NEW_STATUS_DESC . "', '6', '8', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_PAID_STATUS_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_PAID_STATUS', '0', '" . MODULE_PAYMENT_BARZAHLEN_PAID_STATUS_DESC . "', '6', '9', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now()),
            ('" . MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS_TITLE . "', 'MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS', '0', '" . MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS_DESC . "', '6', '10', 'zen_cfg_pull_down_order_statuses(', 'zen_get_order_status_name', now())");

        $query = $db->Execute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                                WHERE table_name = '" . TABLE_ORDERS . "'
                                  AND table_schema = '" . DB_DATABASE . "'
                                  AND column_name = 'barzahlen_transaction_id'");

        if ($query->RecordCount() == 0) {
            $db->Execute("ALTER TABLE `" . TABLE_ORDERS . "` ADD `barzahlen_transaction_id` int(11) NOT NULL default '0';");
        }

        $query = $db->Execute("SELECT * FROM INFORMATION_SCHEMA.COLUMNS
                                WHERE table_name = '" . TABLE_ORDERS . "'
                                  AND table_schema = '" . DB_DATABASE . "'
                                  AND column_name = 'barzahlen_transaction_state'");

        if ($query->RecordCount() == 0) {
            $db->Execute("ALTER TABLE `" . TABLE_ORDERS . "` ADD `barzahlen_transaction_state` varchar(7) NOT NULL default '';");
        } else {
            $db->Execute("ALTER TABLE `" . TABLE_ORDERS . "` CHANGE `barzahlen_transaction_state` `barzahlen_transaction_state` varchar(15) NOT NULL default '';");
        }
    }

    /**
     * Uninstall sql queries.
     */
    function remove()
    {
        global $db;

        $parameters = $this->keys();
        $parameters[] = 'MODULE_PAYMENT_BARZAHLEN_ALLOWED';
        $parameters[] = 'MODULE_PAYMENT_BARZAHLEN_LAST_AUTO_CANCEL';
        $parameters[] = 'MODULE_PAYMENT_BARZAHLEN_LAST_UPDATE_CHECK';
        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $parameters) . "')");
    }

    /**
     * All necessary configuration attributes for the payment module.
     *
     * @return array with configuration attributes
     */
    function keys()
    {
        return array(
            'MODULE_PAYMENT_BARZAHLEN_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_SANDBOX',
            'MODULE_PAYMENT_BARZAHLEN_SHOPID',
            'MODULE_PAYMENT_BARZAHLEN_PAYMENTKEY',
            'MODULE_PAYMENT_BARZAHLEN_NOTIFICATIONKEY',
            'MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL',
            'MODULE_PAYMENT_BARZAHLEN_NEW_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_PAID_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_SORT_ORDER',
            'MODULE_PAYMENT_BARZAHLEN_DEBUG'
        );
    }

    /**
     * Gets settings and creates API object.
     *
     * @return Barzahlen_Api
     */
    function createApi()
    {
        $sandbox = MODULE_PAYMENT_BARZAHLEN_SANDBOX == 'True' ? true : false;
        $debug = MODULE_PAYMENT_BARZAHLEN_DEBUG == 'True' ? true : false;
        $api = new Barzahlen_Api(MODULE_PAYMENT_BARZAHLEN_SHOPID, MODULE_PAYMENT_BARZAHLEN_PAYMENTKEY, $sandbox);
        $api->setDebug($debug, $this->logFile);
        $api->setUserAgent('Zen Cart v' . PROJECT_VERSION_MAJOR . '.' . PROJECT_VERSION_MINOR . ' / Plugin v' . $this->version);

        return $api;
    }

    /**
     * Checks if automatic cancelation run more than one hour ago.
     *
     * @return bool
     */
    function checkLastAutoCancel()
    {
        global $db;

        $lastQuery = $db->Execute("SELECT configuration_value
                                     FROM " . TABLE_CONFIGURATION . "
                                    WHERE configuration_key = 'MODULE_PAYMENT_BARZAHLEN_LAST_AUTO_CANCEL'");

        if($lastQuery->RecordCount() == 0) {
            $db->Execute("INSERT INTO " . TABLE_CONFIGURATION . "
                          (configuration_key, configuration_value, configuration_group_id, date_added)
                          VALUES
                          ('MODULE_PAYMENT_BARZAHLEN_LAST_AUTO_CANCEL', NOW(), '6', NOW())");

            return true;
        } elseif ((time() - strtotime($lastQuery->fields['configuration_value'])) > 3600) {
            $db->Execute("UPDATE " . TABLE_CONFIGURATION . "
                             SET configuration_value = NOW()
                           WHERE configuration_key = 'MODULE_PAYMENT_BARZAHLEN_LAST_AUTO_CANCEL'");

            return true;
        }

        return false;
    }

    /**
     * Automatic cancel payment slips.
     */
    function autoCancel()
    {
        global $db;

        $api = $this->createApi();

        $orders_query = $db->Execute("SELECT orders_id, barzahlen_transaction_id
                                        FROM " . TABLE_ORDERS . "
                                       WHERE payment_method = 'barzahlen'
                                         AND orders_status = '" . MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS . "'
                                         AND barzahlen_transaction_state = 'pending'");

        while (!$orders_query->EOF) {
            $cancel = new Barzahlen_Request_Cancel($orders_query->fields['barzahlen_transaction_id']);
            try {
                $api->handleRequest($cancel);
            } catch (Exception $e) {
                $this->bzLog('barzahlen/cancel: ' . $e);
            }

            $db->Execute("UPDATE " . TABLE_ORDERS . "
                             SET barzahlen_transaction_state = 'canceled'
                           WHERE orders_id = '" . $orders_query->fields['orders_id'] . "'");

            $orders_query->MoveNext();
        }
    }

    /**
     * Coverts text to iso-8859-15 encoding.
     *
     * @param string $text utf-8 text
     * @return string ISO-8859-15 text
     */
    function convertISO($text)
    {
        return mb_convert_encoding($text, 'iso-8859-15', 'utf-8');
    }

    /**
     * Logs errors into Barzahlen log file.
     *
     * @param string $message error message
     */
    function bzLog($message)
    {
        $time = date("[Y-m-d H:i:s] ");
        error_log($time . $message . "\r\r", 3, $this->logFile);
    }
}
