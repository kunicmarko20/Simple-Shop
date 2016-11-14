Feature: Subscription
    In order to get cool product pack
    As a web user
    I should be able to subscribe to a plan

    Background:
        Given I am logged in as an admin
        And I am on "/"

    @javascript
    Scenario: Subscribing to pack
        Given I follow "Subscription"
        And I follow "Go Mini!"
        Then I should not see "$0"
        Then I fill card field "card-name" with "testing"
        And I fill card field "card-expiration" with "10/20"
        And I fill card field "card-cvc" with "123"
        And I fill card field "card-number" with "4242424242424242"
        Then I press "Checkout"
        And I wait "10000" ms for javascript to process
        Then I should see "Order Complete"
        And I follow "Account"
        Then I should see "Active"
        And I should see "Visa ending in : 4242"

    Scenario: Canceling subscription
        Given I follow "Account"
        And I press "Cancel Subscription"
        Then I should see "Subscription Cancelled"
        But I should not see "Active"
        And I should see "Cancelled"
        And I should see "n/a"

    Scenario: Reactivating subscription
        Given I follow "Account"
        And I press "Reactivate Subscription"
        Then I should see "Reactivated subscription"
        But I should not see "Cancelled"
        And I should see "Active"
        And I should see "Visa ending in : 4242"




