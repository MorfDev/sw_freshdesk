<?php


class Shopware_Controllers_Api_Freshdesk extends Shopware_Controllers_Api_Rest
{
    /**
     * @var Shopware\Components\Api\Resource\Freshdesk
     */
    protected $resource = null;

    public function init()
    {
        $this->resource = \Shopware\Components\Api\Manager::getResource('Freshdesk');
    }

    /**
     * GET Request on /api/Freshdesk
     */
    public function indexAction()
    {
        $email = $this->Request()->getParam('email');
        $orderId = $this->Request()->getParam('order_id');

        $result = $this->resource->getInfo($email, $orderId);

        $this->View()->assign(['success' => true, 'data' => $result]);
    }
}
