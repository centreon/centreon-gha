  Cypress.Commands.add('addOrUpdateContact', (body: Contact) => {
      cy.wait('@getTimeZone');
      cy.waitForElementInIframe('#main-content', 'input[id="contact_alias"]');
      cy.getIframeBody()
        .find('input[id="contact_alias"]')
        .clear()
        .type(body.alias);
      cy.getIframeBody()
        .find('input[id="contact_name"]')
        .clear()
        .type(body.name);
      cy.getIframeBody()
        .find('input[id="contact_email"]')
        .clear()
        .type(body.email);
      cy.getIframeBody()
        .find('input[id="contact_pager"]')
        .clear()
        .type(body.pager);
      cy.getIframeBody().find('#contact_template_id').select(body.template);
      cy.getIframeBody().contains('label', body.isNotificationsEnabled).click();
      cy.getIframeBody().find('input.btc.bt_success[name^="submit"]').eq(0).click();
      cy.wait('@getTimeZone');
      cy.exportConfig();
  
  });

  Cypress.Commands.add('addOrUpdateContactGroup', (body: ContactGroup) => {
    cy.wait('@getTimeZone');
    cy.waitForElementInIframe('#main-content', 'input[name="cg_name"]');
    cy.getIframeBody()
      .find('input[name="cg_name"]')
      .clear()
      .type(body.name);
    cy.getIframeBody()
      .find('input[name="cg_alias"]')
      .clear()
      .type(body.alias);

    cy.getIframeBody().find('input[class="select2-search__field"]').eq(0).click();
    cy.wait('@getContacts');
    cy.getIframeBody().contains('div', body.linkedContact).click();

    cy.getIframeBody().find('input[class="select2-search__field"]').eq(1).click();
    cy.wait('@getACLGroups');
    cy.getIframeBody().contains('div', 'ALL').click();

    cy.getIframeBody().contains(body.status).click();

    cy.getIframeBody()
      .find('textarea[name="cg_comment"]')
      .clear()
      .type(body.comment);

    cy.getIframeBody().find('input.btc.bt_success[name^="submit"]').eq(1).click();
    cy.wait('@getTimeZone');
    cy.exportConfig();

});
  
  interface Contact {
    alias: string,
    name: string,
    email: string,
    pager: string,
    template: string,
    isNotificationsEnabled: string
  }
  
  interface ContactGroup {
    name: string,
    alias: string,
    linkedContact: string,
    status: string,
    comment: string,
  }

  declare global {
    namespace Cypress {
      interface Chainable {
        addOrUpdateContact: (body: Contact) => Cypress.Chainable;
        addOrUpdateContactGroup: (body: ContactGroup) => Cypress.Chainable;
      }
    }
  }
  
  export {};