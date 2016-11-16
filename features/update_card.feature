Feature: Card
    If I continue using this shop
    As a web user
    I should be able to update my credit card info

    Background:
        Given I am logged in as an admin
        And I am on "/"
        When I follow "Account"
        And I press "Update Card Info"
        Then I fill card field "card-name" with "testing"
        And I fill card field "card-expiration" with "10/20"
        And I fill card field "card-cvc" with "123"

    @javascript
    Scenario: Update Card
        Given I fill card field "card-number" with "4242424242424242"
        Then I press "Update Card"
        And I wait "5000" ms for javascript to process
        Then I should see "Card updated!"

    @javascript
    Scenario: Card update declined
        Given I fill card field "card-number" with "4000000000000002"
        Then I press "Update Card"
        And I wait "5000" ms for javascript to process
        Then I should see "There was a problem charging your card: Your card was declined."

