Cypress.Commands.add('addOrUpdateVirtualMetric', (body: VirtualMetric) => {
    cy.wait('@getTimeZone');
    cy.waitForElementInIframe('#main-content', 'input[name="vmetric_name"]');
    cy.getIframeBody()
      .find('input[name="vmetric_name"]')
      .clear()
      .type(body.name);
    cy.getIframeBody()
      .find('span[id="select2-host_id-container"]')
      .click();
    cy.getIframeBody()
      .find(`div[title='${body.linkedHostServices}']`)
      .click();
    cy.getIframeBody()
      .find('textarea[name="rpn_function"]')
      .clear();
    cy.getIframeBody()
      .find('span[id="select2-sl_list_metrics-container"]')
      .click();
    cy.wait('@getListOfMetricsByService')
    cy.waitUntil(
        () => {
          return cy.getIframeBody()
          .find('.select2-results')
          .find('li')
            .then(($lis) => {
              const count = $lis.length;
              if (count <= 1) {
                cy.exportConfig();
                cy.getIframeBody()
                  .find('span[title="Clear field"]')
                  .eq(1)
                  .click();
                cy.getIframeBody()
                  .find('span[id="select2-sl_list_metrics-container"]')
                  .click();
                cy.wait('@getListOfMetricsByService')
              }
              return count > 1;
            });
        },
        { interval: 10000, timeout: 600000 }
    );
    
    cy.getIframeBody()
      .find('span[title="Clear field"]')
      .eq(1)
      .click();
    cy.getIframeBody()
      .find('span[id="select2-sl_list_metrics-container"]')
      .click();
    cy.wait('@getListOfMetricsByService')
    cy.getIframeBody()
      .find(`div[title='${body.knownMetric}']`)
      .click();  
    cy.getIframeBody()
      .find('#td_list_metrics img')
      .eq(0)
      .click();
    cy.getIframeBody()
      .find('input[name="unit_name"]')
      .clear()
      .type(body.unit); 
    cy.getIframeBody()
      .find('input[name="warn"]')
      .clear()
      .type(body.warning_threshold); 
    cy.getIframeBody()
      .find('input[name="crit"]')
      .clear()
      .type(body.critical_threshold); 
    cy.getIframeBody().find('div.md-checkbox.md-checkbox-inline').eq(0).click();
    cy.getIframeBody()
      .find('textarea[name="comment"]')
      .clear()
      .type(body.comments);
    cy.getIframeBody().find('input.btc.bt_success[name^="submit"]').eq(0).click();
    cy.wait('@getTimeZone');
    cy.exportConfig();
});

Cypress.Commands.add('checkFieldsOfVM', (body: VirtualMetric) => {
  cy.wait('@getTimeZone');
  cy.waitForElementInIframe('#main-content', 'input[name="vmetric_name"]');
  cy.getIframeBody()
    .find('input[name="vmetric_name"]')
    .should('have.value', body.name);
  cy.getIframeBody()
    .find('#select2-host_id-container')
    .should('have.attr', 'title', body.linkedHostServices);
  cy.getIframeBody()
    .find('textarea[name="rpn_function"]')
    .should('have.value', body.knownMetric);
  cy.getIframeBody()
    .find('input[name="unit_name"]')
    .should('have.value', body.unit);
  cy.getIframeBody()
    .find('input[name="warn"]')
    .should('have.value', body.warning_threshold);
  cy.getIframeBody()
    .find('input[name="crit"]')
    .should('have.value', body.critical_threshold);
  cy.getIframeBody()
    .find('textarea[name="comment"]')
    .should('have.value', body.comments);
});

Cypress.Commands.add('addMetaService', (body: MetaService)  => {
  cy.getIframeBody().find('a.bt_success').contains('Add').click();
  cy.wait('@getTimeZone');
  cy.waitForElementInIframe('#main-content', 'input[name="meta_name"]');
  cy.getIframeBody().find('input[name="meta_name"]').type(body.name);
  cy.getIframeBody()
    .find('input[name="max_check_attempts"]')
    .type(body.max_check_attempts);
  cy.getIframeBody().find('input.btc.bt_success[name^="submit"]').eq(0).click();
  cy.wait('@getTimeZone');
});

