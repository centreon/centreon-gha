/* eslint-disable newline-before-return */
/* eslint-disable @typescript-eslint/no-namespace */
import metrics from '../../fixtures/dashboards/creation/widgets/metrics.json';
import singleMetricPayloadPl from '../../fixtures/dashboards/creation/widgets/singleMetricPayloadPl.json';
import singleMetricPayloadRta from '../../fixtures/dashboards/creation/widgets/singleMetricPayloadRta.json';
import singleMetricDoubleWidgets from '../../fixtures/dashboards/creation/widgets/dashboardWithTwoWidgets.json';
import metricsGraphWidget from '../../fixtures/dashboards/creation/widgets/metricsGraphWidget.json';
import statusGridWidget from '../../fixtures/dashboards/creation/widgets/status-grid-widget.json';
import textWidget from '../../fixtures/dashboards/creation/widgets/textWidget.json';
import topBottomWidget from '../../fixtures/dashboards/creation/widgets/dashboardWithTopBottomWidget.json';

Cypress.Commands.add('enableDashboardFeature', () => {
  cy.execInContainer({
    command: `sed -i 's@"dashboard": 0@"dashboard": 3@' /usr/share/centreon/config/features.json`,
    name: 'web'
  });
});

Cypress.Commands.add(
  'waitUntilForDashboardRoles',
  (accessRightsTestId, expectedElementCount, closeIconIndex) => {
    const openModalAndCheck: () => Cypress.Chainable<boolean> = () => {
      cy.getByTestId({ testId: accessRightsTestId }).invoke('show').click();
      cy.get('.MuiSelect-select').should('be.visible');

      return cy
        .get('.MuiSelect-select')
        .should('be.visible')
        .then(($element) => {
          if (closeIconIndex !== undefined) {
            cy.getByTestId({ testId: 'CloseIcon' }).eq(closeIconIndex).click();
          } else {
            cy.getByTestId({ testId: 'CloseIcon' }).click();
          }
          
          return cy.wrap($element.length === expectedElementCount);
        });
    };

    return cy.waitUntil(() => openModalAndCheck(), {
      errorMsg: 'The element does not exist',
      interval: 3000,
      timeout: 30000
    });
  }
);

Cypress.Commands.add('verifyGraphContainer', () => {
  cy.get('[class*="graphContainer"]')
    .should('be.visible')
    .within(() => {
      cy.get('[class*="graphText"]')
        .should('be.visible')
        .within(() => {
          cy.get('[class*="MuiTypography-h2"]').should('be.visible');

          cy.get('[class*="MuiTypography-h5"]')
            .eq(0)
            .should('contain', metrics.rtaValues.warning);

          cy.get('[class*="MuiTypography-h5"]')
            .eq(1)
            .should('contain', metrics.rtaValues.critical);
        });
    });
});

Cypress.Commands.add('verifyDuplicatesGraphContainer', () => {
  cy.get('[class*="graphContainer"]')
    .eq(1)
    .should('be.visible')
    .within(() => {
      cy.get('[class*="graphText"]')
        .should('be.visible')
        .within(() => {
          cy.get('[class*="MuiTypography-h2"]').should('be.visible');

          cy.get('[class*="MuiTypography-h5"]')
            .eq(0)
            .should('contain', metrics.plValues.warning);

          cy.get('[class*="MuiTypography-h5"]')
            .eq(1)
            .should('contain', metrics.plValues.critical);
        });
    });
});

