<?php

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\PyStringNode;
use Behat\Gherkin\Node\TableNode;
use Behat\MinkExtension\Context\MinkContext;
use AppBundle\Entity\User;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;

require_once __DIR__.'/../../vendor/phpunit/phpunit/src/Framework/Assert/Functions.php';

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
     * @BeforeScenario @fixtures
     */
    public function clearData()
    {
        $purger = new ORMPurger($this->getContainer()->get('doctrine')->getManager());
        $purger->purge();
    }
    
    /**
     * Saving a screenshot
     *
     * @When I save a screenshot to :filename
     */
    public function iSaveAScreenshotIn($filename)
    {
        sleep(1);
        $this->saveScreenshot($filename, __DIR__.'/../..');
    }
     /**
     * @BeforeScenario @fixtures
     */
    public function loadFixtures()
    {
        $loader = new ContainerAwareLoader($this->getContainer());
        $loader->loadFromDirectory(__DIR__.'/../../src/AppBundle/DataFixtures');
        $executor = new ORMExecutor($this->getEntityManager());
        $executor->execute($loader->getFixtures(), true);
    }
    /**
     * @return \Doctrine\ORM\EntityManager
    */
    private function getEntityManager()
    {
        return $this->getContainer()->get('doctrine.orm.entity_manager');
    }
    
    /**
     * @When I select random product
     */
    public function iSelectRandomProduct()
    {
        $button = $this->assertSession()
            ->elementExists('css', '.shear-product > a');

        $button->press();
    }
    
    /**
     * @return \Behat\Mink\Element\DocumentElement
     */
    private function getPage()
    {
        return $this->getSession()->getPage();
    }
    
    /**
     * @Given I am logged in as an admin
     */
    public function iAmLoggedInAsAnAdmin()
    {

        $this->visitPath('/login');
        $this->getPage()->fillField('Username', 'admin1');
        $this->getPage()->fillField('Password', 'admin1');
        $this->getPage()->pressButton('Login');
    }
    
    /**
     * @Given there are :number products
     */
    public function thereAreProducts($number)
    {
        $table = $this->getPage()->find('css', 'table.table');
        assertNotNull($table, 'Cannot find a table!');

        assertCount(intval($number), $table->findAll('css', 'tbody tr'));
    }
    
    /**
     * @Then /^I fill card field "([^"]*)" with "([^"]*)"$/
     */
    public function iFillCardFieldWith($field, $value)
    {
        $javascript = "document.getElementById('$field').value='$value'";
        $this->getSession()->executeScript($javascript);
    }

    /**
     * @Then I wait :number ms for javascript to process
     */
    public function iWaitMsForJavascriptToProcess($number)
    {
        $this->getSession()->wait(
            $number);
    }

    /**
     * @Then I should see :number products
     */
    public function iShouldSeeProducts($number)
    {
        $this->thereAreProducts($number);
    }
    /**
     * @Given I press :linkText in the :rowText row
     */
    public function iPressInTheRow($linkText, $rowText)
    {
        $this->findRowByText($rowText)->pressButton($linkText);
    }

    
    /**
     * @param $rowText
     * @return \Behat\Mink\Element\NodeElement
     */
    private function findRowByText($rowText)
    {
        $row = $this->getPage()->find('css', sprintf('table tr:contains("%s")', $rowText));
        assertNotNull($row, 'Cannot find a table row with this text!');

        return $row;
    }
    
    /**
     * @When I click :linkName
     */
    public function iClick($linkName)
    {
        $this->getPage()->clickLink($linkName);
    }
}
