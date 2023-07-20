import { Given, When, Then } from '@badeball/cypress-cucumber-preprocessor';

import dashboards from '../../../fixtures/dashboards/check-permissions/dashboards.json';

before(() => {
  cy.startWebContainer({ version: 'develop' });
  /* cy.execInContainer({
    command: `sed -i 's@"dashboard": 0@"dashboard": 3@' /usr/share/centreon/config/features.json`,
    name: Cypress.env('dockerName')
  });
  cy.executeCommandsViaClapi(
    'resources/clapi/config-ACL/dashboard-check-permissions.json'
  ); */
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
  cy.loginByTypeOfUser({
    jsonName: 'user-dashboard-administrator',
    loginViaApi: true
  });
  cy.insertDashboard({ ...dashboards.fromAdministratorUser });
  cy.loginByTypeOfUser({
    jsonName: 'user-dashboard-creator',
    loginViaApi: true
  });
  cy.insertDashboard({ ...dashboards.fromCreatorUser });
  cy.shareDashboardToUser({
    dashboardName: dashboards.fromCreatorUser.name,
    role: 'viewer',
    userName: 'user-dashboard-viewer'
  });
});

/* after(() => {
  cy.stopWebContainer();
}); */

afterEach(() => {
  cy.requestOnDatabase({
    database: 'centreon',
    query: 'DELETE FROM dashboard'
  });
});

Given('an admin user is logged in on a platform with dashboards', () => {
  cy.loginByTypeOfUser({
    jsonName: 'admin',
    loginViaApi: true
  });
});

When('the admin user accesses the dashboard library', () => {
  cy.visit(`${Cypress.config().baseUrl}/centreon/home/dashboards`);
});

Then(
  'the admin user can view all the dashboards configured on the platform',
  () => {
    cy.getByLabel({
      label: 'view',
      tag: 'button'
    })
      .contains(dashboards.fromAdministratorUser.name)
      .should('exist');
  }
);

When('the admin user clicks on a dashboard', () => {
  cy.getByLabel({
    label: 'view',
    tag: 'button'
  })
    .contains(dashboards.fromAdministratorUser.name)
    .click();
});

Then(
  'the admin user is redirected to the detail page for this dashboard',
  () => {
    cy.location('pathname')
      .should('include', '/dashboards/')
      .invoke('split', '/')
      .should('not.be.empty')
      .then(Cypress._.last)
      .then(Number)
      .should('not.be', 'dashboards')
      .should('be.a', 'number');

    cy.getByLabel({ label: 'page header title' }).should(
      'contain.text',
      dashboards.fromAdministratorUser.name
    );
  }
);

Then(
  'the admin user is allowed to access the edit mode for this dashboard',
  () => {
    cy.get('button[data-testid="edit_dashboard"]').click();
    cy.location('search').should('include', 'edit=true');
    cy.getByLabel({ label: 'add widget', tag: 'button' }).should('be.enabled');
    cy.getByLabel({ label: 'Exit', tag: 'button' }).click();
  }
);

Then("the admin user is allowed to update the dashboard's properties", () => {
  cy.getByLabel({ label: 'edit', tag: 'button' }).click();

  cy.getByLabel({ label: 'Name', tag: 'input' }).clear();
  cy.getByLabel({ label: 'Name', tag: 'input' }).type(
    `${dashboards.fromAdministratorUser.name}-edited`
  );
  cy.getByLabel({ label: 'Description', tag: 'textarea' }).clear();
  cy.getByLabel({ label: 'Description', tag: 'textarea' }).type(
    `${dashboards.fromAdministratorUser.description} and admin`
  );

  cy.getByLabel({ label: 'Update', tag: 'button' }).should('be.enabled');
  cy.getByLabel({ label: 'Update', tag: 'button' }).click();

  cy.reload();
  cy.contains(`${dashboards.fromAdministratorUser.name}-edited`).should(
    'exist'
  );
  cy.contains(
    `${dashboards.fromAdministratorUser.description} and admin`
  ).should('exist');
});
