@REQ_MON-22028
Feature: List Resources
  As a user
  I want to list the available Resources and filter them
  So that I can handle associated problems quickly and efficiently

  @TEST_MON-22031
  Scenario: Accessing the page for the first time
    Then the unhandled problems filter is selected
    And only non-ok resources are displayed

  @TEST_MON-22029
  Scenario: Filtering Resources through criterias
    When I put in some criterias
    Then only the Resources matching the selected criterias are displayed in the result

  @TEST_MON-22030
  Scenario: Selecting filters
    Given a saved custom filter
    When I select the custom filter
    Then only Resources matching the selected filter are displayed in the result

  @TEST_MON-156875
  Scenario: Selecting critical services custom filter
    Given a saved critical service filter
    When I select the critical service filter
    Then only the critical services are displayed in the result

  @TEST_MON-156876
  Scenario: Selecting pending hosts custom filter
    Given a saved pending host filter
    When I select the pending host filter
    Then only the pending hosts are displayed in the result

  @TEST_MON-22030
  Scenario: Selecting up hosts custom filter
    Given a saved up host filter
    When I select the up host filter
    Then only the up hosts are displayed in the result