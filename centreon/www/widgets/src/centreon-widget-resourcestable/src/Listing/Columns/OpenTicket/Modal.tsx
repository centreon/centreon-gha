import { useEffect } from 'react';

import { equals } from 'ramda';

import { Modal } from '@centreon/ui/components';
import { useSnackbar } from '@centreon/ui';

import { Ticket } from '../../models';

interface Props {
  close: () => void;
  isOpen: boolean;
  providerID: number;
  resource: Ticket;
}

const OpenTicketModal = ({
  close,
  isOpen,
  resource,
  providerID
}: Props): JSX.Element => {
  const { showSuccessMessage } = useSnackbar();

  const autoClose = (event: MessageEvent): void => {
    if (
      !equals(event.data, 'success') ||
      !equals(event.source?.name, 'open-ticket')
    ) {
      return;
    }

    showSuccessMessage('Ticket created');

    close();
  };

  useEffect(() => {
    if (!isOpen) {
      window.removeEventListener('message', autoClose);
    }

    window.addEventListener('message', autoClose);

    return () => {
      window.removeEventListener('message', autoClose);
    };
  }, [isOpen]);

  const src = resource.serviceID
    ? `./main.get.php?p=60421&cmd=4&provider_id=${providerID}&host_id=${resource.hostID}&service_id=${resource.serviceID}`
    : `./main.get.php?p=60421&cmd=4&provider_id=${providerID}&host_id=${resource.hostID}`;

  return (
    <Modal hasCloseButton open={isOpen} size="xlarge" onClose={close}>
      <Modal.Header>Create a ticket</Modal.Header>
      <Modal.Body>
        <iframe
          frameBorder={0}
          id="open-ticket"
          name="open-ticket"
          src={src}
          style={{ minHeight: '30vh', width: '100%' }}
          title="Main Content"
        />
      </Modal.Body>
    </Modal>
  );
};

export default OpenTicketModal;
