@REQ_MON-23902
Feature: this is just a test

Scenario: Logging in test
  When I enter my credentials on the login page
  Then I am redirected to the default page

Scenario: Logging out test
  Given I am logged in
  When I click on the logout action
  Then I am logged out and redirected to the login page