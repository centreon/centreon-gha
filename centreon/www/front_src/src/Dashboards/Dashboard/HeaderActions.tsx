import { useEffect, useMemo, useState } from 'react';

import { useTranslation } from 'react-i18next';
import { useAtomValue, useSetAtom } from 'jotai';

import EditOutlinedIcon from '@mui/icons-material/EditOutlined';
import { Typography } from '@mui/material';

import { Modal, Button } from '@centreon/ui/components';
import { SaveButton } from '@centreon/ui';

import {
  labelExit,
  labelExitEditionMode,
  labelEditDashboard,
  labelSave,
  labelSaving,
  labelLeaveEditionModeChangesNotSaved,
  labelQuitDashboardChangesNotSaved,
  labelExitDashboard
} from './translatedLabels';
import {
  dashboardAtom,
  isEditingAtom,
  switchPanelsEditionModeDerivedAtom
} from './atoms';
import useDashboardSaveBlocker from './useDashboardSaveBlocker';
import { PanelDetails } from './models';
import { formatPanel } from './useDashboardDetails';
import useSaveDashboard from './useSaveDashboard';
import useDashboardDirty from './useDashboardDirty';

interface HeaderActionsProps {
  id?: number;
  name?: string;
  panels?: Array<PanelDetails>;
}

const HeaderActions = ({
  id,
  name,
  panels
}: HeaderActionsProps): JSX.Element => {
  const { t } = useTranslation();

  const [isAskingCancelConfirmation, setIsAskingCancelConfirmation] =
    useState(false);

  const isEditing = useAtomValue(isEditingAtom);
  const switchPanelsEditionMode = useSetAtom(
    switchPanelsEditionModeDerivedAtom
  );
  const setDashboard = useSetAtom(dashboardAtom);

  const { blocked, blockNavigation, proceedNavigation } =
    useDashboardSaveBlocker({ id, name });

  const { saveDashboard, isSaving } = useSaveDashboard();

  const dirty = useDashboardDirty(
    (panels || []).map((panel) => formatPanel({ panel, staticPanel: false }))
  );

  const startEditing = (): void => {
    switchPanelsEditionMode(true);
  };

  const askCancelConfirmation = (): void => {
    if (!dirty) {
      switchPanelsEditionMode(false);

      return;
    }
    setIsAskingCancelConfirmation(true);
  };

  const closeAskCancelConfirmationAndBlock = (): void => {
    setIsAskingCancelConfirmation(false);

    if (blocked) {
      blockNavigation?.();
    }
  };

  const closeAskCancelConfirmationAndProceed = (): void => {
    setIsAskingCancelConfirmation(false);

    if (blocked) {
      proceedNavigation?.();
    }
  };

  const cancelEditing = (): void => {
    setDashboard({
      layout: panels?.map((panel) => formatPanel({ panel })) || []
    });
    switchPanelsEditionMode(false);
    closeAskCancelConfirmationAndProceed();
  };

  const saveAndProceed = (): void => {
    saveDashboard();
    setIsAskingCancelConfirmation(false);

    if (blocked) {
      proceedNavigation?.();
    }
  };

  useEffect(() => {
    if (!blocked) {
      return;
    }

    setIsAskingCancelConfirmation(true);
  }, [blocked]);

  const modalTitle = useMemo(
    () =>
      blocked && isAskingCancelConfirmation
        ? t(labelExitDashboard, { dashboardName: name })
        : t(labelExitEditionMode),
    [blocked, isAskingCancelConfirmation, name]
  );
  const modalMessage = useMemo(
    () =>
      blocked && isAskingCancelConfirmation
        ? t(labelQuitDashboardChangesNotSaved, { dashboardName: name })
        : t(labelLeaveEditionModeChangesNotSaved),
    [blocked, isAskingCancelConfirmation, name]
  );

  if (!isEditing) {
    return (
      <Button
        data-testid="edit_dashboard"
        icon={<EditOutlinedIcon />}
        iconVariant="start"
        variant="ghost"
        onClick={startEditing}
      >
        {t(labelEditDashboard)}
      </Button>
    );
  }

  return (
    <>
      <Button
        data-testid="cancel_dashboard"
        disabled={isSaving}
        variant="ghost"
        onClick={askCancelConfirmation}
      >
        {t(labelExit)}
      </Button>
      <SaveButton
        labelLoading={t(labelSaving) as string}
        labelSave={t(labelSave) as string}
        loading={isSaving}
        onClick={saveDashboard}
      />
      <Modal
        open={isAskingCancelConfirmation}
        onClose={closeAskCancelConfirmationAndBlock}
      >
        <Modal.Header>{modalTitle}</Modal.Header>
        <Modal.Body>
          <Typography>{modalMessage}</Typography>
        </Modal.Body>
        <Modal.Actions
          isLoading={isSaving}
          labels={{
            cancel: t(labelExit),
            confirm: t(labelSave),
            loading: t(labelSaving)
          }}
          onCancel={cancelEditing}
          onConfirm={saveAndProceed}
        />
      </Modal>
    </>
  );
};

export default HeaderActions;
