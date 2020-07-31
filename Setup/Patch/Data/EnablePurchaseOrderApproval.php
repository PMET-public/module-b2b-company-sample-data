<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace MagentoEse\B2BCompanySampleData\Setup\Patch\Data;


use Magento\Company\Api\CompanyRepositoryInterface;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\Setup\Patch\DataPatchInterface;
use Magento\Config\Model\ResourceModel\Config as ResourceConfig;


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
    /** @var ResourceConfig */
    protected $resourceConfig;


    /**
     * EnablePurchaseOrderApproval constructor.
     * @param CompanyRepositoryInterface $companyRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     */

    public function __construct(CompanyRepositoryInterface $companyRepository,
                                SearchCriteriaBuilder $searchCriteriaBuilder, ResourceConfig $resourceConfig)
    {
        $this->companyRepository = $companyRepository;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->resourceConfig = $resourceConfig;
    }

    public function apply()
    {
        //Enable order approval
        $this->resourceConfig->saveConfig('btob/website_configuration/purchaseorder_enabled', 1, 'default', 0);
//        //Enable for each store
//        $filter = $this->searchCriteriaBuilder;
//        $filter->addFilter('entity_id','0','neq');
//        $companyList = $this->companyRepository->getList($filter->create())->getItems();
//        foreach($companyList as $company){
//            //$this->enablePurchaseOrdersByCompanyId($company->getId());
//        }
    }

    private function enablePurchaseOrdersByCompanyId(int $companyId)
    {

        $company = $this->companyRepository->get($companyId);
        $company->getExtensionAttributes()->setIsQuoteEnabled(false);
        $this->companyRepository->save($company);
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