Cypress.Commands.add('waitUntilPingExists', () => {
  cy.intercept({
    method: 'GET',
    url: /\/centreon\/api\/latest\/monitoring\/services\/names.*$/
  }).as('servicesRequest');

  return cy.waitUntil(
    () => {
      cy.getByTestId({ testId: 'Select resource' }).eq(0).realClick();
      cy.contains('Centreon-Server').realClick();
      cy.wait(60_000);
      cy.getByTestId({ testId: 'Select resource' }).eq(1).realClick();

      return cy.wait('@servicesRequest').then((interception) => {
        if (interception && interception.response) {
          cy.log('Response Body:', interception.response.body);
          const responseBody = interception.response.body;
          if (
            Array.isArray(responseBody.result) &&
            responseBody.result.length > 0
          ) {
            const pingFound = responseBody.result.some(
              (result) => result.name === 'Ping'
            );

            if (pingFound) {
              cy.contains('Ping').click();
              return cy.wrap(true);
            }
            cy.log('Ping not found in the API response');

            return cy.wrap(false);
          }
          cy.log('Response is not an array or is empty');
          return cy.wrap(false);
        }
        cy.log('Interception or response is undefined');
        return cy.wrap(false);
      });
    },
    {
      errorMsg: 'Timed out waiting for Ping to exist',
      interval: 3000,
      timeout: 60000
    }
  );
});

Cypress.Commands.add(
  'insertDashboardWithWidget',
  (dashboardBody, patchBody) => {
    cy.request({
      body: {
        ...dashboardBody
      },
      method: 'POST',
      url: '/centreon/api/latest/configuration/dashboards'
    }).then((response) => {
      const dashboardId = response.body.id;
      cy.waitUntil(
        () => {
          return cy
            .request({
              method: 'GET',
              url: `/centreon/api/latest/configuration/dashboards/${dashboardId}`
            })
            .then((getResponse) => {
              return getResponse.body && getResponse.body.id === dashboardId;
            });
        },
        {
          timeout: 10000
        }
      );
      cy.request({
        body: patchBody,
        method: 'PATCH',
        url: `/centreon/api/latest/configuration/dashboards/${dashboardId}`
      });
    });
  }
);

Cypress.Commands.add('getCellContent', (rowIndex, columnIndex) => {
  cy.waitUntil(
    () =>
      cy
        .get(
          `.MuiTable-root:eq(1) .MuiTableRow-root:nth-child(${rowIndex}) .MuiTableCell-root:nth-child(${columnIndex})`
        )
        .should('be.visible')
        .then(() => true),
    { interval: 1000, timeout: 10000 }
  );

  return cy
    .get(
      `.MuiTable-root:eq(1) .MuiTableRow-root:nth-child(${rowIndex}) .MuiTableCell-root:nth-child(${columnIndex})`
    )
    .invoke('text')
    .then((content) => {
      const columnContents = content ? content.match(/[A-Z][a-z]*/g) || [] : [];
      cy.log(
        `Column contents (${rowIndex}, ${columnIndex}): ${columnContents
          .join(',')
          .trim()}`
      );

      return cy.wrap(columnContents);
    });
});

Cypress.Commands.add('applyAcl', () => {
  const apacheUser = Cypress.env('WEB_IMAGE_OS').includes('alma')
    ? 'apache'
    : 'www-data';

  cy.execInContainer({
    command: `su -s /bin/sh ${apacheUser} -c "/usr/bin/env php -q /usr/share/centreon/cron/centAcl.php"`,
    name: 'web'
  });
});

Cypress.Commands.add(
  'verifyLegendItemStyle',
  (index, expectedColors, expectedValue) => {
    cy.get('[data-testid="Legend"] > *')
      .eq(index)
      .each(($legendItem) => {
        cy.wrap($legendItem)
          .find('[class*=legendItem] a')
          .then(($aTags) => {
            $aTags.each((i, aTag) => {
              cy.wrap(aTag)
                .find('div')
                .invoke('attr', 'style')
                .then((style) => {
                  expect(style).to.contain(expectedColors[i]);
                });

              // Get the value of the <p> element next to the <a> tag
              cy.wrap(aTag)
                .next('p')
                .invoke('text')
                .then((text) => {
                  expect(text).to.contain(expectedValue[i]);
                });
            });
          });
      });
  }
);

interface Dashboard {
  description?: string;
  name: string;
}

