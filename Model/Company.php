<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace MagentoEse\B2BCompanySampleData\Model;

use Magento\Framework\File\Csv;
use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
use Magento\Framework\Setup\SampleData\FixtureManager;

 class Company
 {

     /**
      * @var \Magento\Framework\Setup\SampleData\Context
      */
     protected $sampleDataContext;

     /**
      * @var \Magento\Company\Model\Customer\Company
      */
     protected $companyCustomer;

     /**
      * @var \Magento\Customer\Api\CustomerRepositoryInterface
      */
     protected $customer;

     /**
      * @var \Magento\Company\Model\ResourceModel\Customer
      */
     protected $customerResource;

     /**
      * @var \Magento\Company\Api\Data\StructureInterfaceFactory
      */
     protected $structure;

     /**
      * @var float
      */
     protected $creditLimit;

     /**
      * @var \Magento\CompanyCredit\Api\CreditLimitManagementInterface
      */
     protected $creditLimitManagement;

     /**
      * @var \Magento\User\Api\Data\UserInterfaceFactory
      */
     protected $userFactory;

     /**
      * 
      * @var FixtureManager
      */
     protected $fixtureManager;
     
     /**
      * 
      * @var Csv
      */
     protected $csvReader;

     /**
      * Company constructor.
      * @param SampleDataContext $sampleDataContext
      * @param \Magento\Company\Model\Customer\Company $companyCustomer
      * @param \Magento\Customer\Api\CustomerRepositoryInterface $customer
      * @param \Magento\Company\Model\ResourceModel\Customer $customerResource
      * @param \Magento\Company\Api\Data\StructureInterfaceFactory $structure
      * @param \Magento\CompanyCredit\Api\CreditLimitManagementInterface $creditLimitManagement
      * @param \Magento\User\Api\Data\UserInterfaceFactory $userInterfaceFactory
      */
     public function __construct(
         SampleDataContext $sampleDataContext,
         \Magento\Company\Model\Customer\Company $companyCustomer,
         \Magento\Customer\Api\CustomerRepositoryInterface $customer,
         \Magento\Company\Model\ResourceModel\Customer $customerResource,
        \Magento\Company\Api\Data\StructureInterfaceFactory $structure,
        \Magento\CompanyCredit\Api\CreditLimitManagementInterface $creditLimitManagement,
        \Magento\User\Model\UserFactory $userFactory
     )
     {
         $this->fixtureManager = $sampleDataContext->getFixtureManager();
         $this->csvReader = $sampleDataContext->getCsvReader();
         $this->companyCustomer = $companyCustomer;
         $this->customer = $customer;
         $this->customerResource = $customerResource;
         $this->structure = $structure;
         $this->creditLimitManagement = $creditLimitManagement;
         $this->userFactory = $userFactory;
     }

     /**
      * @param array $fixtures
      */
     public function install(array $fixtures)
     {

         foreach ($fixtures as $fileName) {
             $fileName = $this->fixtureManager->getFixture($fileName);
             if (!file_exists($fileName)) {
                 throw new Exception('File not found: '.$fileName);
             }
             $rows = $this->csvReader->getData($fileName);
             $header = array_shift($rows);
             foreach ($rows as $row) {
                 $data = [];
                 foreach ($row as $key => $value) {
                     $data[$header[$key]] = $value;
                 }
                 $data['company_customers'] = explode(",", $data['company_customers']);
                 //get customer for admin user
                 $adminCustomer = $this->customer->get($data['admin_email']);
                 //get sales rep
                 $salesRep = $this->userFactory->create();
                 $salesRep->loadByUsername($data['sales_rep']);

                 //$salesRep = $this->customer->get($data['sales_rep']);
                 $data['company_email']=$data['admin_email'];
                 //create company
                 $newCompany = $this->companyCustomer->createCompany($adminCustomer, $data);
                 $newCompany->setSalesRepresentativeId($salesRep->getId());
                 $newCompany->setIsPurchaseOrderEnabled(true);
                 $newCompany->save();
                 //set credit limit
                 $creditLimit = $this->creditLimitManagement->getCreditByCompanyId($newCompany->getId());
                 $creditLimit->setCreditLimit($data['credit_limit']);
                 $creditLimit->save();

                 if(count($data['company_customers']) > 0) {
                     foreach ($data['company_customers'] as $companyCustomerEmail) {
                         //tie other customers to company
                         $companyCustomer = $this->customer->get(trim($companyCustomerEmail));
                         $this->addCustomerToCompany($newCompany, $companyCustomer);
                         /* add the customer in the tree under the admin user
                         //They may be moved later on if they are part of a team */
                         $this->addToTree($companyCustomer->getId(), $adminCustomer->getId());

                     }

                 }

             }
         }
     }


     /**
      * @param \Magento\Company\Model\Customer\Company $newCompany
      * @param \Magento\Company\Model\Customer\Company $companyCustomer
      */
     private function addCustomerToCompany($newCompany,$companyCustomer){

         //assign to company
         if ($companyCustomer->getExtensionAttributes() !== null
             && $companyCustomer->getExtensionAttributes()->getCompanyAttributes() !== null) {
             $companyAttributes = $companyCustomer->getExtensionAttributes()->getCompanyAttributes();
             $companyAttributes->setCustomerId($companyCustomer->getId());
             $companyAttributes->setCompanyId($newCompany->getId());
             $this->customerResource->saveAdvancedCustomAttributes($companyAttributes);
         }
     }

     /**
      * @param int $customerId
      * @param int $parentId
      */
     private function addToTree($customerId,$parentId){
         $newStruct = $this->structure->create();
         $newStruct->setEntityId($customerId);
         $newStruct->setEntityType(0);
         $newStruct->setParentId($parentId);
         $newStruct->setPath('1/2');
         $newStruct->setLevel(1);
         $newStruct->save();
     }
 }
