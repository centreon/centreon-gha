import { renderHook, act } from '@testing-library/react-hooks/dom';
import { useSetAtom } from 'jotai';

import { TestQueryProvider, Method, SnackbarProvider } from '@centreon/ui';

import {
  labelDelete,
  labelSave,
  labelDuplicate,
  labelActiveOrInactive,
  labelClosePanel,
  labelReduceInformationPanel,
  labelExpandInformationPanel,
  labelChangeName,
  labelNotificationName,
  labelRequired,
  labelSearchHostGroups,
  labelSearchServiceGroups,
  labelChooseAtLeastOneResource,
  labelChooseAtleastOneUser,
  labelTimePeriod,
  labelSubject,
  labelMessageFieldShouldNotBeEmpty,
  labelDoYouWantToConfirmAction,
  labelConfirmEditNotification,
  labelSuccessfulEditNotification
} from '../translatedLabels';

import { notificationtEndpoint } from './api/endpoints';
import { PanelMode } from './models';
import { EditedNotificationIdAtom, panelModeAtom } from './atom';
import { listNotificationResponse } from './testUtils';

import Form from '.';

const PanelWithQueryProvider = (): JSX.Element => {
  return (
    <div style={{ height: '100vh' }}>
      <TestQueryProvider>
        <SnackbarProvider>
          <Form />
        </SnackbarProvider>
      </TestQueryProvider>
    </div>
  );
};

const initialize = (): void => {
  cy.interceptAPIRequest({
    alias: 'listingRequest',
    method: Method.GET,
    path: notificationtEndpoint({ id: 1 }),
    response: listNotificationResponse
  });

  cy.interceptAPIRequest({
    alias: 'editNotificationRequest',
    method: Method.PUT,
    path: notificationtEndpoint({ id: 1 }),
    response: { status: 'ok' }
  });

  cy.window().then((window) => {
    window.localStorage.setItem('cloud-notifications-panel-width', '800');
  });

  cy.mount({
    Component: <PanelWithQueryProvider />
  });

  cy.viewport(1200, 1000);
};

