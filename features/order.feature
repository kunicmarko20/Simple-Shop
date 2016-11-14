Feature: Order
    In order to buy cool products
    As a web user
    I should be able to add products to cart and checkout

    Background:
        Given I am logged in as an admin
        And I am on "/"
        When I follow "Products"
        And I select random product
        Then I press "Add to Cart"
        And I should see "Product added!"
        But I should not see "$0"
        Then I fill card field "card-name" with "testing"
        And I fill card field "card-expiration" with "10/20"
        And I fill card field "card-cvc" with "123"

    @javascript
    Scenario: Adding to cart success
        Given I fill card field "card-number" with "4242424242424242"
        Then I press "Checkout"
        And I wait "10000" ms for javascript to process
        Then I should see "Order Complete"

    @javascript
    Scenario: Card declined
        Given I fill card field "card-number" with "4000000000000002"
        Then I press "Checkout"
        And I wait "10000" ms for javascript to process
        Then I should see "There was a problem charging your card: Your card was declined."

    @javascript
    Scenario: Wrong CVC
        Given I fill card field "card-number" with "4000000000000127"
        Then I press "Checkout"
        And I wait "10000" ms for javascript to process
        Then I should see "There was a problem charging your card: Your card's security code is incorrect."

    @javascript
    Scenario: Expired Card
        Given I fill card field "card-number" with "4000000000000069"
        Then I press "Checkout"
        And I wait "10000" ms for javascript to process
        Then I should see "There was a problem charging your card: Your card has expired."

    @javascript
    Scenario: Proccessing card error
        Given I fill card field "card-number" with "4000000000000119"
        Then I press "Checkout"
        And I wait "10000" ms for javascript to process
        Then I should see "There was a problem charging your card: An error occurred while processing your card. Try again in a little bit."

