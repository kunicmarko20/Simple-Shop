Feature: Product admin
    In order to maintain the products shown on the site
    As an admin
    I need to be able to add/edit/delete products

    Background:
        Given I am logged in as an admin
        Given I am on "/admin/products"

    Scenario: Add a new product
        Given there are 15 products
        When I click "New Product"
        And I fill in "Name" with "Super Random Product"
        And I fill in "Price" with "awdawdawd"
        And I fill in "Description" with "Lorem ipsum testing this"
        And I press "Add"
        Then I should see "This value is not valid."
        And I fill in "Price" with "-20"
        And I press "Add"
        Then I should see "You can't enter negative number."
        And I fill in "Price" with "20"
        And I press "Add"
        Then I should see "Product created"
        And I should see "Super Random Product"
        And I should see 16 products

    Scenario: Deleting a product
        Given I press "Delete" in the "Super Random Product" row
        Then I should see "The product was deleted"
        And I should not see "Super Random Product"
        But I should see 15 products

