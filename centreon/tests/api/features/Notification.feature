Feature:
  In order to check the notifications
  As a logged in user
  I want to manipulate notification using api

  Background:
    Given a running cloud platform instance of Centreon Web API
    And the endpoints are described in Centreon Web API documentation

  Scenario: Notification creation as admin
    Given I am logged in
    And a feature flag "notification" of bitmask 2
    And the following CLAPI import data:
    """
    SG;ADD;service-grp1;service-grp1-alias
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """

    When I send a POST request to '/api/latest/configuration/notifications' with body:
    """
    {
      "name": "notification-name",
      "timeperiod": 2,
      "resources": [
        {
          "type": "hostgroup",
          "events": 5,
          "ids": [53,56],
          "extra": {
            "event_services": 2
          }
        },
        {
          "type": "servicegroup",
          "events": 5,
          "ids": [1,2]
        }
      ],
      "messages": [
        {
          "channel": "Slack",
          "subject": "Hello world !",
          "message": "just a small message"
        }
      ],
      "users": [20,21],
      "is_activated": true
    }
    """
    Then the response code should be "201"
    And the JSON should be equal to:
    """
    {
      "id": 1,
      "name": "notification-name",
      "timeperiod": {
          "id": 1,
          "name": "24x7"
      },
      "users": [
          {
              "id": 20,
              "name": "user-name1"
          },
          {
              "id": 21,
              "name": "user-name2"
          }
      ],
      "resources": [
          {
              "type": "hostgroup",
              "events": 5,
              "ids": [
                  {
                      "id": 53,
                      "name": "Linux-Servers"
                  },
                  {
                      "id": 56,
                      "name": "Printers"
                  }
              ],
              "extra": {
                  "event_services": 2
              }
          },
          {
              "type": "servicegroup",
              "events": 5,
              "ids": [
                  {
                      "id": 1,
                      "name": "service-grp1"
                  },
                  {
                      "id": 2,
                      "name": "service-grp2"
                  }
              ]
          }
      ],
      "messages": [
          {
              "channel": "Slack",
              "subject": "Hello world !",
              "message": "just a small message"
          }
      ],
      "is_activated": true
    }
    """

  Scenario: Notification creation as non-admin
    Given the following CLAPI import data:
    """
    CONTACT;ADD;ala;ala;ala@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;ala;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;GRANTRW;ACL Menu test;1;Configuration;Notifications;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Linux-Servers
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Printers
    ACLGROUP;add;ACL Group test;my alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    ACLGROUP;addcontact;ACL Group test;ala
    SG;ADD;service-grp1;service-grp1-alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-grp1
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I am logged in with "ala"/"Centreon@2022"
    And a feature flag "notification" of bitmask 2
    When I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1,2]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    Then the response code should be "400"

    When I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    Then the response code should be "201"
    And the JSON should be equal to:
      """
      {
        "id": 1,
        "name": "notification-name",
        "timeperiod": {
            "id": 1,
            "name": "24x7"
        },
        "users": [
            {
                "id": 20,
                "name": "ala"
            },
            {
                "id": 21,
                "name": "user-name1"
            }
        ],
        "resources": [
            {
                "type": "hostgroup",
                "events": 5,
                "ids": [
                    {
                        "id": 53,
                        "name": "Linux-Servers"
                    },
                    {
                        "id": 56,
                        "name": "Printers"
                    }
                ],
                "extra": {
                    "event_services": 2
                }
            },
            {
                "type": "servicegroup",
                "events": 5,
                "ids": [
                    {
                        "id": 1,
                        "name": "service-grp1"
                    }
                ]
            }
        ],
        "messages": [
            {
                "channel": "Slack",
                "subject": "Hello world !",
                "message": "just a small message"
            }
        ],
        "is_activated": true
      }
      """

  Scenario: Notification Listing as admin
    Given I am logged in
    And a feature flag "notification" of bitmask 2
    And the following CLAPI import data:
    """
    SG;ADD;service-grp1;service-grp1-alias
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """

    When I send a POST request to '/api/latest/configuration/notifications' with body:
    """
    {
      "name": "notification-name",
      "timeperiod": 1,
      "resources": [
        {
          "type": "hostgroup",
          "events": 5,
          "ids": [53,56],
          "extra": {
            "event_services": 2
          }
        },
        {
          "type": "servicegroup",
          "events": 5,
          "ids": [1,2]
        }
      ],
      "messages": [
        {
          "channel": "Slack",
          "subject": "Hello world !",
          "message": "just a small message"
        }
      ],
      "users": [20,21],
      "is_activated": true
    }
    """
    Then the response code should be "201"

    When I send a GET request to '/api/latest/configuration/notifications'
    Then the response code should be "200"
    And the JSON should be equal to:
    """
    {
      "result": [
        {
          "id": 1,
          "is_activated": true,
          "name": "notification-name",
          "user_count": 2,
          "channels": [
            "Slack"
          ],
          "resources": [
            {
              "type": "hostgroup",
              "count": 2
            },
            {
              "type": "servicegroup",
              "count": 2
            }
          ],
          "timeperiod": {
            "id": 1,
            "name": "24x7"
          }
        }
      ],
      "meta": {
        "page": 1,
        "limit": 10,
        "search": {},
        "sort_by": {},
        "total": 0
      }
    }
    """
  Scenario: Notification Listing as non-admin
    Given the following CLAPI import data:
    """
    CONTACT;ADD;test-user;test-user;test-user@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;test-user;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;GRANTRW;ACL Menu test;1;Configuration;Notifications;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Linux-Servers
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Printers
    ACLGROUP;add;ACL Group test;my alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    ACLGROUP;addcontact;ACL Group test;test-user
    SG;ADD;service-grp1;service-grp1-alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-grp1
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I am logged in with "test-user"/"Centreon@2022"
    And a feature flag "notification" of bitmask 2

    When I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56]
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    Then the response code should be "201"

    When I send a GET request to '/api/latest/configuration/notifications'
    Then the response code should be "200"
    And the JSON should be equal to:
    """
    {
      "result": [
        {
          "id": 1,
          "is_activated": true,
          "name": "notification-name",
          "user_count": 2,
          "channels": [
            "Slack"
          ],
          "resources": [
            {
              "type": "hostgroup",
              "count": 2
            },
            {
              "type": "servicegroup",
              "count": 1
            }
          ],
          "timeperiod": {
            "id": 1,
            "name": "24x7"
          }
        }
      ],
      "meta": {
        "page": 1,
        "limit": 10,
        "search": {},
        "sort_by": {},
        "total": 0
      }
    }
    """

  Scenario: Notification listing as non-admin without sufficient rights
    Given the following CLAPI import data:
    """
    CONTACT;ADD;test-user;test-user;test-user@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;test-user;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;GRANTRW;ACL Menu test;1;Configuration;Notifications;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Linux-Servers
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Printers
    ACLGROUP;add;ACL Group test;my alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    SG;ADD;service-grp1;service-grp1-alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-grp1
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I am logged in
    And a feature flag "notification" of bitmask 2

    When I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56]
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    Then the response code should be "201"

    Given I am logged in with "test-user"/"Centreon@2022"
    When I send a GET request to '/api/latest/configuration/notifications'
    Then the response code should be "403"

  Scenario: Delete notification definition as an admin user
    Given I am logged in
    And a feature flag "notification" of bitmask 2
    And the following CLAPI import data:
    """
    SG;ADD;service-grp1;service-grp1-alias
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """

    And I send a POST request to '/api/latest/configuration/notifications' with body:
    """
    {
      "name": "notification-name",
      "timeperiod": 2,
      "resources": [
        {
          "type": "hostgroup",
          "events": 5,
          "ids": [53,56],
          "extra": {
            "event_services": 2
          }
        },
        {
          "type": "servicegroup",
          "events": 5,
          "ids": [1,2]
        }
      ],
      "messages": [
        {
          "channel": "Slack",
          "subject": "Hello world !",
          "message": "just a small message"
        }
      ],
      "users": [20,21],
      "is_activated": true
    }
    """
    When I send a DELETE request to '/api/latest/configuration/notifications/1'
    Then the response should be "204"

  Scenario: Delete notification definition as a user with sufficient rights
    Given the following CLAPI import data:
    """
    CONTACT;ADD;test-user;test-user;test-user@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;test-user;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;GRANTRW;ACL Menu test;1;Configuration;Notifications;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Linux-Servers
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Printers
    ACLGROUP;add;ACL Group test;my alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    ACLGROUP;addcontact;ACL Group test;test-user
    SG;ADD;service-grp1;service-grp1-alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-grp1
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I am logged in with "test-user"/"Centreon@2022"
    And a feature flag "notification" of bitmask 2
    And I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1,2]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    When I send a DELETE request to '/api/latest/configuration/notifications/1'
    Then the response should be "204"

  Scenario: Delete notification definition as a user with insufficient rights
    Given the following CLAPI import data:
    """
    CONTACT;ADD;test-user;test-user;test-user@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;test-user;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;GRANTRW;ACL Menu test;1;Configuration;Notifications;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Linux-Servers
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Printers
    ACLGROUP;add;ACL Group test;my alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    SG;ADD;service-grp1;service-grp1-alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-grp1
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I am logged in
    And a feature flag "notification" of bitmask 2
    And I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56]
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    And I am logged in with "test-user"/"Centreon@2022"
    When I send a DELETE request to '/api/latest/configuration/notifications/1'
    Then the response code should be "403"

  Scenario: Delete notification definition with ID that does not exist
    Given the following CLAPI import data:
    """
    CONTACT;ADD;test-user;test-user;test-user@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;test-user;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;GRANTRW;ACL Menu test;1;Configuration;Notifications;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Linux-Servers
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Printers
    ACLGROUP;add;ACL Group test;my alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    ACLGROUP;addcontact;ACL Group test;test-user
    SG;ADD;service-grp1;service-grp1-alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-grp1
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I am logged in with "test-user"/"Centreon@2022"
    And a feature flag "notification" of bitmask 2
    And I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1,2]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    When I send a DELETE request to '/api/latest/configuration/notifications/2'
    Then the response should be "404"

  Scenario: Delete multiple notification definitions as admin user
    Given I am logged in
    And a feature flag "notification" of bitmask 2
    And the following CLAPI import data:
    """
    SG;ADD;service-grp1;service-grp1-alias
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1,2]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """

      When I send a POST request to '/api/latest/configuration/notifications/_delete' with body:
      """
      {
        "ids": [1, 2]
      }
      """
      Then the response should be "207"
      And the JSON should be equal to:
      """
        {
          "results": [
            {
              "href": "/configuration/notifications/1",
              "status": 204,
              "message": null
            },
            {
              "href": "/configuration/notifications/2",
              "status": 404,
              "message": "Notification not found"
            }
          ]
        }
      """

  Scenario: Delete multiple notfication definitions as an admin user sending invalid request body
    Given I am logged in
    And a feature flag "notification" of bitmask 2
    And the following CLAPI import data:
    """
    SG;ADD;service-grp1;service-grp1-alias
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1,2]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """

      When I send a POST request to '/api/latest/configuration/notifications/_delete' with body:
      """
      {
        "ids": 1
      }
      """
      Then the response should be "400"
      And the JSON should be equal to:
      """
      {
        "code": 400,
        "message": "[ids] Integer value found, but an array is required\n"
      }
      """

  Scenario: Delete multiple notification definitions as a non-admin user with sufficient rights
    Given the following CLAPI import data:
    """
    CONTACT;ADD;test-user;test-user;test-user@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;test-user;reach_api;1
    ACLMENU;add;ACL Menu test;my alias
    ACLMENU;GRANTRW;ACL Menu test;1;Configuration;Notifications;
    ACLRESOURCE;add;ACL Resource test;my alias
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Linux-Servers
    ACLRESOURCE;grant_hostgroup;ACL Resource test;Printers
    ACLGROUP;add;ACL Group test;my alias
    ACLGROUP;addmenu;ACL Group test;ACL Menu test
    ACLGROUP;addresource;ACL Group test;ACL Resource test
    ACLGROUP;addcontact;ACL Group test;test-user
    SG;ADD;service-grp1;service-grp1-alias
    ACLRESOURCE;grant_servicegroup;ACL Resource test;service-grp1
    SG;ADD;service-grp2;service-grp2-alias
    CONTACT;ADD;user-name1;user-alias1;user1@mail.com;Centreon!2021;0;0;;local
    CONTACT;ADD;user-name2;user-alias2;user2@mail.com;Centreon!2021;0;0;;local
    """
    And I am logged in with "test-user"/"Centreon@2022"
    And a feature flag "notification" of bitmask 2
    And I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53,56],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1,2]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20,21],
        "is_activated": true
      }
      """
    And I send a POST request to '/api/latest/configuration/notifications' with body:
      """
      {
        "name": "notification-name-2",
        "timeperiod": 2,
        "resources": [
          {
            "type": "hostgroup",
            "events": 5,
            "ids": [53],
            "extra": {"event_services": 2}
          },
          {
            "type": "servicegroup",
            "events": 5,
            "ids": [1]
          }
        ],
        "messages": [
          {
            "channel": "Slack",
            "subject": "Hello world !",
            "message": "just a small message"
          }
        ],
        "users": [20],
        "is_activated": true
      }
      """

    When I send a POST request to '/api/latest/configuration/notifications/_delete' with body:
      """
      {
        "ids": [1, 2]
      }
      """
      Then the response should be "207"
      And the JSON should be equal to:
      """
        {
          "results": [
            {
              "href": "/configuration/notifications/1",
              "status": 204,
              "message": null
            },
            {
              "href": "/configuration/notifications/2",
              "status": 204,
              "message": null
            }
          ]
        }
      """

  Scenario: Delete multiple notification definitions as a non-admin user without sufficient rights
    Given the following CLAPI import data:
    """
    CONTACT;ADD;test-user;test-user;test-user@localservice.com;Centreon@2022;0;1;en_US;local
    CONTACT;setparam;test-user;reach_api;1
    """
    And I am logged in with "test-user"/"Centreon@2022"
    And a feature flag "notification" of bitmask 2

    When I send a POST request to '/api/latest/configuration/notifications/_delete' with body:
    """
    {
      "ids": [1, 2]
    }
    """
    Then the response should be "403"
    And the JSON should be equal to:
    """
    {
      "code": 403,
      "message": "You are not allowed to delete a notification configuration"
    }
    """
