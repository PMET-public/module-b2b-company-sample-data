<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

 namespace MagentoEse\B2BCompanySampleData\Model;

 use Magento\Company\Api\AclInterface;
 use Magento\Company\Model\CompanyManagement;
 use Magento\Company\Model\CompanyRepository;
 use Magento\Company\Model\StructureFactory;
 use Magento\Company\Model\StructureRepository;
 use Magento\Company\Model\TeamFactory;
 use Magento\Customer\Api\CustomerRepositoryInterface;
 use Magento\Framework\Api\SearchCriteriaBuilder;
 use Magento\Framework\App\ResourceConnection;
 use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
 use Magento\Company\Api\RoleRepositoryInterface;
 use Magento\User\Block\Role;
 use Magento\Company\Api\Data\RoleInterface;

 class Team
 {

     /**
      * @var SampleDataContext
      */
     protected $sampleDataContext;

     /**
      * @var TeamFactory
      */
     protected $teamFactory;

     /**
      * @var StructureRepository
      */
     protected $structureRepository;

     /**
      * @var SearchCriteriaBuilder
      */
     protected $searchCriteriaBuilder;

     /**
      * @var CompanyRepository
      */
     protected $companyRepository;

     /**
      * @var CompanyManagement
      */
     protected $companyManagement;
     /**
      * @var CustomerRepositoryInterface
      */
     protected $customer;

     /**
      * @var AclInterface
      */
     protected $acl;

     /**
      * @var ResourceConnection
      */
     protected $resourceConnection;

     /** @var RoleRepositoryInterface */
     protected $roleRepositoryInterface;
    
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
       * 
       * @var Magento\Company\Model\StructureFactory
       */
      protected $structure;

     /**
      * Team constructor.
      * @param SampleDataContext $sampleDataContext
      * @param TeamFactory $teamFactory
      * @param StructureFactory $structure
      * @param StructureRepository $structureRepository
      * @param SearchCriteriaBuilder $searchCriteriaBuilder
      * @param CompanyRepository $companyRepository
      * @param CompanyManagement $companyManagement
      * @param CustomerRepositoryInterface $customer
      * @param AclInterface $acl
      * @param ResourceConnection $resourceConnection
      */
     public function __construct(
         SampleDataContext $sampleDataContext,
         TeamFactory $teamFactory,
         StructureFactory $structure,
         StructureRepository $structureRepository,
         SearchCriteriaBuilder $searchCriteriaBuilder,
         CompanyRepository $companyRepository,
         CompanyManagement $companyManagement,
         CustomerRepositoryInterface $customer,
         AclInterface $acl,
         ResourceConnection $resourceConnection,
         RoleRepositoryInterface $roleRepositoryInterface
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
         $this->roleRepositoryInterface = $roleRepositoryInterface;
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
                 //get sales role
                 $filter = $this->searchCriteriaBuilder;
                 $filter->addFilter(RoleInterface::ROLE_NAME,'Purchaser','eq');
                 $filter->addFilter(RoleInterface::COMPANY_ID,$company->getId(),'eq');
                 $roleList = $this->roleRepositoryInterface->getList($filter->create())->getItems();
                 foreach($roleList as $role){
                     break;
                 }
                  //loop over team members
                 foreach ($data['members'] as $companyCustomerEmail) {
                     //get user id from email
                     $userId = $this->customer->get(trim($companyCustomerEmail))->getId();
                     //assign role to user
                     $this->acl->assignUserDefaultRole($userId, $company->getId());
                     $this->acl->assignRoles($userId,[$role]);
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
         //$this->enablePurchaseOnCredit();
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
