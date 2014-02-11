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
 * @copyright   Copyright (c) 2013 Zerebro Internet GmbH (http://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

class barzahlen
{
    /**
     * @const APIDOMAIN call domain (productive use)
     */
    const APIDOMAIN = 'https://api.barzahlen.de/v1/transactions/';

    /**
     * @const APIDOMAINSANDBOX sandbox call domain (productive use)
     */
    const APIDOMAINSANDBOX = 'https://api-sandbox.barzahlen.de/v1/transactions/';

    /**
     * @const HASHSEPARATOR hash separator for hash string
     */
    const HASHSEPARATOR = ';';

    /**
     * @const HASHALGORITHM algorithm for hash generation
     */
    const HASHALGORITHM = 'sha512';

    /**
     * @const MAXATTEMPTS maximum connect attempts per request
     */
    const MAXATTEMPTS = 2;

    /**
     * Constructor class, sets the settings.
     */
    function barzahlen()
    {
        $this->code = 'barzahlen';
        $this->version = '1.1.6';
        $this->title = MODULE_PAYMENT_BARZAHLEN_TEXT_TITLE;
        $this->description = '<div align="center">' . zen_image('https://cdn.barzahlen.de/images/barzahlen_logo.png', MODULE_PAYMENT_BARZAHLEN_TEXT_TITLE) . '</div><br>' . MODULE_PAYMENT_BARZAHLEN_TEXT_DESCRIPTION;
        $this->sort_order = MODULE_PAYMENT_BARZAHLEN_SORT_ORDER;
        $this->enabled = (MODULE_PAYMENT_BARZAHLEN_STATUS == 'True') ? true : false;
        $this->defaultCurrency = 'EUR';

        $this->cert = DIR_WS_MODULES . 'payment/barzahlen/ca-bundle.crt';
        $this->logFile = DIR_FS_CATALOG . 'logs/barzahlen.log';
        $this->currencies = array('EUR');
        $this->countries = array('DE');
        $this->connectAttempts = 0;
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
            $this->_bzLog('Maximum order amount (' . MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL . ') is not valid.' .
                ' Should be between 0.00 and 999.99 Euros.');
            return false;
        }

        if ($order->info['total'] < MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL) {
            $title = $this->title;
            $description = str_replace('{{image}}', zen_image('https://cdn.barzahlen.de/images/barzahlen_logo.png'), MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_DESCRIPTION);

            if (MODULE_PAYMENT_BARZAHLEN_SANDBOX == 'True') {
                $title .= ' [SANDBOX]';
                $description .= MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_SANDBOX;
            }

            $description .= MODULE_PAYMENT_BARZAHLEN_TEXT_FRONTEND_PARTNER;

            for ($i = 1; $i <= 10; $i++) {
                $count = str_pad($i, 2, "0", STR_PAD_LEFT);
                $description .= '<img src="https://cdn.barzahlen.de/images/barzahlen_partner_' . $count . '.png" alt="" style="vertical-align: middle; height: 25px;" />';
            }

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

        $transData = array();
        $transData['customer_email'] = $order->customer['email_address'];
        $transData['amount'] = number_format($order->info['total'], 2, '.', '');
        $transData['currency'] = $order->info['currency'];
        $transData['language'] = $_SESSION['languages_code'];
        $transData['order_id'] = $insert_id;
        $transData['customer_street_nr'] = $this->_isoConvert($order->customer['street_address']);
        $transData['customer_zipcode'] = $order->customer['postcode'];
        $transData['customer_city'] = $this->_isoConvert($order->customer['city']);
        $transData['customer_country'] = $order->customer['country']['iso_code_2'];
        $transData['custom_var_0'] = '';
        $transData['custom_var_1'] = '';
        $transData['custom_var_2'] = '';
        $transArray = $this->_buildTransArray($transData);
        $xmlArray = $this->_connectToApi('create', $transArray);

        // select last order history comment for this order
        $query = $db->Execute("SELECT orders_status_history_id, comments FROM " . TABLE_ORDERS_STATUS_HISTORY . "
                           WHERE orders_id = '" . $insert_id . "'
                           ORDER BY orders_status_history_id DESC");

        if ($xmlArray != null) {
            $_SESSION['payment_method_messages'] = $xmlArray['infotext-1'];

            // set transaction details
            $db->Execute("UPDATE " . TABLE_ORDERS . "
                    SET barzahlen_transaction_id = '" . (int)$xmlArray['transaction-id'] . "' ,
                        barzahlen_transaction_state = 'pending',
                        orders_status = '" . MODULE_PAYMENT_BARZAHLEN_NEW_STATUS . "'
                    WHERE orders_id = '" . $insert_id . "'");

            // insert create success comment
            $db->Execute("UPDATE " . TABLE_ORDERS_STATUS_HISTORY . "
                    SET orders_status_id = '" . MODULE_PAYMENT_BARZAHLEN_NEW_STATUS . "',
                        comments = '" . MODULE_PAYMENT_BARZAHLEN_TEXT_X_ATTEMPT_SUCCESS . "'
                    WHERE orders_status_history_id = '" . $query->fields['orders_status_history_id'] . "'");
        } else {
            // set order details
            $db->Execute("UPDATE " . TABLE_ORDERS . "
                    SET barzahlen_transaction_id = '" . (int)$xmlArray['transaction-id'] . "' ,
                        barzahlen_transaction_state = 'pending',
                        orders_status = '" . MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS . "'
                    WHERE orders_id = '" . $insert_id . "'");

            // insert failure comment
            $db->Execute("UPDATE " . TABLE_ORDERS_STATUS_HISTORY . "
                    SET orders_status_id = '" . MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS . "',
                        comments = '" . $this->_convertISO(MODULE_PAYMENT_BARZAHLEN_TEXT_PAYMENT_ATTEMPT_FAILED) . "'
                    WHERE orders_status_history_id = '" . $query->fields['orders_status_history_id'] . "'");

            $messageStack->add_session('checkout_payment', $this->_convertISO(MODULE_PAYMENT_BARZAHLEN_TEXT_PAYMENT_ERROR), 'error');
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
            $check_query = $db->Execute("SELECT configuration_value FROM " . TABLE_CONFIGURATION . "
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
        $db->Execute("DELETE FROM " . TABLE_CONFIGURATION . " WHERE configuration_key IN ('" . implode("', '", $parameters) . "')");
    }

    /**
     * All necessary configuration attributes for the payment module.
     *
     * @return array with configuration attributes
     */
    function keys()
    {
        return array('MODULE_PAYMENT_BARZAHLEN_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_SANDBOX',
            'MODULE_PAYMENT_BARZAHLEN_SHOPID',
            'MODULE_PAYMENT_BARZAHLEN_PAYMENTKEY',
            'MODULE_PAYMENT_BARZAHLEN_NOTIFICATIONKEY',
            'MODULE_PAYMENT_BARZAHLEN_MAXORDERTOTAL',
            'MODULE_PAYMENT_BARZAHLEN_NEW_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_PAID_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_EXPIRED_STATUS',
            'MODULE_PAYMENT_BARZAHLEN_SORT_ORDER',
            'MODULE_PAYMENT_BARZAHLEN_DEBUG');
    }

    /**
     * Prepares the transaction array with shop id and hash. Removes empty entires.
     *
     * @param array $data request details data
     * @return array with shop id and hash
     */
    function _buildTransArray(array $data)
    {
        $transArray = array();
        $transArray['shop_id'] = MODULE_PAYMENT_BARZAHLEN_SHOPID;

        foreach ($data as $key => $value) {
            $transArray[$key] = $value;
        }

        $transArray['hash'] = $this->_getHash($transArray);

        foreach ($transArray as $key => $value) {
            if ($value == '') {
                unset($transArray[$key]);
            }
        }

        return $transArray;
    }

    /**
     * Generates the sha512 hash out of the transaction array.
     *
     * @param array $array transaction array
     * @return sha512 hash
     */
    function _getHash(array $array)
    {
        $array[] = MODULE_PAYMENT_BARZAHLEN_PAYMENTKEY;
        $HashString = implode(self::HASHSEPARATOR, $array);
        return hash(self::HASHALGORITHM, $HashString);
    }

    /**
     * Prepares call domain and request sending.
     *
     * @param string $type request type ('create' or 'update')
     * @param array $transArray transaction data for request
     * @return null | array with xml response values
     */
    function _connectToApi($type, array $transArray)
    {
        if (MODULE_PAYMENT_BARZAHLEN_SANDBOX == 'False') {
            $this->callDomain = self::APIDOMAIN . $type;
        } else {
            $this->callDomain = self::APIDOMAINSANDBOX . $type;
        }

        $this->connectAttempts++;
        $this->_bzDebug('Sending transaction array to server - ' . serialize(array($this->callDomain, $transArray)));
        $xmlResponse = $this->_sendTransArray($transArray);
        $this->_bzDebug('Received xml response, parsing now - ' . serialize($xmlResponse));
        $xmlArray = $this->_getResponseData($xmlResponse);
        $this->_bzDebug('Finished parsing, xml array ready - ' . serialize($xmlArray));

        if ($xmlArray == null && $this->connectAttempts < self::MAXATTEMPTS) {
            return $this->_connectToApi($type, $transArray);
        }

        return $xmlArray;
    }

    /**
     * Sends the build transaction array to the Barzahlen server.
     *
     * @param array $transArray build transaction array
     * @return string with xml answer | null, if an error occurred
     */
    function _sendTransArray(array $transArray)
    {
        try {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->callDomain);
            curl_setopt($ch, CURLOPT_POST, count($transArray));
            curl_setopt($ch, CURLOPT_POSTFIELDS, $transArray);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
            curl_setopt($ch, CURLOPT_CAINFO, $this->cert);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 7);
            curl_setopt($ch, CURLOPT_HTTP_VERSION, 1.1);
            $return = curl_exec($ch);

            if ($return === false) {
                $this->_bzLog(curl_error($ch));
            }

            curl_close($ch);
            return $return;
        } catch (Exception $e) {
            $this->_bzLog($e);
            return null;
        }
    }

    /**
     * Extracts the data out of the xml answer and verifies them.
     *
     * @param string $xmlResponse received xml answer
     * @return null if an error occurred | array with received and valid data
     */
    function _getResponseData($xmlResponse)
    {
        $nodes = array('transaction-id', 'payment-slip-link', 'expiration-notice', 'infotext-1', 'infotext-2', 'result', 'hash');

        try {

            $simpleXML = new SimpleXMLElement($xmlResponse);

            if ($simpleXML->{'result'} != 0) {
                $this->_bzLog($simpleXML->{'error-message'});
                return null;
            }

            $xmlArray = array();
            foreach ($nodes as $node) {
                $xmlArray[$node] = (string)$simpleXML->{$node};
            }
        } catch (Exception $e) {
            $this->_bzLog($e);
            return null;
        }

        if (!$this->_verifyHash($xmlArray)) {
            $this->_bzLog('Hash not valid - ' . serialize($xmlArray));
            return null;
        }

        return $xmlArray;
    }

    /**
     * Verifies that the hash and therefore the xml answer is valid.
     *
     * @param array $xmlArray extracted xml data
     * @return boolean (TRUE if hash is valid, FALSE if not)
     */
    function _verifyHash(array $xmlArray)
    {
        $responseHash = $xmlArray['hash'];
        unset($xmlArray['hash']);
        $generatedHash = $this->_getHash($xmlArray);

        return $responseHash == $generatedHash;
    }

    /**
     * Logs errors into Barzahlen log file.
     *
     * @param string $message error message
     */
    function _bzLog($message)
    {
        $time = date("[Y-m-d H:i:s] ");
        error_log($time . $message . "\r\r", 3, $this->logFile);
    }

    /**
     * Writes transaction steps into Barzahlen log file, if enabled.
     *
     * @param string $message debug message
     */
    function _bzDebug($message)
    {
        if (MODULE_PAYMENT_BARZAHLEN_DEBUG == 'True') {
            $this->_bzLog($message);
        }
    }

    /**
     * Coverts text to iso-8859-15 encoding.
     *
     * @param string $text utf-8 text
     * @return string ISO-8859-15 text
     */
    function _convertISO($text)
    {
        return mb_convert_encoding($text, 'iso-8859-15', 'utf-8');
    }

    /**
     * Converts ISO-8859-1 strings to UTF-8 if necessary.
     *
     * @param string $string text which is to check
     * @return string with utf-8 encoding
     */
    function _isoConvert($string)
    {
        if (!preg_match('/\S/u', $string)) {
            $string = utf8_encode($string);
        }

        return $string;
    }
}
