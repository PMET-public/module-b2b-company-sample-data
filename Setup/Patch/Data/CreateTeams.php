<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\B2BCompanySampleData\Setup\Patch\Data;


use Magento\Framework\Setup\Patch\DataPatchInterface;
use MagentoEse\B2BCompanySampleData\Model\Team;

class CreateTeams implements DataPatchInterface
{

    /**
     * @var Team
     */
    protected $teamSetup;

    public function __construct(Team $teamSetup)
    {
        $this->teamSetup = $teamSetup;
    }

    public function apply()
    {
        $this->teamSetup->install(['MagentoEse_B2BCompanySampleData::fixtures/teams.csv']);
    }

    public static function getDependencies()
    {
        return [OldInstallData::class,AddCompanyUserRoles::class];
    }

    public function getAliases()
    {
        return [];
    }
}
