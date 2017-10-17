<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
  
 namespace MagentoEse\B2BCompanySampleData\Model;

 use Magento\Framework\Setup\SampleData\Context as SampleDataContext;


 class Team
 {

     /**
      * @var SampleDataContext
      */
     protected $sampleDataContext;

     /**
      * @var \Magento\Company\Model\TeamFactory
      */
     protected $teamFactory;

     /**
      * @var \Magento\Company\Model\StructureRepository
      */
     protected $structureRepository;

     /**
      * @var \Magento\Framework\Api\SearchCriteriaBuilder
      */
     protected $searchCriteriaBuilder;

     /**
      * @var \Magento\Company\Model\CompanyRepository
      */
     protected $companyRepository;

     /**
      * @var \Magento\Company\Model\CompanyManagement
      */
     protected $companyManagement;
     /**
      * @var \Magento\Customer\Api\CustomerRepositoryInterface
      */
     protected $customer;

     /**
      * @var \Magento\Company\Api\AclInterface
      */
     protected $acl;

     /**
      * @var \Magento\Framework\App\ResourceConnection
      */
     protected $resourceConnection;

     /**
      * Team constructor.
      * @param SampleDataContext $sampleDataContext
      * @param \Magento\Company\Model\TeamFactory $teamFactory
      * @param \Magento\Company\Model\StructureFactory $structure
      * @param \Magento\Company\Model\StructureRepository $structureRepository
      * @param \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder
      * @param \Magento\Company\Model\CompanyRepository $companyRepository
      * @param \Magento\Company\Model\CompanyManagement $companyManagement
      * @param \Magento\Customer\Api\CustomerRepositoryInterface $customer
      * @param \Magento\Company\Api\AclInterface $acl
      * @param \Magento\Framework\App\ResourceConnection $resourceConnection
      */
     public function __construct(
         SampleDataContext $sampleDataContext,
         \Magento\Company\Model\TeamFactory $teamFactory,
         \Magento\Company\Model\StructureFactory $structure,
         \Magento\Company\Model\StructureRepository $structureRepository,
         \Magento\Framework\Api\SearchCriteriaBuilder $searchCriteriaBuilder,
         \Magento\Company\Model\CompanyRepository $companyRepository,
         \Magento\Company\Model\CompanyManagement $companyManagement,
         \Magento\Customer\Api\CustomerRepositoryInterface $customer,
         \Magento\Company\Api\AclInterface $acl,
        \Magento\Framework\App\ResourceConnection $resourceConnection
     )
     {
         $this->fixtureManager = $sampleDataContext->getFixtureManager();
         $this->csvReader = $sampleDataContext->getCsvReader();
         $this->teamFactory = $teamFactory;
         $this->structure = $structure;
         $this->structureRepository = $structureRepository;
         $this->searchCriteriaBuilder = $searchCriteriaBuilder;
         $this->companyRepository = $companyRepository;
         $this->companyManagement = $companyManagement;
         $this->customer = $customer;
         $this->acl = $acl;
         $this->resourceConnection = $resourceConnection;
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
                 $data['members'] = explode(",", $data['members']);
                 //create array from members addresses
                 // Create Team
                 $newTeam = $this->teamFactory->create();
                 $newTeam->setName($data['name']);
                 $newTeam->save();

                 //get company by name
                 $company = $this->getCompanyByName($data['company_name']);
                 //get admin user id
                 $adminUserId = $this->companyManagement->getAdminByCompanyId($company->getId())->getId();
                 //get admins structure
                 $parentId = $this->getStructureByEntity($adminUserId,0)->getDataByKey('structure_id');
                 $teamId =($newTeam->getId());
                 //put team under admin users
                 $teamStruct = $this->addTeamToTree($teamId,$parentId);

                 //loop over team members
                 foreach ($data['members'] as $companyCustomerEmail) {
                     //get user id from email
                     $userId = $this->customer->get(trim($companyCustomerEmail))->getId();
                     //assign role to user
                     $this->acl->assignUserDefaultRole($userId, $company->getId());

                     //delete structure that the user belongs to
                     $userStruct = $this->getStructureByEntity($userId,0);
                     if($userStruct){
                         $structureId = $userStruct->getDataByKey('structure_id');
                         $this ->structureRepository->deleteById($structureId);
                     }

                     //add them to the new team
                     $this->addUserToTeamTree($userId,$teamStruct->getId(),$teamStruct->getPath());

                 }

             }

         }
         $this->enablePurchaseOnCredit();
     }

     private function enablePurchaseOnCredit(){
         //this is not the proper method, but was done in interest of deadline
         $connection = $this->resourceConnection->getConnection();
         $tableName = $connection->getTableName('company_permissions');
         $sql = "update " . $tableName . " set permission = 'allow' where resource_id = 'Magento_Sales::payment_account'";
         $connection->query($sql);
      }

     /**
      * @param int $teamId
      * @param int $parentId
      * @return \Magento\Company\Model\Structure
      */
     private function addTeamToTree($teamId,$parentId){
         //path is structure_id of admin user / structure_id of team)
         $newStruct = $this->structure->create();
         $newStruct->setEntityId($teamId);
         $newStruct->setEntityType(1);
         $newStruct->setParentId($parentId);
         //$newStruct->setPath('1/2');
         $newStruct->setLevel(1);
         $newStruct->save();
         $newStruct->setPath($parentId.'/'.$newStruct->getId());
         $newStruct->save();
         return $newStruct;
     }

     /**
      * @param int $userId
      * @param int $parentId
      * @param string $path
      * @return \Magento\Company\Model\Structure
      */
     private function addUserToTeamTree($userId,$parentId,$path){
         $newStruct = $this->structure->create();
         $newStruct->setEntityId($userId);
         $newStruct->setEntityType(0);
         $newStruct->setParentId($parentId);
         //$newStruct->setPath('1/3');
         $newStruct->setLevel(2);
         $newStruct->save();
         $newStruct->setPath($path.'/'.$newStruct->getId());
         $newStruct->save();
         return $newStruct;
     }

     /**
      * @param string $companyName
      * @return \Magento\Company\Api\Data\CompanyInterface|mixed
      */
     private function getCompanyByName($companyName){
         $builder = $this->searchCriteriaBuilder;
         $builder->addFilter('company_name', $companyName);
         $companyStructures = $this->companyRepository->getList($builder->create())->getItems();
         //$companyId = reset($companyStructures)->getDataByKey('entity_id');
         return reset($companyStructures);
     }

     /**
      * @param $entityId
      * @param $entityType
      * @return \Magento\Company\Api\Data\StructureInterface|mixed
      */
     private function getStructureByEntity($entityId,$entityType){
         $builder = $this->searchCriteriaBuilder;
         $builder->addFilter('entity_id', $entityId);
         $builder->addFilter('entity_type',$entityType);
         $structures = $this->structureRepository->getList($builder->create())->getItems();
         return reset($structures);
     }

 }
