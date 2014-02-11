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
 * @author      Mathias Hertlein
 * @license     http://opensource.org/licenses/GPL-2.0  GNU General Public License, version 2 (GPL-2.0)
 */

require_once('src/admin/includes/modules/barzahlen/BarzahlenPluginCheckRequest.php');

class BarzahlenPluginCheckRequestTest extends PHPUnit_Framework_TestCase
{
    private $httpClient;
    private $params;
    private $key;
    private $result;
    private $pluginVersion;
    private $resultXml;

    public function setUp()
    {
        $this->httpClient = $this->getMock("BarzahlenHttpClient", array("post"), array(), "", false);
        $this->params = array(
            'shop_id' => 123,
            'shopsystem' => "foo",
            'shopsystem_version' => "123.45",
            'plugin_version' => "133.41",
        );
        $this->key = "foo123bar";
        $this->result = 0;
        $this->pluginVersion = "1.2";
        $this->resultXml = "<?xml version=\"1.0\" ?><response><result>{$this->result}</result><plugin-version>{$this->pluginVersion}</plugin-version></response>";
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
        $request->sendRequest($this->params, $this->key);
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
        $request->sendRequest($this->params, $this->key);

        $this->assertEquals($this->pluginVersion, $request->getPluginVersion());
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
        $request->sendRequest($this->params, $this->key);
    }

    public function testExceptionWillBeThrownOnError()
    {
        $this->setExpectedException("RuntimeException");

        $this->resultXml = "<?xml version=\"1.0\" ?><response><result>1</result><plugin-version>{$this->pluginVersion}</plugin-version></response>";

        $this->httpClient
            ->expects($this->any())
            ->method('post')
            ->will($this->returnValue(array(
                'response' => $this->resultXml,
                'error' => "0",
            )));

        $request = $this->createRequest();
        $request->sendRequest($this->params, $this->key);
    }
}
