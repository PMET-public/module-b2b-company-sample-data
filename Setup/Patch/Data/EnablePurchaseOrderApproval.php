<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\B2bCompanySampleData\Setup\Patch\Data;


use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\Patch\DataPatchInterface;


class EnablePurchaseOrderApproval implements DataPatchInterface
{

    /**
     * @var CompanyRepositoryInterface
     */
    private $companyRepository;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    public function __construct(CompanyRepositoryInterface $companyRepository, SearchCriteriaBuilder $searchCriteriaBuilder)
    {
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
    }

    public function apply()
    {
        $filter = $this->searchCriteriaBuilder;
        $filter->addFilter('entity_id','gt 0');
        $companyList = $this->companyRepository->getList($filter->create())->getItems();
        foreach($companyList as $company){
            $this->enablePurchaseOrdersByCompanyId($company->getId());
        }
    }

    private function enablePurchaseOrdersByCompanyId(int $companyId)
    {

        $company = $this->companyRepository->get($companyId);
        $company->getExtensionAttributes()->setIsPurchaseOrderEnabled(true);
        $this->companyRepository->save($company);
    }

    public static function getDependencies()
    {
        return [OldInstallData::class];
    }

    public function getAliases()
    {
        return [];
    }
}
