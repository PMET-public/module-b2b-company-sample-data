<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace MagentoEse\B2BCompanySampleData\Setup;

use Magento\Framework\Setup;


class Installer implements Setup\SampleData\InstallerInterface
{

    /**
     * @var \MagentoEse\B2BCompanySampleData\Model\Company
     */
    protected $companySetup;

    /**
     * @var \MagentoEse\B2BCompanySampleData\Model\Customer
     */
    protected $customerSetup;

    /**
     * @var \MagentoEse\B2BCompanySampleData\Model\Salesrep
     */
    protected $salesrepSetup;

    /**
     * @var \MagentoEse\B2BCompanySampleData\Model\Team
     */
    protected $teamSetup;


    /**
     * Product constructor.
     * @param \MagentoEse\B2BCompanySampleData\Model\Company $companySetup
     * @param \MagentoEse\B2BCompanySampleData\Model\Customer $customerSetup
     * @param \MagentoEse\B2BCompanySampleData\Model\Salesrep $salesrepSetup
     * @param \MagentoEse\B2BCompanySampleData\Model\Team $teamSetup
     */
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