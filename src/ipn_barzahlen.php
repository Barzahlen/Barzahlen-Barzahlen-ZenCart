<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * @copyright   Copyright (c) 2015 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

if(isset($_GET['state']) && preg_match('/^refund_/', $_GET['state'])) {
    header("HTTP/1.1 200 OK");
    header("Status: 200 OK");
    die();
} else {
    // catch $_GET content and unset $_GET to avoid abortion by application_top.php (hash, currency)
    $data = $_GET;
    unset($_GET);

    require_once("includes/application_top.php");
    require_once(DIR_WS_MODULES . "payment/barzahlen/model.ipn.php");
    global $db;
    $query = $db->Execute("SELECT directory FROM " . TABLE_LANGUAGES . " WHERE code = '" . DEFAULT_LANGUAGE . "'");
    require_once(DIR_WS_LANGUAGES . $query->fields['directory'] . '/modules/payment/barzahlen.php');

    $ipn = new Barzahlen_IPN;

    if ($ipn->callback($data)) {
        header("HTTP/1.1 200 OK");
        header("Status: 200 OK");
        die();
    } else {
        header("HTTP/1.1 400 Bad Request");
        header("Status: 400 Bad Request");
        die();
    }
}