describe('Edit Panel', () => {
  before(() => {
    const panelMode = renderHook(() => useSetAtom(panelModeAtom));
    const editedNotification = renderHook(() =>
      useSetAtom(EditedNotificationIdAtom)
    );

    const setPanelMode = panelMode.result.current;
    const setEditedNotification = editedNotification.result.current;

    act(() => {
      setEditedNotification(1);
      setPanelMode(PanelMode.Edit);
    });
  });

  beforeEach(initialize);

  it('Ensures that the header section displays all the expected actions', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelDelete).should('be.visible');
    cy.findByLabelText(labelSave).should('be.visible');
    cy.findByLabelText(labelDuplicate).should('be.visible');
    cy.findByLabelText(labelActiveOrInactive).should('be.visible');
    cy.findByLabelText(labelClosePanel).should('be.visible');

    cy.matchImageSnapshot();
  });

  it('Confirms that the notification name is properly rendered with the edited value and supports the capability for users to modify the name by interacting with the "Edit icon" ', () => {
    cy.waitForRequest('@listingRequest');

    const notificationName = 'Notifications 1';
    cy.findByText(notificationName).should('be.visible');
    cy.findByTestId(labelChangeName).click();
    cy.findByText(labelChangeName).should('not.exist');

    cy.findByPlaceholderText(labelNotificationName).should(
      'have.value',
      notificationName
    );
    cy.findByPlaceholderText(labelNotificationName).should(
      'have.attr',
      'required'
    );

    cy.matchImageSnapshot();
  });

  it('Ensures that the Form correctly handles an empty name field by showing an error message and disabling the save button as a validation measure', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId(labelChangeName).click();

    cy.findByPlaceholderText(labelNotificationName).clear();
    cy.clickOutside();

    cy.findByText(labelRequired).should('be.visible');
    cy.findByLabelText(labelSave).should('be.disabled');

    cy.matchImageSnapshot();
  });

  it('Confirms that the "Expand/Reduce" button triggers the desired expansion or reduction of the panel, providing users with the ability to control its visibility and size', () => {
    cy.waitForRequest('@listingRequest');

    expect(
      localStorage.getItem('cloud-notifications-panel-width')
    ).to.deep.equal('800');

    cy.findByText(labelReduceInformationPanel).should('be.visible');
    cy.findByTestId(labelReduceInformationPanel).click();
    cy.findByText(labelExpandInformationPanel).should('be.visible');
    cy.wait(50).then(() => {
      expect(localStorage.getItem('cloud-notifications-panel-width')).to.equal(
        '550'
      );
    });

    cy.findByTestId(labelReduceInformationPanel).click();
    cy.findByText(labelReduceInformationPanel).should('be.visible');
    cy.wait(50).then(() => {
      expect(localStorage.getItem('cloud-notifications-panel-width')).to.equal(
        '800'
      );
    });

    cy.matchImageSnapshot();
  });

  it("Ensures that the save button's initial state is set to disabled", () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelSave).should('be.disabled');

    cy.matchImageSnapshot();
  });

  it('Confirms that the save button correctly responds to field changes, becoming enabled when a modification occurs and the form is error-free', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelSave).should('be.disabled');
    cy.findByLabelText(labelActiveOrInactive).click();
    cy.findByLabelText(labelSave).should('not.be.disabled');

    cy.matchImageSnapshot();
  });

  it('Displays host groups resources and events with the edited notification values', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelSearchHostGroups).should('be.visible');
    cy.findByText('Firewall').should('be.visible');
    cy.findByText('Linux-Servers').should('be.visible');
    cy.findByText('Networks').should('be.visible');
    cy.findByText('Printers').should('be.visible');

    cy.findByTestId('Host groups events').within(() => {
      cy.findAllByRole('checkbox').should('have.length', 3);
      cy.findAllByRole('checkbox').eq(0).should('be.checked');
      cy.findAllByRole('checkbox').eq(1).should('be.checked');
      cy.findAllByRole('checkbox').eq(2).should('not.be.checked');
    });

    cy.matchImageSnapshot();
  });

  it('Ensures that the "Include Services" field presents the value of the edited notification', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId('include Services').within(() => {
      cy.findByRole('checkbox').should('be.checked');
    });

    cy.findByTestId('Extra events services').within(() => {
      cy.findAllByRole('checkbox').should('have.length', 4);
      cy.findAllByRole('checkbox').eq(0).should('be.checked');
      cy.findAllByRole('checkbox').eq(1).should('be.checked');
      cy.findAllByRole('checkbox').eq(2).should('not.be.checked');
      cy.findAllByRole('checkbox').eq(3).should('not.be.checked');
    });

    cy.matchImageSnapshot();
  });

  it('Confirms that the "Include Services" checkbox controls the enabling and checking status of all host group services checkboxes', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId('include Services').click();

    cy.findByTestId('Extra events services').within(() => {
      for (let i = 0; i < 4; i += 1) {
        cy.findAllByRole('checkbox').eq(i).should('not.be.checked');
        cy.findAllByRole('checkbox').eq(i).should('be.disabled');
      }
    });

    cy.matchImageSnapshot();
  });

  it('Ensures that when the "Host Groups" field is empty, all events checkboxes are unchecked and the "Include Services" field is not visible.', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId('include Services').should('be.visible');
    cy.findByTestId('Extra events services').should('be.visible');

    for (let i = 0; i < 4; i += 1) {
      cy.findAllByTestId('CancelIcon').eq(0).click();
    }

    cy.findByTestId('Host groups events').within(() => {
      for (let i = 0; i < 3; i += 1) {
        cy.findAllByRole('checkbox').eq(i).should('not.be.checked');
      }
    });

    cy.findByTestId('include Services').should('not.exist');
    cy.findByTestId('Extra events services').should('not.exist');

    cy.matchImageSnapshot();
  });

  it('Displays service groups and events fields with the edited notification values', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelSearchServiceGroups).should('be.visible');
    cy.findByText('service1').should('be.visible');
    cy.findByText('service2').should('be.visible');
    cy.findByText('service3').should('be.visible');

    cy.findByTestId('Service groups events').within(() => {
      cy.findAllByRole('checkbox').should('have.length', 4);
      cy.findAllByRole('checkbox').eq(0).should('not.be.checked');
      cy.findAllByRole('checkbox').eq(1).should('be.checked');
      cy.findAllByRole('checkbox').eq(2).should('be.checked');
      cy.findAllByRole('checkbox').eq(2).should('be.checked');
    });

    cy.matchImageSnapshot();
  });

  it('Ensures that when the service groups field is empty, all associated events are disabled and unchecked', () => {
    cy.waitForRequest('@listingRequest');

    for (let i = 0; i < 3; i += 1) {
      cy.findAllByTestId('CancelIcon').eq(4).click();
    }

    cy.findByTestId('Service groups events').within(() => {
      for (let i = 0; i < 4; i += 1) {
        cy.findAllByRole('checkbox').eq(i).should('not.be.checked');
        cy.findAllByRole('checkbox').eq(i).should('be.disabled');
      }
    });

    cy.matchImageSnapshot();
  });

  it('Validates that when both resource fields are empty, the user interface responds by displaying an error message and disabling the save button', () => {
    cy.waitForRequest('@listingRequest');

    for (let i = 0; i < 7; i += 1) {
      cy.findAllByTestId('CancelIcon').eq(0).click();
    }

    cy.findAllByText(labelChooseAtLeastOneResource).should('have.length', 2);
    cy.findByLabelText(labelSave).should('be.disabled');

    cy.matchImageSnapshot();
  });

  it('Displays users field with Edited notification users', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByText('centreon-gorgone').should('be.visible');
    cy.findByText('Guest').should('be.visible');

    cy.matchImageSnapshot();
  });

  it('Validates that when users field is empty, the user interface responds by displaying an error message and disabling the save button', () => {
    cy.waitForRequest('@listingRequest');

    cy.findAllByTestId('CancelIcon').eq(7).click();
    cy.findAllByTestId('CancelIcon').eq(7).click();
    cy.clickOutside();

    cy.findByText(labelChooseAtleastOneUser).should('be.visible');
    cy.findByLabelText(labelSave).should('be.disabled');

    cy.matchImageSnapshot();
  });

  it('Ensures that the time period checkbox is checked and disabled, indicating its pre-selected status', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId(labelTimePeriod).should('be.visible');
    cy.findByTestId(labelTimePeriod).within(() => {
      cy.findByRole('checkbox').should('be.checked');
      cy.findByRole('checkbox').should('be.disabled');
    });

    cy.matchImageSnapshot();
  });

  it('Confirms that the three icons for notification channels are appropriately presented, with the email icon initially chosen and the other icons disabled', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId('Email').within(() => {
      cy.findByRole('checkbox').should('be.checked');
    });

    cy.findByTestId('SMS').within(() => {
      cy.findByRole('checkbox').should('not.be.checked');
      cy.findByRole('checkbox').should('be.disabled');
    });

    cy.findByTestId('Slack').within(() => {
      cy.findByRole('checkbox').should('not.be.checked');
      cy.findByRole('checkbox').should('be.disabled');
    });

    cy.matchImageSnapshot();
  });

  it('Confirms that the subject field is properly rendered with the edited notification subject', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelSubject).should('have.value', 'Notification');

    cy.matchImageSnapshot();
  });

  it('Confirms that the message field is properly rendered with the edited notification message', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId('EmailBody').contains('Bonjour');
    cy.findByTestId('EmailBody').contains('Cordialement');

    cy.matchImageSnapshot();
  });

  it('Validates that when the message field is empty, the user interface responds by displaying an error message and disabling the save button', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByTestId('EmailBody').clear();
    cy.clickOutside();

    cy.findByText(labelMessageFieldShouldNotBeEmpty).should('be.visible');
    cy.findByLabelText(labelSave).should('be.disabled');

    cy.matchImageSnapshot();
  });
});

describe('Edit Panel : Confirm Dialog', () => {
  beforeEach(initialize);

  it('Confirms that the save button triggers the display of a confirmation dialog, providing the user with an additional confirmation step before proceeding with the action', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelSave).should('be.disabled');
    cy.findByLabelText(labelActiveOrInactive).click();
    cy.findByLabelText(labelSave).click();

    cy.findByText(labelDoYouWantToConfirmAction);
    cy.findByText(labelConfirmEditNotification);

    cy.matchImageSnapshot();
  });

  it('Confirms that the confirm button triggers the sending of a PUT request', () => {
    cy.waitForRequest('@listingRequest');

    cy.findByLabelText(labelActiveOrInactive).click();
    cy.findByLabelText(labelSave).click();

    cy.findByLabelText('Confirm').click();

    cy.waitForRequest('@editNotificationRequest').then(({ request }) => {
      expect(JSON.parse(request.body).is_activated).to.equal(true);
    });

    cy.findByText(labelSuccessfulEditNotification).should('be.visible');

    cy.matchImageSnapshot();
  });
});
