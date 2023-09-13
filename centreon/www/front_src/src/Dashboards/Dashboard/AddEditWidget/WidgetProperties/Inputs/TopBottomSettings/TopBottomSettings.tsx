import { useTranslation } from 'react-i18next';

import {
  TextField,
  ToggleButton,
  ToggleButtonGroup,
  Typography
} from '@mui/material';

import {
  labelBottom,
  labelNumberOfValues,
  labelShowValueLabels,
  labelTop
} from '../../../../translatedLabels';
import { editProperties } from '../../../../useCanEditDashboard';
import { WidgetPropertyProps } from '../../../models';
import WidgetSwitch from '../Switch';

import useTopBottomSettings from './useTopBottomSettings';
import { useTopBottomSettingsStyles } from './TopBottomSettings.styles';

const TopBottomSettings = ({
  propertyName
}: WidgetPropertyProps): JSX.Element => {
  const { t } = useTranslation();
  const { classes } = useTopBottomSettingsStyles();

  const { value, changeOrder, changeNumberOfValues } =
    useTopBottomSettings(propertyName);

  const { canEditField } = editProperties.useCanEditProperties();

  return (
    <div>
      <div className={classes.values}>
        <Typography>{t(labelNumberOfValues)}</Typography>
        <TextField
          className={classes.input}
          disabled={!canEditField}
          size="compact"
          type="number"
          value={value.numberOfValues}
          onChange={changeNumberOfValues}
        />
        <ToggleButtonGroup
          exclusive
          className={classes.toggleButtonGroup}
          color="primary"
          disabled={!canEditField}
          size="small"
          value={value.order}
          onChange={changeOrder}
        >
          <ToggleButton data-testid={labelTop} value="top">
            {t(labelTop)}
          </ToggleButton>
          <ToggleButton data-testid={labelBottom} value="bottom">
            {t(labelBottom)}
          </ToggleButton>
        </ToggleButtonGroup>
      </div>
      <WidgetSwitch
        label={labelShowValueLabels}
        propertyName={`${propertyName}.showLabels`}
      />
    </div>
  );
};

export default TopBottomSettings;
