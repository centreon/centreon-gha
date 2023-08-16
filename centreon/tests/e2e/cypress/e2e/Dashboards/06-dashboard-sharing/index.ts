import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor';

import dashboards from '../../../fixtures/dashboards/check-permissions/dashboards.json';
import dashboardAdministratorUser from '../../../fixtures/users/user-dashboard-administrator.json';
import dashboardCreatorUser from '../../../fixtures/users/user-dashboard-creator.json';
import dashboardViewerUser from '../../../fixtures/users/user-dashboard-viewer.json';

before(() => {
  cy.startWebContainer({ version: 'develop' });
  cy.execInContainer({
    command: `sed -i 's@"dashboard": 0@"dashboard": 3@' /usr/share/centreon/config/features.json`,
    name: Cypress.env('dockerName')
  });
  cy.executeCommandsViaClapi(
    'resources/clapi/config-ACL/dashboard-check-permissions.json'
  );
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
    url: '/centreon/api/latest/configuration/dashboards'
  }).as('createDashboard');
  cy.loginByTypeOfUser({
    jsonName: dashboardAdministratorUser.login,
    loginViaApi: true
  });
  cy.insertDashboard({ ...dashboards.fromDashboardAdministratorUser });
  cy.logoutViaAPI();
  cy.loginByTypeOfUser({
    jsonName: dashboardCreatorUser.login,
    loginViaApi: true
  });
  cy.insertDashboard({ ...dashboards.fromDashboardCreatorUser });
  cy.shareDashboardToUser({
    dashboardName: dashboards.fromDashboardCreatorUser.name,
    role: 'viewer',
    userName: dashboardViewerUser.login
  });
  cy.logoutViaAPI();
});

after(() => {
  cy.stopWebContainer();
});

afterEach(() => {
  cy.visit(`${Cypress.config().baseUrl}/centreon/home/dashboards`);
  cy.requestOnDatabase({
    database: 'centreon',
    query: 'DELETE FROM dashboard'
  });
  cy.logout();
});

Given('a non-admin user who is on a list of shared dashboards', () => {
  cy.loginByTypeOfUser({
    jsonName: 'admin',
    loginViaApi: false
  });
  cy.visit(`${Cypress.config().baseUrl}/centreon/home/dashboards`);
});

When('the user selects the share option on a dashboard', () => {
  cy.getByLabel({
    label: 'view',
    tag: 'button'
  })
    .contains(dashboards.fromDashboardAdministratorUser.name)
    .click();
  cy.getByLabel({ label: 'share', tag: 'button' }).click();
});

Then('the user is redirected to the sharing list of the dashboard', () => {
  cy.contains('Manage access rights').should('be.visible');
  cy.get('*[class^="MuiList-root"]').eq(4).should('exist');
});

Then('the creator of the dashboard is listed as its sole editor', () => {
  cy.get('*[class^="MuiList-root"]').eq(4).its('length').should('eq', 1);
  cy.get('*[class^="MuiList-root"]')
    .eq(4)
    .children()
    .eq(0)
    .should('contain', `${dashboardAdministratorUser.login}`);
  cy.getByTestId({ testId: 'role-input' })
    .eq(1)
    .should('contain.text', 'editor');
});