Cypress.Commands.add('addMSDependency', (body: MetaServiceDependency) => {
  cy.waitForElementInIframe('#main-content', 'input[name="dep_name"]');
  cy.getIframeBody()
    .find('input[name="dep_name"]')
    .type(body.name);
  cy.getIframeBody()
    .find('input[name="dep_description"]')
    .type(body.description);
  cy.getIframeBody().find('label[for="eUnknown"]').click({ force: true });
  cy.getIframeBody().find('label[for="nUnknown"]').click({ force: true });
  cy.getIframeBody().find('input[class="select2-search__field"]').eq(0).click();
  cy.getIframeBody().find(`div[title="${body.metaServicesNames[0]}"]`).click();
  cy.getIframeBody().find('input[class="select2-search__field"]').eq(0).click();
  cy.getIframeBody().find(`div[title="${body.metaServicesNames[1]}"]`).click();
  cy.getIframeBody().find('input[class="select2-search__field"]').eq(1).click();
  cy.getIframeBody().find(`div[title="${body.dependentMSNames[0]}"]`).click();
  cy.getIframeBody()
    .find('textarea[name="dep_comment"]')
    .type(body.comment);
  cy.getIframeBody().find('input.btc.bt_success[name^="submit"]').eq(0).click();
});

Cypress.Commands.add('updateMSDependency', (body: MetaServiceDependency) => {
  cy.waitForElementInIframe('#main-content', 'input[name="dep_name"]');
    cy.getIframeBody()
      .find('input[name="dep_name"]')
      .clear()
      .type(body.name);
    cy.getIframeBody()
      .find('input[name="dep_description"]')
      .clear()
      .type(body.description);
    cy.getIframeBody().find('label[for="eUnknown"]').click({ force: true });
    cy.getIframeBody().find('label[for="eOk"]').click({ force: true });

    cy.getIframeBody().find('label[for="nUnknown"]').click({ force: true });
    cy.getIframeBody().find('label[for="nCritical"]').click({ force: true });
    cy.getIframeBody().find('span[title="Clear field"]').eq(0).click();
    cy.getIframeBody()
      .find('input[class="select2-search__field"]')
      .eq(0)
      .click();
    cy.getIframeBody().find(`div[title="${body.metaServicesNames[0]}"]`).click();
    cy.getIframeBody().find('span[title="Clear field"]').eq(1).click();
    cy.getIframeBody()
      .find('input[class="select2-search__field"]')
      .eq(1)
      .click();
    cy.getIframeBody().find(`div[title="${body.dependentMSNames[0]}"]`).click();
    cy.getIframeBody()
      .find('textarea[name="dep_comment"]')
      .clear()
      .type(body.comment);
    cy.getIframeBody()
      .find('input.btc.bt_success[name^="submit"]')
      .eq(0)
      .click();
})

Cypress.Commands.add('addServiceDependency', (body: ServiceDependency) => {
  cy.waitForElementInIframe('#main-content', 'input[name="dep_name"]');
  cy.getIframeBody()
    .find('input[name="dep_name"]')
    .type(body.name);
  cy.getIframeBody()
    .find('input[name="dep_description"]')
    .type(body.description);
  cy.getIframeBody().find('label[for="eOk"]').click({ force: true });
  cy.getIframeBody().find('label[for="eWarning"]').click({ force: true });
  cy.getIframeBody().find('label[for="eCritical"]').click({ force: true });
  cy.getIframeBody().find('label[for="nOk"]').click({ force: true });
  cy.getIframeBody().find('label[for="nWarning"]').click({ force: true });
  cy.getIframeBody().find('label[for="nCritical"]').click({ force: true });

  cy.getIframeBody().find('input[class="select2-search__field"]').eq(0).click();
  cy.getIframeBody().find(`div[title="${body.services[0]}"]`).click();

  cy.getIframeBody().find('input[class="select2-search__field"]').eq(1).type(`host2 - ${body.dependentServices[0]}`);
  cy.getIframeBody().find(`div[title="host2 - ${body.dependentServices[0]}"]`).click();

  cy.getIframeBody().find('input[class="select2-search__field"]').eq(2).click();
  cy.getIframeBody().find(`div[title="${body.dependentHosts[0]}"]`).click();

  cy.getIframeBody()
    .find('textarea[name="dep_comment"]')
    .type(body.comment);
  cy.getIframeBody().find('input.btc.bt_success[name^="submit"]').eq(0).click();
  cy.wait('@getTimeZone');
  cy.exportConfig();
});

