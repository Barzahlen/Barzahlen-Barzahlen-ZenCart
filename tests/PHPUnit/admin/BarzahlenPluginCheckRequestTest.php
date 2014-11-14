<?php
/**
 * Barzahlen Payment Module (Zen Cart)
 *
 * @copyright   Copyright (c) 2014 Cash Payment Solutions GmbH (https://www.barzahlen.de)
 * @author      Mathias Hertlein
 * @author      Alexander Diebler
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once('src/admin/includes/modules/barzahlen/BarzahlenPluginCheckRequest.php');

class BarzahlenPluginCheckRequestTest extends PHPUnit_Framework_TestCase
{
    private $httpClient;
    private $params;
    private $result;
    private $pluginUrl;
    private $pluginVersion;
    private $resultXml;

    public function setUp()
    {
        $this->httpClient = $this->getMock("BarzahlenHttpClient", array("post"), array(), "", false);
        $this->params = array(
            'shop_id' => MODULE_PAYMENT_BARZAHLEN_SHOPID,
            'shopsystem' => "Zen Cart",
            'shopsystem_version' => "1.5.0",
            'plugin_version' => "1.2.0",
        );
        $this->result = 0;
        $this->pluginUrl = "https://integration.barzahlen.de";
        $this->pluginVersion = "1.3.0";
        $this->resultXml = "<?xml version=\"1.0\" ?><response><result>{$this->result}</result><plugin-url>{$this->pluginUrl}</plugin-url><plugin-version>{$this->pluginVersion}</plugin-version></response>";
    }

    public function createRequest()
    {
        return new BarzahlenPluginCheckRequest($this->httpClient);
    }

    public function testClientWillBeCalled()
    {
        $this->httpClient
            ->expects($this->once())
            ->method('post')
            ->will($this->returnValue(array(
                'response' => $this->resultXml,
                'error' => "0",
            )));

        $request = $this->createRequest();
        $request->sendRequest($this->params);
    }

    public function testPluginVersionWillBeReturned()
    {
        $this->httpClient
            ->expects($this->any())
            ->method('post')
            ->will($this->returnValue(array(
                'response' => $this->resultXml,
                'error' => "0",
            )));

        $request = $this->createRequest();
        $request->sendRequest($this->params);

        $this->assertEquals($this->pluginVersion, $request->getPluginVersion());
        $this->assertEquals($this->pluginUrl, $request->getPluginUrl());
    }

    public function testExceptionWillBeThrownOnRequestError()
    {
        $this->setExpectedException("RuntimeException");

        $this->httpClient
            ->expects($this->any())
            ->method('post')
            ->will($this->returnValue(array(
                'response' => $this->resultXml,
                'error' => "1",
            )));

        $request = $this->createRequest();
        $request->sendRequest($this->params);
    }

    public function testExceptionWillBeThrownOnError()
    {
        $this->setExpectedException("RuntimeException");

        $this->resultXml = "<?xml version=\"1.0\" ?><response><result>1</result><plugin-url>{$this->pluginUrl}</plugin-url><plugin-version>{$this->pluginVersion}</plugin-version></response>";

        $this->httpClient
            ->expects($this->any())
            ->method('post')
            ->will($this->returnValue(array(
                'response' => $this->resultXml,
                'error' => "0",
            )));

        $request = $this->createRequest();
        $request->sendRequest($this->params);
    }
}
