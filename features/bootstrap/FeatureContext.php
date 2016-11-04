<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
/**
 * Defines application features from the specific context.
 */
class FeatureContext extends MinkContext implements Context
{
    use \Behat\Symfony2Extension\Context\KernelDictionary;
    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }
    /**
     * @BeforeScenario
     */
    public function clearData()
    {
        $purger = new ORMPurger($this->getContainer()->get('doctrine')->getManager());
        $purger->purge();
    }
    /**
     * @Given there is an admin user :username with password :password and email :email
     */
    public function thereIsAnAdminUserWithPasswordAndEmail($username, $password, $email)
    {
        $user = new User();
        $user->setUsername($username);
        $user->setEmail($email);
        $user->setPlainPassword($password);
        $user->setRoles(['ROLE_ADMIN']);
        $em = $this->getEntityManager();
        $em->persist($user);
        $em->flush();

        return $user;
    }
    /**
     * @return \Doctrine\ORM\EntityManager
    */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
}
