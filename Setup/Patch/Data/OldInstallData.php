<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\B2bCompanySampleData\Setup;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\B2BCompanySampleData\Model\Company;
use MagentoEse\B2BCompanySampleData\Model\Customer;
use MagentoEse\B2BCompanySampleData\Model\Salesrep;
use MagentoEse\B2BCompanySampleData\Model\Team;


class OldInstallData implements DataPatchInterface
{


    /**
     * @var Company
     */
    protected $companySetup;

    /**
     * @var Customer
     */
    protected $customerSetup;

    /**
     * @var Salesrep
     */
    protected $salesrepSetup;

    /**
     * @var Team
     */
    protected $teamSetup;


    /**
     * Product constructor.
     * @param Company $companySetup
     * @param Customer $customerSetup
     * @param Salesrep $salesrepSetup
     * @param Team $teamSetup
     */
    public function __construct(
        Company $companySetup,
        Customer $customerSetup,
        Salesrep $salesrepSetup,
        Team $teamSetup

    ) {
        $this->companySetup = $companySetup;
        $this->customerSetup = $customerSetup;
        $this->salesrepSetup = $salesrepSetup;
        $this->teamSetup = $teamSetup;


    }

    public function apply()
    {
        $this->salesrepSetup->install(['MagentoEse_B2BCompanySampleData::fixtures/salesreps.csv']);
        $this->customerSetup->install(['MagentoEse_B2BCompanySampleData::fixtures/customers.csv']);
        $this->companySetup->install(['MagentoEse_B2BCompanySampleData::fixtures/companies.csv']);
        $this->teamSetup->install(['MagentoEse_B2BCompanySampleData::fixtures/teams.csv']);
    }

    public static function getDependencies()
    {
        return [];
    }

    public function getAliases()
    {
        return [];
    }
}
