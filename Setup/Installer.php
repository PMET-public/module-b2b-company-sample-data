<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\B2BCompanySampleData\Setup;

use Magento\Framework\Setup;


class Installer implements Setup\SampleData\InstallerInterface
{

    protected $companySetup;
    protected $customerSetup;
    protected $salesrepSetup;
    protected $teamSetup;
    protected $catalogSetup;
    protected $sharedCatalogConfig;
    protected $tierPricing;
    protected $relatedProducts;
    protected $sampleOrder;
    protected $index;


    public function __construct(
        \MagentoEse\B2BCompanySampleData\Model\Company $companySetup,
        \MagentoEse\B2BCompanySampleData\Model\Customer $customerSetup,
        \MagentoEse\B2BCompanySampleData\Model\Salesrep $salesrepSetup,
        \MagentoEse\B2BCompanySampleData\Model\Team $teamSetup

    ) {
        $this->companySetup = $companySetup;
        $this->customerSetup = $customerSetup;
        $this->salesrepSetup = $salesrepSetup;
        $this->teamSetup = $teamSetup;


    }

    /**
     * {@inheritdoc}
     */
    public function install()
    {
       $this->salesrepSetup->install(['MagentoEse_B2BCompanySampleData::fixtures/salesreps.csv']);
       $this->customerSetup->install(['MagentoEse_B2BCompanySampleData::fixtures/customers.csv']);
       $this->companySetup->install(['MagentoEse_B2BCompanySampleData::fixtures/companies.csv']);
       $this->teamSetup->install(['MagentoEse_B2BCompanySampleData::fixtures/teams.csv']);
    }
}