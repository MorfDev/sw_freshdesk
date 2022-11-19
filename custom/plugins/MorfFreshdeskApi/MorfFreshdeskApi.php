<?php

namespace MorfFreshdeskApi;

use Shopware\Components\Plugin;

class MorfFreshdeskApi extends Plugin
{
    /**
     * @inheritdoc
     */
    public static function getSubscribedEvents()
    {
        return [
            'Enlight_Controller_Dispatcher_ControllerPath_Api_Banner' => 'onGetFreshdeskApiController',
            'Enlight_Controller_Front_StartDispatch' => 'onEnlightControllerFrontStartDispatch'
        ];
    }

    /**
     * @return string
     */
    public function onGetFreshdeskApiController()
    {
        return $this->getPath() . '/Controllers/Api/Freshdesk.php';
    }

    /**
     *
     */
    public function onEnlightControllerFrontStartDispatch()
    {
        $this->container->get('loader')->registerNamespace('Shopware\Components', $this->getPath() . '/Components/');
    }
}