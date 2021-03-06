<?php

/*
 *  This file is part of SplashSync Project.
 *
 *  Copyright (C) 2015-2020 Splash Sync  <www.splashsync.com>
 *
 *  This program is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
 *
 *  For the full copyright and license information, please view the LICENSE
 *  file that was distributed with this source code.
 */

namespace Splash\Connectors\SendInBlue\Test\Controller;

use Splash\Connectors\SendInBlue\Objects\ThirdParty;
use Splash\Connectors\SendInBlue\Services\SendInBlueConnector;
use Splash\Tests\Tools\TestCase;

/**
 * Test of SendInBlue Connector WebHook Controller
 */
class S01WebHookTest extends TestCase
{
    const PING_RESPONSE = '{"success":true}';
    const MEMBER = "ThirdParty";
    const FAKE_EMAIL = "fake@exemple.com";

    /**
     * Test WebHook For Ping
     *
     * @return void
     */
    public function testWebhookPing()
    {
        //====================================================================//
        // Load Connector
        $connector = $this->getConnector("sendinblue");
        $this->assertInstanceOf(SendInBlueConnector::class, $connector);

        //====================================================================//
        // Ping Action -> POST -> KO
        $this->assertPublicActionWorks($connector, null, array("email" => "example@example.com"), "POST");
        $this->assertEquals(self::PING_RESPONSE, $this->getResponseContents());

        //====================================================================//
        // Ping Action -> POST -> KO
        $this->assertPublicActionFail($connector, null, array(), "POST");
        //====================================================================//
        // Ping Action -> GET -> KO
        $this->assertPublicActionFail($connector, null, array(), "GET");
        //====================================================================//
        // Ping Action -> PUT -> KO
        $this->assertPublicActionFail($connector, null, array(), "PUT");
    }

    /**
     * Test WebHook with Errors
     *
     * @return void
     */
    public function testWebhookErrors()
    {
        //====================================================================//
        // Load Connector
        $connector = $this->getConnector("sendinblue");
        $this->assertInstanceOf(SendInBlueConnector::class, $connector);

        //====================================================================//
        // Empty Contents
        //====================================================================//

        $this->assertPublicActionFail($connector, null, array(), "POST");

        //====================================================================//
        // EVENT BUT NO EMAIL
        //====================================================================//

        $this->assertPublicActionFail($connector, null, array("event" => "unsubscribed"), "POST");

        //====================================================================//
        // EMAIOL BUT NO EVENT
        //====================================================================//

        $this->assertPublicActionFail($connector, null, array("email" => self::FAKE_EMAIL), "POST");
    }

    /**
     * Test WebHook Member Updates
     *
     * @dataProvider webHooksInputsProvider
     *
     * @param array  $data
     * @param string $objectType
     * @param string $action
     * @param string $objectId
     *
     * @return void
     */
    public function testWebhookRequest(array $data, string $objectType, string $action, string $objectId)
    {
        //====================================================================//
        // Load Connector
        $connector = $this->getConnector("sendinblue");
        $this->assertInstanceOf(SendInBlueConnector::class, $connector);

        //====================================================================//
        // Prepare Request
//        $post  = array_replace_recursive(
//            array("mj_list_id" => $connector->getParameter("ApiList")),
//            $data
//        );
        $post = $data;

        //====================================================================//
        // Touch Url
        $this->assertPublicActionWorks($connector, null, $post, "POST");
        $this->assertEquals(
            json_encode(array("success" => true)),
            $this->getResponseContents()
        );

        //====================================================================//
        // Verify Response
        $this->assertIsLastCommited($action, $objectType, $objectId);
    }

    /**
     * Generate Fake Inputs for WebHook Requets
     *
     * @return array
     */
    public function webHooksInputsProvider()
    {
        $hooks = array();

        //====================================================================//
        // Generate Subscribe Events
        for ($i = 0; $i < 10; $i++) {
            //====================================================================//
            // Generate Random Contact Email
            $randEmail = uniqid().self::FAKE_EMAIL;
            //====================================================================//
            // Add WebHook Test
            $hooks[] = array(
                array(
                    "event" => "unsubscribed",
                    "email" => $randEmail,
                ),
                self::MEMBER,
                SPL_A_UPDATE,
                ThirdParty::encodeContactId($randEmail),
            );
        }

        //====================================================================//
        // Generate Add To List Events
        for ($i = 0; $i < 10; $i++) {
            //====================================================================//
            // Generate Random Contact Email
            $randEmail = uniqid().self::FAKE_EMAIL;
            //====================================================================//
            // Add WebHook Test
            $hooks[] = array(
                array(
                    "event" => "listAddition",
                    "email" => $randEmail,
                ),
                self::MEMBER,
                SPL_A_UPDATE,
                ThirdParty::encodeContactId($randEmail),
            );
        }

        return $hooks;
    }
}
