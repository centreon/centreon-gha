<<<<<<< HEAD
import { useTranslation } from 'react-i18next';

import { useTheme } from '@mui/material';
import IconAcknowledge from '@mui/icons-material/Person';
=======
import * as React from 'react';

import { useTranslation } from 'react-i18next';

import { useTheme } from '@material-ui/core';
import IconAcknowledge from '@material-ui/icons/Person';
>>>>>>> centreon/dev-21.10.x

import { Props } from '..';
import { labelAcknowledgement } from '../../../../../translatedLabels';
import EventAnnotations from '../EventAnnotations';

const AcknowledgementAnnotations = (props: Props): JSX.Element => {
  const { t } = useTranslation();
  const theme = useTheme();

  const color = theme.palette.action.acknowledged;

  return (
    <EventAnnotations
      Icon={IconAcknowledge}
      ariaLabel={t(labelAcknowledgement)}
      color={color}
      type="acknowledgement"
      {...props}
    />
  );
};

export default AcknowledgementAnnotations;
