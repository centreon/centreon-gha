Feature: Create a new Additional Connector Configuration
  As a Centreon user
  I want to visit the Specific Connector Configuration page
  To manage additional connector configuration

  Scenario: Add an additional connector configuration with all informations
    Given a non-admin user is in the Specific Connector Configuration page
    When the user clicks on Add
    Then a pop-up menu with the form is displayed
    When the user fills in all the informations
    And the user clicks on Create
    Then the first connector is displayed in the Specific Connector Configuration page
#    And the parameters should be saved in vault

  Scenario: Add an additional connector configuration with mandatory informations
    Given a non-admin user is in the Specific Connector Configuration page
    When the user clicks on Add
    Then a pop-up menu with the form is displayed
    When the user fills in the mandatory informations
    And the user clicks on Create
    Then the second configuration is displayed in the Specific Connector Configuration page
#    And the parameters should be saved in vault

  Scenario: Add an additional connector configuration with multiple parameter groups
    Given a non-admin user is in the Specific Connector Configuration page
    When the user clicks on Add
    Then a pop-up menu with the form is displayed
    When the user clicks to add a second vCenter
    Then a second group of parameters is displayed
    When the user fills in the informations of all the parameter groups
    And the user clicks on Create
    Then the third configuration is displayed in the Specific Connector Configuration page
#    And the parameters should be saved in vault

  Scenario: Add an additional connector configuration with missing informations
    Given a non-admin user is in the Specific Connector Configuration page
    When the user clicks on Add
    And the user doesn't fill in all the mandatory informations
    Then the user cannot click on Create

  Scenario: Add an additional connector configuration with incorrect informations
    Given a non-admin user is in the Specific Connector Configuration page
    When the user clicks on Add
    And the user doesn't fill in correct type of informations
    Then the form displayed an error

  Scenario: Cancel a creation form
    Given a non-admin user is in the Specific Connector Configuration page
    When the user clicks on Add
    And the user fills in the needed informations
    And the user clicks on the Cancel button of the creation form
    Then a pop-up appears to confirm cancellation
    And the user confirms the the cancellation
    Then the creation form is closed
    And the additional connector configuration has not been created
    When the user clicks on Add
    Then the form fields are empty