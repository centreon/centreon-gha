<<<<<<< HEAD
import { useTranslation } from 'react-i18next';

import { Tooltip } from '@mui/material';
import makeStyles from '@mui/styles/makeStyles';
import FlappingIcon from '@mui/icons-material/SwapCalls';
=======
import * as React from 'react';

import { useTranslation } from 'react-i18next';

import { makeStyles, Tooltip } from '@material-ui/core';
import FlappingIcon from '@material-ui/icons/SwapCalls';
>>>>>>> centreon/dev-21.10.x

import {
  labelResourceFlapping,
  labelFlapping,
} from '../../../../translatedLabels';
import { ResourceDetails } from '../../../models';

import DetailsLine from './DetailsLine';

const useStyles = makeStyles((theme) => ({
  percentStateCard: {
    alignItems: 'center',
<<<<<<< HEAD
    columnGap: theme.spacing(1),
=======
    columnGap: `${theme.spacing(1)}px`,
>>>>>>> centreon/dev-21.10.x
    display: 'grid',
    gridTemplateColumns: 'auto min-content',
  },
}));

interface Props {
  details: ResourceDetails;
}
const PercentStateChangeCard = ({ details }: Props): JSX.Element => {
  const classes = useStyles();
  const { t } = useTranslation();

  return (
    <div className={classes.percentStateCard}>
      <DetailsLine line={`${details.percent_state_change}%`} />
      {details.flapping && (
        <Tooltip title={t(labelResourceFlapping) as string}>
          <FlappingIcon
            aria-label={t(labelFlapping)}
            color="primary"
            fontSize="small"
          />
        </Tooltip>
      )}
    </div>
  );
};

export default PercentStateChangeCard;
