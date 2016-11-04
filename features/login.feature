Feature: Login
    In order to buy cool products
    As a web user
    I need to be able to login

    Scenario: Login : Valid
        Given there is an admin user "admin_test" with password "admin_test" and email "admin.test@admin.com"
        And I am on "/"
        When I follow "Login"
        And I fill in "Username" with "admin_test"
        And I fill in "Password" with "admin_test"
        And I press "Login"
        Then I should see "Logout"

    Scenario: Login : No user
        Given I am on "/"
        When I follow "Login"
        And I fill in "Username" with "admin12314124124"
        And I fill in "Password" with "admin123"
        And I press "Login"
        Then I should see "Username or Email cound not be found."

    Scenario: Login : Invalid credentials
        Given there is an admin user "admin_test" with password "admin_test" and email "admin.test@admin.com"
        And I am on "/"
        When I follow "Login"
        And I fill in "Username" with "admin_test"
        And I fill in "Password" with "admin"
        And I press "Login"
        Then I should see "Invalid credentials."