Cypress.Commands.add('updateServiceDependency', (body: ServiceDependency) => {
  cy.waitForElementInIframe('#main-content', 'input[name="dep_name"]');
    cy.getIframeBody()
      .find('input[name="dep_name"]')
      .clear()
      .type(body.name);
    cy.getIframeBody()
      .find('input[name="dep_description"]')
      .clear()
      .type(body.description);
    cy.getIframeBody().find('label[for="eOk"]').click({ force: true });
    cy.getIframeBody().find('label[for="nOk"]').click({ force: true });
    
    cy.getIframeBody().find('span[title="Clear field"]').eq(0).click();
    cy.getIframeBody()
      .find('input[class="select2-search__field"]')
      .eq(0)
      .click();
    cy.getIframeBody().find(`div[title="host2 - ${body.services[0]}"]`).click();

    cy.getIframeBody().find('span[title="Clear field"]').eq(1).click();
    cy.getIframeBody()
      .find('input[class="select2-search__field"]')
      .eq(1)
      .type(body.dependentServices[0]);
    cy.getIframeBody().find(`div[title="host3 - ${body.dependentServices[0]}"]`).click();

    cy.getIframeBody().find('span[title="Clear field"]').eq(2).click();
    cy.getIframeBody()
      .find('input[class="select2-search__field"]')
      .eq(2)
      .type(body.dependentHosts[0]);
    cy.getIframeBody().find(`div[title="${body.dependentHosts[0]}"]`).click();

    cy.getIframeBody()
      .find('textarea[name="dep_comment"]')
      .clear()
      .type(body.comment);
    cy.getIframeBody()
      .find('input.btc.bt_success[name^="submit"]')
      .eq(0)
      .click();
    cy.wait('@getTimeZone');
    cy.exportConfig();
})

interface ServiceDependency {
  name: string,
  description: string,
  parent_relationship: number,
  execution_fails_on_ok: number,
  execution_fails_on_warning: number,
  execution_fails_on_unknown: number,
  execution_fails_on_critical: number,
  execution_fails_on_pending: number,
  execution_fails_on_none: number,
  notification_fails_on_none: number,
  notification_fails_on_ok: number,
  notification_fails_on_warning: number,
  notification_fails_on_unknown: number,
  notification_fails_on_critical: number,
  notification_fails_on_pending: number,
  services: string[],
  dependentServices: string[],
  dependentHosts: string[],
  comment: string
}

interface VirtualMetric {
  name: string,
  linkedHostServices: string,
  knownMetric: string,
  unit: string,
  warning_threshold: string,
  critical_threshold: string,
  comments: string,
}

interface MetaService {
  name : string,
  max_check_attempts: string
}

interface MetaServiceDependency {
  name: string,
  description: string,
  parent_relationship: number,
  execution_fails_on_ok: number,
  execution_fails_on_warning: number,
  execution_fails_on_unknown: number,
  execution_fails_on_critical: number,
  execution_fails_on_pending: number,
  execution_fails_on_none: number,
  notification_fails_on_none: number,
  notification_fails_on_ok: number,
  notification_fails_on_warning: number,
  notification_fails_on_unknown: number,
  notification_fails_on_critical: number,
  notification_fails_on_pending: number,
  metaServicesNames: string[],
  dependentMSNames: string[],
  comment: string
}

declare global {
  namespace Cypress {
    interface Chainable {
      addOrUpdateVirtualMetric: (body: VirtualMetric) => Cypress.Chainable;
      checkFieldsOfVM: (body: VirtualMetric) => Cypress.Chainable;
      addMetaService: (body: MetaService) => Cypress.Chainable;
      addMSDependency: (body:MetaServiceDependency) => Cypress.Chainable;
      updateMSDependency: (body: MetaServiceDependency) => Cypress.Chainable;
      addServiceDependency: (body: ServiceDependency) => Cypress.Chainable;
      updateServiceDependency: (body: ServiceDependency) => Cypress.Chainable;
    }
  }
}

export {};