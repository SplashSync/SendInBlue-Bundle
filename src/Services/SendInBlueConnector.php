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

namespace Splash\Connectors\SendInBlue\Services;

use ArrayObject;
use Splash\Bundle\Models\AbstractConnector;
use Splash\Bundle\Models\Connectors\GenericObjectMapperTrait;
use Splash\Bundle\Models\Connectors\GenericWidgetMapperTrait;
use Splash\Connectors\SendInBlue\Form\EditFormType;
use Splash\Connectors\SendInBlue\Form\NewFormType;
use Splash\Connectors\SendInBlue\Models\SendInBlueHelper as API;
use Splash\Connectors\SendInBlue\Objects\WebHook;
use Splash\Core\SplashCore as Splash;
use Symfony\Component\Routing\RouterInterface;

/**
 * SendInBlue REST API Connector for Splash
 *
 * @SuppressWarnings(PHPMD.ExcessiveClassComplexity)
 */
class SendInBlueConnector extends AbstractConnector
{
    use GenericObjectMapperTrait;
    use GenericWidgetMapperTrait;

    /**
     * Objects Type Class Map
     *
     * @var array
     */
    protected static $objectsMap = array(
        "ThirdParty" => "Splash\\Connectors\\SendInBlue\\Objects\\ThirdParty",
    );

    /**
     * Widgets Type Class Map
     *
     * @var array
     */
    protected static $widgetsMap = array(
        "SelfTest" => "Splash\\Connectors\\SendInBlue\\Widgets\\SelfTest",
    );

    /**
     * {@inheritdoc}
     */
    public function ping() : bool
    {
        //====================================================================//
        // Safety Check => Verify Selftest Pass
        if (!$this->selfTest()) {
            return false;
        }
        //====================================================================//
        // Perform Ping Test
        return API::ping();
    }

