Feature: Register
    In order to login and shop
    As a web user
    I need to be able to create personal account

    Background:
        Given there is an admin user "admin_test" with password "admin_test" and email "admin.test@admin.com"
        And I am on "/register"

    Scenario: Register : Unique Fields Fail
        Given I fill in "Username" with "admin_test"
        And I fill in "Email" with "admin.test@admin.com"
        And I press "Register"
        Then I should see "Username already exists."
        And I should see "Email in use."

    Scenario: Register : No Data
        Given I press "Register"
        Then I should see "This value should not be blank."

    Scenario: Register : Password not the same
        Given I fill in "Password" with "admin"
        And I press "Register"
        Then I should see "Password must be the same."

    Scenario: Register : Password too short
        Given I fill in "Password" with "admin"
        And I fill in "Repeat Password" with "admin"
        And I press "Register"
        Then I should see "Your password must be at least 6 characters long."

    Scenario: Register : Valid
        Given I fill in "Username" with "admin_test2"
        And I fill in "Email" with "admin.test2@admin.com"
        And I fill in "Password" with "admin2"
        And I fill in "Repeat Password" with "admin2"
        And I press "Register"
        Then I should see "Welcome admin.test2@admin.com"