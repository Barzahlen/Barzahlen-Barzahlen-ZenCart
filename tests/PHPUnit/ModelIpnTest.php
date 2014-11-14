<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * @copyright   Copyright (c) 2014 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once('src/includes/modules/payment/barzahlen/model.ipn.php');

class ModelIpnTest extends PHPUnit_Framework_TestCase
{
    /**
     * Set everything that is needed for the testing up.
     */
    public function setUp()
    {
        $this->db = new db_handler;
        $this->object = new Barzahlen_IPN;
    }

    /**
     * Test valid paid notification against a pending transaction.
     */
    public function testValidPaidAgainstPending()
    {
        $_GET = array('state' => 'paid',
            'transaction_id' => '6382214',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '1',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => '7f9c78365cfa08d828908c2d599f59a9649953527a276a0cef9d1f9d471b46cdcf7b16b821fd3f911fafc4e98b285a28a0a2be8897beb3e4453986f179f09fac'
        );

        $this->assertTrue($this->object->callback($_GET));

        $query = mysql_query("SELECT * FROM " . TABLE_ORDERS . " WHERE barzahlen_transaction_id = '6382214'");
        $result = mysql_fetch_array($query, MYSQL_ASSOC);
        $this->assertEquals('paid', $result['barzahlen_transaction_state']);
    }

    /**
     * Test valid expired notification against a pending transaction.
     */
    public function testValidExpiredAgainstPending()
    {
        $_GET = array('state' => 'expired',
            'transaction_id' => '6382214',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '1',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => '6c5e7fe765badc380a17cea7abc6df7a8640c0be14c3de94c81a228d754c76e9d18076f24264d12229187b2cca33b7a38b9ea6905da21469b66d1cc46da85474'
        );

        $this->assertTrue($this->object->callback($_GET));

        $query = mysql_query("SELECT * FROM " . TABLE_ORDERS . " WHERE barzahlen_transaction_id = '6382214'");
        $result = mysql_fetch_array($query, MYSQL_ASSOC);
        $this->assertEquals('expired', $result['barzahlen_transaction_state']);
    }

    /**
     * Test valid expired notification against a paid transaction.
     */
    public function testValidExpiredAgainstPaid()
    {
        $_GET = array('state' => 'expired',
            'transaction_id' => '6382566',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '2',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => 'ec1ab500795f10b7cdee36a99a8b55c55b0093337e72a7ed2313cb237a4da2e4c23959fcc8909d4368d7d823da89dc5444c9b977366fede2744d7d82110ce1fb'
        );

        $this->assertFalse($this->object->callback($_GET));

        $query = mysql_query("SELECT * FROM " . TABLE_ORDERS . " WHERE barzahlen_transaction_id = '6382566'");
        $result = mysql_fetch_array($query, MYSQL_ASSOC);
        $this->assertEquals('paid', $result['barzahlen_transaction_state']);
    }

    /**
     * Test valid paid notification against a expired transaction.
     */
    public function testValidPaidAgainstExpired()
    {
        $_GET = array('state' => 'paid',
            'transaction_id' => '6382649',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '3',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => 'f3a1e65146a64a695b7e71090dfdba8b4279d6cc83a496a5e4bc719ef1e5c1f73dcc98a00ca9bb6bc3841f7986a5fc5a683441857ff9c619a271f6124fcd07c6'
        );

        $this->assertFalse($this->object->callback($_GET));

        $query = mysql_query("SELECT * FROM " . TABLE_ORDERS . " WHERE barzahlen_transaction_id = '6382649'");
        $result = mysql_fetch_array($query, MYSQL_ASSOC);
        $this->assertEquals('expired', $result['barzahlen_transaction_state']);
    }

    /**
     * Test valid paid notification against a non existing order.
     */
    public function testValidPaidAgainstNonExistingOrder()
    {
        $_GET = array('state' => 'paid',
            'transaction_id' => '6382649',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '42',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => '0e00d44705e48fff2fe0a3d8d799af4bf33fda2ddf11872c3ccbc9c5099ebf951fe00b1bd5710e8ed03c44d68fbd9ad18bc92d2f1f9907af9d1c30c31d753504'
        );

        $this->assertFalse($this->object->callback($_GET));
    }

    /**
     * Test valid expired notification against a non existing transaction.
     */
    public function testValidExpiredAgainstNonExistingTransaction()
    {
        $_GET = array('state' => 'expired',
            'transaction_id' => '6382640',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '4',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => '578c7019ef08e3959d024b0d691c3ca08494dccac027e2342788639d3432bc93daa26e2acbb31b0dd24a92f6e7d7682625ba520d58e6d51e00858ff5c2fbffb5'
        );

        $this->assertFalse($this->object->callback($_GET));
    }

    /**
     * Test invalid paid notification against a pending transaction. (corrupt hash)
     */
    public function testInvalidPaidAgainstPending()
    {
        $_GET = array('state' => 'paid',
            'transaction_id' => '6382214',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '1',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => '7f9c78365cfa08d828908c2d599f59a9649953527a276a0cef9d1f9d471b46cdcf7b16b821fd3f911fafc4e98b285a28a0a2be8897beb3e4453986f179f09fad'
        );

        $this->assertFalse($this->object->callback($_GET));
    }

    /**
     * Test invalid paid notification against a pending transaction. (Wrong Shop ID)
     */
    public function testInvalidShopIdAgainstPending()
    {
        $_GET = array('state' => 'paid',
            'transaction_id' => '6382214',
            'shop_id' => '12345',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '1',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => 'df376f5a561452b804f466ebba25f12821a36b191a97b442a00812639c1404ed9421f4c63eaf7cc640324a8701f0f38f1811f83e2aa9dd30c4fe2238b9de19e1'
        );

        $this->assertFalse($this->object->callback($_GET));

        $query = mysql_query("SELECT * FROM " . TABLE_ORDERS . " WHERE barzahlen_transaction_id = '6382214'");
        $result = mysql_fetch_array($query, MYSQL_ASSOC);
        $this->assertEquals('pending', $result['barzahlen_transaction_state']);
    }

    /**
     * Test invalid paid notification against a pending transaction.
     */
    public function testInvalidStateAgainstPending()
    {
        $_GET = array('state' => 'paid_expired',
            'transaction_id' => '6382214',
            'shop_id' => '10003',
            'customer_email' => 'foo@bar.com',
            'amount' => '122.07',
            'currency' => 'EUR',
            'order_id' => '1',
            'custom_var_0' => '',
            'custom_var_1' => '',
            'custom_var_2' => '',
            'hash' => '3a9439cae6748944a9282d062e4c46afb505f2c91d699d8057600419e716330c6157a0cc1d589131ff2877d204c759b647eeb85e41ec080944b14d675cee3b06'
        );

        $this->assertFalse($this->object->callback($_GET));

        $query = mysql_query("SELECT * FROM " . TABLE_ORDERS . " WHERE barzahlen_transaction_id = '6382214'");
        $result = mysql_fetch_array($query, MYSQL_ASSOC);
        $this->assertEquals('pending', $result['barzahlen_transaction_state']);
    }

    /**
     * Unset everything before the next test.
     */
    public function tearDown()
    {
        unset($this->db);
        unset($this->object);
    }
}
