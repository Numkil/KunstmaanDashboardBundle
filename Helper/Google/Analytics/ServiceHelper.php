<?php

namespace Kunstmaan\DashboardBundle\Helper\Google\Analytics;

use Google\Service;
use Kunstmaan\DashboardBundle\Helper\Google\ClientHelper;

class ServiceHelper
{
    /** @var Service */
    private $service;

    /** @var ClientHelper */
    private $clientHelper;

    public function __construct(ClientHelper $clientHelper)
    {
        $this->clientHelper = $clientHelper;
        $this->service = new Service($clientHelper->getClient());
    }

    /**
     * @return Service
     */
    public function getService()
    {
        return $this->service;
    }

    /**
     * @return ClientHelper
     */
    public function getClientHelper()
    {
        return $this->clientHelper;
    }
}
