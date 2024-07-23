import { useAtom } from 'jotai';
import { equals } from 'ramda';
import { useQueryClient } from '@tanstack/react-query';
import { useTranslation } from 'react-i18next';

import { Method, useMutationQuery, useSnackbar } from '@centreon/ui';

import {
  labelAdditionalConnectorCreated,
  labelAdditionalConnectorUpdated
} from '../translatedLabels';
import { dialogStateAtom } from '../atoms';
import { AdditionalConnectors } from '../Listing/models';
import {
  additionalConnectorsEndpoint,
  getAdditionalConnectorEndpoint
} from '../api/endpoints';

type UseConnectorConfig = {
  closeDialog: () => void;
  connector: AdditionalConnectors | null;
  isDialogOpen: boolean;
  submit: (values: AdditionalConnectors, _) => void;
  variant: 'create' | 'update';
};

const useAdditionalConnectorModal = (): UseConnectorConfig => {
  const { showSuccessMessage } = useSnackbar();
  const queryClient = useQueryClient();
  const { t } = useTranslation();

  const [dialogState, setDialogState] = useAtom(dialogStateAtom);

  const closeDialog = (): void =>
    setDialogState({ ...dialogState, isOpen: false });

  const requestData = equals(dialogState.variant, 'create')
    ? {
        endpoint: additionalConnectorsEndpoint,
        labelOnSuccess: labelAdditionalConnectorCreated,
        method: Method.POST
      }
    : {
        endpoint: getAdditionalConnectorEndpoint(dialogState?.connector?.id),
        labelOnSuccess: labelAdditionalConnectorUpdated,
        method: Method.PUT
      };

  const { mutateAsync } = useMutationQuery({
    getEndpoint: () => requestData.endpoint,
    method: requestData.method,
    onSettled: closeDialog,
    onSuccess: () => {
      showSuccessMessage(t(requestData.labelOnSuccess));
      queryClient.invalidateQueries({ queryKey: ['listConnectors'] });
    }
  });

  const submit = (values: AdditionalConnectors): void => {
    mutateAsync({ payload: values });
  };

  return {
    closeDialog,
    connector: dialogState.connector,
    isDialogOpen: dialogState.isOpen,
    submit,
    variant: dialogState.variant
  };
};

export default useAdditionalConnectorModal;
