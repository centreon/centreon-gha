Feature:
  In order to monitor services by groups
  As a user
  I want to get service group information using api

  Background:
    Given a running instance of Centreon Web API
    And the endpoints are described in Centreon Web API documentation

  Scenario: Service group listing with an Administrator
    Given I am logged in
    And the following CLAPI import data:
    """
    SG;ADD;Test Service Group;Alias Test service group
    """

    When I send a GET request to '/api/latest/configuration/services/groups?search={"name": {"$eq": "Test Service Group"}}'
    Then the response code should be "200"
    And the JSON should be equal to:
    """
    {
        "result": [
            {
                "id": 1,
                "name": "Test Service Group",
                "alias": "Alias Test service group",
                "geo_coords": null,
                "comment": null,
                "is_activated": true
            }
        ],
        "meta": {
            "page": 1,
            "limit": 10,
            "search": {
                "$and": {"name": {"$eq": "Test Service Group"}}
            },
            "sort_by": {},
            "total": 1
        }
    }
    """

  Scenario: Service group listing with an Administrator and a disabled service group
    Given I am logged in
    And the following CLAPI import data:
    """
    SG;ADD;Test Service disabled;Alias Test service group
    SG;setparam;Test Service disabled;activate;0
    """

    When I send a GET request to '/api/latest/configuration/services/groups?search={"is_activated": false}'
    Then the response code should be "200"
    And the JSON should be equal to:
    """
    {
        "result": [
            {
                "id": 1,
                "name": "Test Service disabled",
                "alias": "Alias Test service group",
                "geo_coords": null,
                "comment": null,
                "is_activated": false
            }
        ],
        "meta": {
            "page": 1,
            "limit": 10,
            "search": {
                "$and": {"is_activated": false}
            },
            "sort_by": {},
            "total": 1
        }
    }
    """

  Scenario: Service group listing with a READ user
    Given the following CLAPI import data:
    """
    SG;ADD;service-group1;service-group1-alias
    SG;ADD;service-group2;service-group2-alias
    CONTACT;ADD;abu;abu;abu@centreon.test;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;abu;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;grantro;ACL Menu test;0;Configuration;Services;
    ACLMENU;grantro;ACL Menu test;0;Configuration;Services;Service Groups;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-group1
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-group2
    ACLGROUP;add;ACL Group test;ACL Group test alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    ACLGROUP;setcontact;ACL Group test;abu;
    """
    And I am logged in with "abu"/"Centreon@2022"

    When I send a GET request to '/api/latest/configuration/services/groups?search={"name": {"$lk": "service-group%"}}'
    Then the response code should be "200"
    And the JSON should be equal to:
    """
    {
        "result": [
            {
                "id": 1,
                "name": "service-group1",
                "alias": "service-group1-alias",
                "geo_coords": null,
                "comment": null,
                "is_activated": true
            },
            {
                "id": 2,
                "name": "service-group2",
                "alias": "service-group2-alias",
                "geo_coords": null,
                "comment": null,
                "is_activated": true
            }
        ],
        "meta": {
            "page": 1,
            "limit": 10,
            "search": {
                "$and": {"name": {"$lk": "service-group%"}}
            },
            "sort_by": {},
            "total": 2
        }
    }
    """

  Scenario: Service group listing with a READ_WRITE user
    Given the following CLAPI import data:
    """
    SG;ADD;service-group1;service-group1-alias
    SG;ADD;service-group2;service-group2-alias
    CONTACT;ADD;abu;abu;abu@centreon.test;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;abu;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;grantrw;ACL Menu test;0;Configuration;Services;
    ACLMENU;grantrw;ACL Menu test;0;Configuration;Services;Service Groups;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-group1
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-group2
    ACLGROUP;add;ACL Group test;ACL Group test alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    ACLGROUP;setcontact;ACL Group test;abu;
    """
    And I am logged in with "abu"/"Centreon@2022"

    When I send a GET request to '/api/latest/configuration/services/groups?search={"name": {"$lk": "service-group%"}}'
    Then the response code should be "200"
    And the JSON should be equal to:
    """
    {
        "result": [
            {
                "id": 1,
                "name": "service-group1",
                "alias": "service-group1-alias",
                "geo_coords": null,
                "comment": null,
                "is_activated": true
            },
            {
                "id": 2,
                "name": "service-group2",
                "alias": "service-group2-alias",
                "geo_coords": null,
                "comment": null,
                "is_activated": true
            }
        ],
        "meta": {
            "page": 1,
            "limit": 10,
            "search": {
                "$and": {"name": {"$lk": "service-group%"}}
            },
            "sort_by": {},
            "total": 2
        }
    }
    """
