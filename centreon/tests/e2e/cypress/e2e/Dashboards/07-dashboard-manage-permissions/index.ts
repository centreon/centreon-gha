import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor';

import dashboards from '../../../fixtures/dashboards/check-permissions/dashboards.json';
import dashboardAdministratorUser from '../../../fixtures/users/user-dashboard-administrator.json';
import dashboardCreatorUser from '../../../fixtures/users/user-dashboard-creator.json';

before(() => {
  cy.startWebContainer();
  cy.execInContainer({
    command: `sed -i 's@"dashboard": 0@"dashboard": 3@' /usr/share/centreon/config/features.json`,
    name: Cypress.env('dockerName')
  });
  cy.executeCommandsViaClapi('resources/clapi/config-ACL/dashboard-share.json');

  cy.loginByTypeOfUser({
    jsonName: dashboardAdministratorUser.login,
    loginViaApi: true
  });
  cy.insertDashboard({ ...dashboards.fromDashboardAdministratorUser });
  cy.logoutViaAPI();
  cy.getByLabel({ label: 'share', tag: 'button' }).click();
  cy.getByLabel({ label: 'Open', tag: 'button' }).click();
  cy.contains(dashboardCreatorUser.login).click();
  cy.getByTestId({ testId: 'role-input' }).eq(0).click();
  cy.get('[role="listbox"]').contains('viewer').click();
  cy.getByTestId({ testId: 'add' }).click();
  cy.getByLabel({ label: 'Update', tag: 'button' }).click();
  cy.logoutViaAPI();
});

beforeEach(() => {
  cy.intercept({
    method: 'GET',
    url: '/centreon/api/internal.php?object=centreon_topology&action=navigationList'
  }).as('getNavigationList');
  cy.intercept({
    method: 'GET',
    url: '/centreon/api/latest/configuration/dashboards?'
  }).as('listAllDashboards');
  cy.intercept({
    method: 'POST',
    url: `/centreon/api/latest/configuration/dashboards/*/access_rights/contacts`
  }).as('addContactToDashboardShareList');
  cy.loginByTypeOfUser({
    jsonName: dashboardAdministratorUser.login,
    loginViaApi: false
  });
});

after(() => {
  cy.stopWebContainer();
});
