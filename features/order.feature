Feature: Order
    In order to buy cool products
    As a web user
    I should be able to add products to cart and checkout

    @javascript
    Scenario: Adding to cart as user
        Given I am logged in as an admin
        And I am on "/"
        When I follow "Products"
        And I select random product
        Then I press "Add to Cart"
        And I should see "Product added!"
        But I should not see "$0"
        Then I fill card field "card-name" with "testing"
        And I fill card field "card-number" with "4242424242424242"
        And I fill card field "card-expiration" with "10/20"
        And I fill card field "card-cvc" with "123"
        Then I press "Checkout"
        And I wait "10000" ms for javascript to process

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
