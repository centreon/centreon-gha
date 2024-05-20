/* eslint-disable import/no-unresolved */
/* eslint-disable cypress/unsafe-to-chain-command */
/* eslint-disable newline-before-return */
/* eslint-disable @typescript-eslint/no-namespace */

import 'cypress-wait-until';
import '@centreon/js-config/cypress/e2e/commands';
import { refreshButton } from '../features/Resources-status/common';
import '../features/Dashboards/commands';

Cypress.Commands.add('refreshListing', (): Cypress.Chainable => {
  return cy.get(refreshButton).click();
});

Cypress.Commands.add('disableListingAutoRefresh', (): Cypress.Chainable => {
  return cy.getByTestId({ testId: 'Disable autorefresh' }).click();
});

Cypress.Commands.add('removeResourceData', (): Cypress.Chainable => {
  return cy.executeActionViaClapi({
    bodyContent: {
      action: 'DEL',
      object: 'HOST',
      values: 'test_host'
    }
  });
});

Cypress.Commands.add('loginKeycloak', (jsonName: string): Cypress.Chainable => {
  cy.fixture(`users/${jsonName}.json`).then((credential) => {
    cy.get('#username').type(`{selectall}{backspace}${credential.login}`);
    cy.get('#password').type(`{selectall}{backspace}${credential.password}`);
  });

  return cy.get('#kc-login').click();
});

Cypress.Commands.add(
  'isInProfileMenu',
  (targetedMenu: string): Cypress.Chainable => {
    cy.get('header svg[aria-label="Profile"]').click();

    return cy.get('div[role="tooltip"]').contains(targetedMenu);
  }
);

Cypress.Commands.add('removeACL', (): Cypress.Chainable => {
  return cy.setUserTokenApiV1().then(() => {
    cy.executeActionViaClapi({
      bodyContent: {
        action: 'DEL',
        object: 'ACLMENU',
        values: 'acl_menu_test'
      }
    });
    cy.executeActionViaClapi({
      bodyContent: {
        action: 'DEL',
        object: 'ACLGROUP',
        values: 'ACL Group test'
      }
    });
  });
});

Cypress.Commands.add('rewriteHeaders', () => {
  cy.intercept('*', (req) =>
    req.on('response', (res) => {
      const setCookies = res.headers['set-cookie'];
      res.headers['set-cookie'] = (
        Array.isArray(setCookies) ? setCookies : [setCookies]
      )
        .filter((x) => x)
        .map((headerContent) =>
          headerContent.replace(
            /samesite=(lax|strict)/gi,
            'secure; samesite=none'
          )
        );
    })
  );
});

declare global {
  namespace Cypress {
    interface Chainable {
      disableListingAutoRefresh: () => Cypress.Chainable;
      isInProfileMenu: (targetedMenu: string) => Cypress.Chainable;
      loginKeycloak: (jsonName: string) => Cypress.Chainable;
      refreshListing: () => Cypress.Chainable;
      removeACL: () => Cypress.Chainable;
      removeResourceData: () => Cypress.Chainable;
      rewriteHeaders: () => Cypress.Chainable;
      startOpenIdProviderContainer: () => Cypress.Chainable;
      stopOpenIdProviderContainer: () => Cypress.Chainable;
    }
  }
}