type metricsGraphWidgetJSONData = typeof metricsGraphWidget;
type statusGridWidgetJSONData = typeof statusGridWidget;
type singleMetricPayloadPlJSONData = typeof singleMetricPayloadPl;
type singleMetricPayloadRtaJSONData = typeof singleMetricPayloadRta;
type singleMetricDoubleWidgetsJSONData = typeof singleMetricDoubleWidgets;
type textWidgetJSONData = typeof textWidget;
type topBottomWidgetJSONData = typeof topBottomWidget;
type widgetJSONData =
  | singleMetricPayloadPlJSONData
  | singleMetricPayloadRtaJSONData
  | singleMetricDoubleWidgetsJSONData
  | metricsGraphWidgetJSONData
  | statusGridWidgetJSONData
  | textWidgetJSONData
  | topBottomWidgetJSONData;

  interface HostDataType {
    acknowledgement_timeout: number;
    action_url: string;
    active_check_enabled: number;
    add_inherited_contact: boolean;
    add_inherited_contact_group: boolean;
    address: string;
    alias: string;
    categories: Array<number>;
    check_command_args: Array<string>;
    check_command_id: number;
    check_timeperiod_id: number;
    comment: string;
    event_handler_command_args: Array<string>;
    event_handler_command_id: number;
    event_handler_enabled: number;
    first_notification_delay: number;
    flap_detection_enabled: number;
    freshness_checked: number;
    freshness_threshold: number;
    geo_coords: string;
    groups: Array<number>;
    high_flap_threshold: number;
    icon_alternative: string;
    icon_id: number;
    is_activated: boolean;
    low_flap_threshold: number;
    macros: Array<object>;
    max_check_attempts: number;
    monitoring_server_id: number;
    name: string;
    normal_check_interval: number;
    note: string;
    note_url: string;
    notification_enabled: number;
    notification_interval: number;
    notification_options: number;
    notification_timeperiod_id: number;
    passive_check_enabled: number;
    recovery_notification_delay: number;
    retry_check_interval: number;
    severity_id: number;
    snmp_community: string;
    snmp_version: string;
    templates: Array<number>;
    timezone_id: number;
  }
  
  
  Cypress.Commands.add('addNewHostAndReturnId', (hostData = {}) => {
    const defaultHostData = {
      address: '127.0.0.1',
      alias: 'generic-active-host',
      groups: [53],
      macros: [
        {
          description: 'Some text to describe the macro',
          is_password: false,
          name: 'MacroName',
          value: 'macroValue'
        }
      ],
      monitoring_server_id: 1,
      name: 'generic-active-host',
      templates: [2]
    };
  
    const requestBody = { ...defaultHostData, ...hostData };
  
    cy.request({
      body: requestBody,
      method: 'POST',
      url: '/centreon/api/latest/configuration/hosts'
    }).then((response) => {
      expect(response.status).to.eq(201);
      return response.body.id;
    });
  });

declare global {
  namespace Cypress {
    interface Chainable {
      applyAcl: () => Cypress.Chainable;
      enableDashboardFeature: () => Cypress.Chainable;
      getCellContent: (rowIndex: number, colIndex: number) => Cypress.Chainable;
      insertDashboardWithWidget: (
        dashboard: Dashboard,
        patch: widgetJSONData
      ) => Cypress.Chainable;
      verifyDuplicatesGraphContainer: (metrics) => Cypress.Chainable;
      verifyGraphContainer: (metrics) => Cypress.Chainable;
      verifyLegendItemStyle: (
        index: number,
        expectedColors: Array<string>,
        expectedValue: Array<string>
      ) => Cypress.Chainable;
      waitUntilForDashboardRoles: (
        accessRightsTestId: string,
        expectedElementCount: number,
        closeIconIndex?:number
      ) => Cypress.Chainable;
      waitUntilPingExists: () => Cypress.Chainable;
      addNewHostAndReturnId: (
        hostData?: Partial<HostDataType>
      ) => Cypress.Chainable;
    }
  }
}

export {};