    /**
     * {@inheritdoc}
     */
    public function connect() : bool
    {
        //====================================================================//
        // Safety Check => Verify Selftest Pass
        if (!$this->selfTest()) {
            return false;
        }
        //====================================================================//
        // Perform Connect Test
        if (!API::connect()) {
            return false;
        }
        //====================================================================//
        // Get List of Available Lists
        if (!$this->fetchMailingLists()) {
            return false;
        }
        //====================================================================//
        // Get List of Available Members Properties
        if (!$this->fetchAttributesLists()) {
            return false;
        }

        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function informations(ArrayObject  $informations) : ArrayObject
    {
        //====================================================================//
        // Server General Description
        $informations->shortdesc = "SendInBlue";
        $informations->longdesc = "Splash Integration for SendInBlue's Api V3.0";
        //====================================================================//
        // Server Logo & Ico
        $informations->icoraw = Splash::file()->readFileContents(
            dirname(dirname(__FILE__))."/Resources/public/img/SendInBlue-Logo.jpg"
        );
        $informations->logourl = null;
        $informations->logoraw = Splash::file()->readFileContents(
            dirname(dirname(__FILE__))."/Resources/public/img/SendInBlue-Logo.jpg"
        );
        //====================================================================//
        // Server Information
        $informations->servertype = "SendInBlue REST Api V3";
        $informations->serverurl = API::ENDPOINT;
        //====================================================================//
        // Module Information
        $informations->moduleauthor = SPLASH_AUTHOR;
        $informations->moduleversion = "master";

        $config = $this->getConfiguration();
        //====================================================================//
        // Safety Check => Verify Selftest Pass
        if (!$this->selfTest() || empty($config["ApiList"])) {
            return $informations;
        }
        //====================================================================//
        // Get List Detailed Information
        $details = API::get('account');
        if (is_null($details)) {
            return $informations;
        }

        //====================================================================//
        // Company Information
        $informations->company = $details->companyName;
        $informations->address = $details->address->street;
        $informations->zip = $details->address->zipCode;
        $informations->town = $details->address->city;
        $informations->country = $details->address->country;
        $informations->www = "www.sendinblue.com";
        $informations->email = $details->email;
        $informations->phone = "~";

        return $informations;
    }

    /**
     * {@inheritdoc}
     */
    public function selfTest() : bool
    {
        $config = $this->getConfiguration();

        //====================================================================//
        // Verify Api Key is Set
        //====================================================================//
        if (!isset($config["ApiKey"]) || empty($config["ApiKey"]) || !is_string($config["ApiKey"])) {
            Splash::log()->err("Api Key is Invalid");

            return false;
        }

        //====================================================================//
        // Configure Rest API
        return API::configure(
            $config["ApiKey"],
            isset($config["ApiList"]) ? $config["ApiList"] : null
        );
    }

    //====================================================================//
    // Objects Interfaces
    //====================================================================//

    //====================================================================//
    // Files Interfaces
    //====================================================================//

    /**
     * {@inheritdoc}
     */
    public function getFile(string $filePath, string $fileMd5)
    {
        //====================================================================//
        // Safety Check => Verify Selftest Pass
        if (!$this->selfTest()) {
            return false;
        }
        Splash::log()->err("There are No Files Reading for Mailchime Up To Now!");

        return false;
    }

    //====================================================================//
    // Profile Interfaces
    //====================================================================//

    /**
     * @abstract   Get Connector Profile Informations
     *
     * @return array
     */
    public function getProfile() : array
    {
        return array(
            'enabled' => true,                                   // is Connector Enabled
            'beta' => false,                                  // is this a Beta release
            'type' => self::TYPE_ACCOUNT,                     // Connector Type or Mode
            'name' => 'sendinblue',                           // Connector code (lowercase, no space allowed)
            'connector' => 'splash.connectors.sendinblue',         // Connector Symfony Service
            'title' => 'profile.card.title',                   // Public short name
            'label' => 'profile.card.label',                   // Public long name
            'domain' => 'SendInBlueBundle',                     // Translation domain for names
            'ico' => '/bundles/sendinblue/img/SendInBlue-Logo.jpg', // Public Icon path
            'www' => 'www.SendInBlue.com',                   // Website Url
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getConnectedTemplate() : string
    {
        return "@SendInBlue/Profile/connected.html.twig";
    }

    /**
     * {@inheritdoc}
     */
    public function getOfflineTemplate() : string
    {
        return "@SendInBlue/Profile/offline.html.twig";
    }

    /**
     * {@inheritdoc}
     */
    public function getNewTemplate() : string
    {
        return "@SendInBlue/Profile/new.html.twig";
    }

    /**
     * {@inheritdoc}
     */
    public function getFormBuilderName() : string
    {
        return $this->getParameter("ApiListsIndex", false) ? EditFormType::class : NewFormType::class;
    }

    /**
     * {@inheritdoc}
     */
    public function getMasterAction(): ?string
    {
        return null;
    }

    /**
     * {@inheritdoc}
     */
    public function getPublicActions() : array
    {
        return array(
            "index" => "SendInBlueBundle:WebHooks:index",
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getSecuredActions() : array
    {
        return array(
            "webhooks" => "SendInBlueBundle:Actions:webhooks",
        );
    }

    //====================================================================//
    //  HIGH LEVEL WEBSERVICE CALLS
    //====================================================================//

    /**
     * Check & Update SendInBlue Api Account WebHooks.
     *
     * @return bool
     */
    public function verifyWebHooks() : bool
    {
        //====================================================================//
        // Connector SelfTest
        if (!$this->selfTest()) {
            return false;
        }
        //====================================================================//
        // Generate WebHook Url
        $webHookServer = filter_input(INPUT_SERVER, 'SERVER_NAME');
        //====================================================================//
        // When Running on a Local Server
        if (false !== strpos("localhost", $webHookServer)) {
            $webHookServer = "www.splashsync.com";
        }
        //====================================================================//
        // Create Object Class
        $webHookManager = new WebHook($this);
        $webHookManager->configure("webhook", $this->getWebserviceId(), $this->getConfiguration());
        //====================================================================//
        // Get List Of WebHooks for this List
        $webHooks = $webHookManager->objectsList();
        if (isset($webHooks["meta"])) {
            unset($webHooks["meta"]);
        }
        //====================================================================//
        // Filter & Clean List Of WebHooks
        foreach ($webHooks as $webHook) {
            //====================================================================//
            // This is a Splash WebHooks
            if (false !== strpos(trim($webHook['url']), $webHookServer)) {
                return true;
            }
        }
        //====================================================================//
        // Splash WebHooks was NOT Found
        return false;
    }

    /**
     * Check & Update SendInBlue Api Account WebHooks.
     *
     * @param RouterInterface $router
     *
     * @return bool
     */
    public function updateWebHooks(RouterInterface $router) : bool
    {
        //====================================================================//
        // Connector SelfTest
        if (!$this->selfTest()) {
            return false;
        }
        //====================================================================//
        // Generate WebHook Url
        $webHookServer = filter_input(INPUT_SERVER, 'SERVER_NAME');
        $webHookUrl = $router->generate(
            'splash_connector_action',
            array(
                'connectorName' => $this->getProfile()["name"],
                'webserviceId' => $this->getWebserviceId(),
            ),
            RouterInterface::ABSOLUTE_URL
        );
        //====================================================================//
        // When Running on a Local Server
        if (false !== strpos("localhost", $webHookServer)) {
            $webHookServer = "www.splashsync.com";
            $webHookUrl = "https://www.splashsync.com/en/ws/SendInBlue/123456";
        }
        //====================================================================//
        // Create Object Class
        $webHookManager = new WebHook($this);
        $webHookManager->configure("webhook", $this->getWebserviceId(), $this->getConfiguration());
        //====================================================================//
        // Get List Of WebHooks for this List
        $webHooks = $webHookManager->objectsList();
        if (isset($webHooks["meta"])) {
            unset($webHooks["meta"]);
        }
        //====================================================================//
        // Filter & Clean List Of WebHooks
        $foundWebHook = false;
        foreach ($webHooks as $webHook) {
            //====================================================================//
            // This is Current Node WebHooks
            if (trim($webHook['url']) == $webHookUrl) {
                $foundWebHook = true;

                continue;
            }
            //====================================================================//
            // This is a Splash WebHooks
            if (false !== strpos(trim($webHook['url']), $webHookServer)) {
                $webHookManager->delete($webHook['id']);
            }
        }
        //====================================================================//
        // Splash WebHooks was Found
        if ($foundWebHook) {
            return true;
        }
        //====================================================================//
        // Add Splash WebHooks
        return (false !== $webHookManager->create($webHookUrl));
    }

    //====================================================================//
    //  LOW LEVEL PRIVATE FUNCTIONS
    //====================================================================//

    /**
     * Get SendInBlue User Lists
     *
     * @return bool
     */
    private function fetchMailingLists(): bool
    {
        //====================================================================//
        // Get User Lists from Api
        $response = API::get('contacts/lists');
        if (is_null($response)) {
            return false;
        }
        if (!isset($response->lists)) {
            return false;
        }
        //====================================================================//
        // Parse Lists to Connector Settings
        $listIndex = array();
        foreach ($response->lists as $listDetails) {
            //====================================================================//
            // Add List Index
            $listIndex[$listDetails->id] = $listDetails->name;
        }
        //====================================================================//
        // Store in Connector Settings
        $this->setParameter("ApiListsIndex", $listIndex);
        $this->setParameter("ApiListsDetails", $response->lists);
        //====================================================================//
        // Update Connector Settings
        $this->updateConfiguration();

        return true;
    }

    /**
     * Get SendInBlue User Attributes Lists
     *
     * @return bool
     */
    private function fetchAttributesLists(): bool
    {
        //====================================================================//
        // Get User Lists from Api
        $response = API::get('contacts/attributes');
        if (is_null($response)) {
            return false;
        }
        // @codingStandardsIgnoreStart
        if (!isset($response->attributes)) {
            return false;
        }
        //====================================================================//
        // Store in Connector Settings
        $this->setParameter("ContactAttributes", $response->attributes);
        // @codingStandardsIgnoreEnd
        //====================================================================//
        // Update Connector Settings
        $this->updateConfiguration();

        return true;
    }
}
