Feature: Order
    In order to buy cool products
    As a web user
    I should be able to add products to cart and checkout

    Scenario: Adding to cart as user
        Given I am logged in as an admin
        And I am on "/"
        When I follow "Products"
        And I select random product
        Then I press "Add to Cart"
        And I should see "Product added!"
        But I should not see "$0"

    Scenario: Adding to cart as anonymous
        Given I am on "/"
        When I follow "Products"
        And I select random product
        Then I press "Add to Cart"
        And I should see "Login!"
        Then I fill in "Username" with "admin5"
        And I fill in "Password" with "admin5"
        And I press "Login"
        Then I should be on "/checkout"
        And I should not see "$0"
