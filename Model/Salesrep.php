<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
  
 namespace MagentoEse\B2BCompanySampleData\Model;

 use Magento\Framework\Setup\SampleData\Context as SampleDataContext;
 use Magento\Authorization\Model\Acl\Role\Group as RoleGroup;
 use Magento\Authorization\Model\UserContextInterface;

 class Salesrep
 {

     /**
      * @var SampleDataContext
      */
     protected $sampleDataContext;

     /**
      * @var \Magento\User\Model\UserFactory
      */
     protected $user;

     /**
      * @var \Magento\Authorization\Model\RoleFactory
      */
     protected $roleFactory;

     /**
      * @var \Magento\Authorization\Model\RulesFactory
      */
     protected $rulesFactory;

     /**
      * Salesrep constructor.
      * @param SampleDataContext $sampleDataContext
      * @param \Magento\User\Model\UserFactory $user
      * @param \Magento\Authorization\Model\RoleFactory $roleFactory
      * @param \Magento\Authorization\Model\RulesFactory $rulesFactory
      */
     public function __construct(
         SampleDataContext $sampleDataContext,
         \Magento\User\Model\UserFactory $user,
         \Magento\Authorization\Model\RoleFactory $roleFactory,
         \Magento\Authorization\Model\RulesFactory $rulesFactory
     )
     {
         $this->fixtureManager = $sampleDataContext->getFixtureManager();
         $this->csvReader = $sampleDataContext->getCsvReader();
         $this->user = $user;
         $this->roleFactory = $roleFactory;
         $this->rulesFactory = $rulesFactory;
     }

     /**
      * @param array $fixtures
      */
     public function install(array $fixtures)
     {
         /**
          * Create Salesrep role
          */
         $role=$this->roleFactory->create();
         $role->setName('Sales Rep') //Set Role Name Which you want to create
         ->setPid(0) //set parent role id of your role
         ->setRoleType(RoleGroup::ROLE_TYPE)
             ->setUserType(UserContextInterface::USER_TYPE_ADMIN);
         $role->save();

         /* Add resources we allow to this role */
         $resource=['Magento_Backend::admin',
             'Magento_Sales::sales'
         ];
         //save resources to role
         $this->rulesFactory->create()->setRoleId($role->getId())->setResources($resource)->saveRel();

        foreach ($fixtures as $fileName) {
             $fileName = $this->fixtureManager->getFixture($fileName);
             if (!file_exists($fileName)) {
                 continue;
             }
             $rows = $this->csvReader->getData($fileName);
             $header = array_shift($rows);
             foreach ($rows as $row) {
                 $data = [];
                 foreach ($row as $key => $value) {
                     $data[$header[$key]] = $value;
                 }
                 $salesRep = $this->user->create();
                 $salesRep->setEmail($data['email']);
                 $salesRep->setFirstName($data['firstname']);
                 $salesRep->setLastName($data['lastname']);
                 $salesRep->setUserName($data['username']);
                 $salesRep->setPassword($data['password']);
                 $salesRep->save();
                 $userRole=$this->roleFactory->create();
                 // add role for user
                 //$userRole->setParentId($role->getId());
                 $userRole->setParentId(1);
                 $userRole->setTreeLevel(1);
                 $userRole->setRoleType('U');
                 $userRole->setUserId($salesRep->getId());
                 $userRole->setUserType(2);
                 $userRole->setRoleName($salesRep->getUserName());
                 $userRole->save();
             }
         }
     }
 }
