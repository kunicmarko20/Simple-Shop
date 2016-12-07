Feature: Login
    In order to buy cool products
    As a web user
    I need to be able to login

    Background:
        Given I am on "/"
        When I follow "Login"
    @javascript
    Scenario: Login : Valid
        Given I fill in "Username" with "admin1"
        And I fill in "Password" with "admin1"
        And I press "Login"
        And I wait "3000" ms for javascript to process
        Then I should see "Logout"
    @javascript
    Scenario: Login : No user
        Given I fill in "Username" with "admin12314124124"
        And I fill in "Password" with "admin123"
        And I press "Login"
        And I wait "3000" ms for javascript to process
        Then I should see "Username or Email cound not be found."
    @javascript
    Scenario: Login : Invalid credentials
        Given I fill in "Username" with "admin1"
        And I fill in "Password" with "admin"
        And I press "Login"
        And I wait "3000" ms for javascript to process
        Then I should see "Invalid credentials."