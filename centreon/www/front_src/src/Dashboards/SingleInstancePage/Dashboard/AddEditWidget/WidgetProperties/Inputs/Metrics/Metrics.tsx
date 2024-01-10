/* eslint-disable react/no-array-index-key */
import { useTranslation } from 'react-i18next';
import { equals, head } from 'ramda';
import { useAtomValue } from 'jotai';

import { CircularProgress, Typography } from '@mui/material';

import { Avatar } from '@centreon/ui/components';
import { MultiAutocompleteField, SingleAutocompleteField } from '@centreon/ui';

import {
  labelAvailable,
  labelIsTheSelectedResource,
  labelMetrics,
  labelSelectMetric,
  labelThresholdsAreAutomaticallyHidden,
  labelYouCanSelectUpToTwoMetricUnits,
  labelYouHaveTooManyMetrics
} from '../../../../translatedLabels';
import { WidgetPropertyProps } from '../../../models';
import { useAddWidgetStyles } from '../../../addWidget.styles';
import { useResourceStyles } from '../Inputs.styles';
import { isAtLeastOneResourceFullfilled } from '../utils';
import { editProperties } from '../../../../hooks/useCanEditDashboard';
import { singleMetricSelectionAtom } from '../../../atoms';

import useMetrics from './useMetrics';

const Metric = ({ propertyName }: WidgetPropertyProps): JSX.Element => {
  const { classes } = useResourceStyles();
  const { classes: avatarClasses } = useAddWidgetStyles();
  const { t } = useTranslation();

  const {
    metrics,
    changeMetric,
    hasTooManyMetrics,
    isLoadingMetrics,
    metricCount,
    resources,
    selectedMetrics,
    getOptionLabel,
    changeMetrics,
    getMetricOptionDisabled,
    getMultipleOptionLabel,
    deleteMetricItem,
    error,
    isTouched,
    hasReachedTheLimitOfUnits,
    metricWithSeveralResources
  } = useMetrics(propertyName);

  const { canEditField } = editProperties.useCanEditProperties();
  const singleMetricSelection = useAtomValue(singleMetricSelectionAtom);

  const canDisplayMetricsSelection =
    isAtLeastOneResourceFullfilled(resources) && !hasTooManyMetrics;

  const title =
    metricCount && isAtLeastOneResourceFullfilled(resources)
      ? `${t(labelMetrics)} (${metricCount} ${labelAvailable})`
      : t(labelMetrics);

  const warningMessages = [
    error && isTouched && error,
    hasReachedTheLimitOfUnits && (
      <>
        <span>{t(labelYouCanSelectUpToTwoMetricUnits)}</span>
        <br />
        <span>{t(labelThresholdsAreAutomaticallyHidden)}</span>
      </>
    ),
    singleMetricSelection && metricWithSeveralResources && (
      <>
        <strong>{metricWithSeveralResources}</strong>{' '}
        {t(labelIsTheSelectedResource)}
      </>
    )
  ];

  const header = (
    <div className={classes.resourcesHeader}>
      <Avatar compact className={avatarClasses.widgetAvatar}>
        3
      </Avatar>
      <Typography className={classes.resourceTitle}>{title}</Typography>
      {isLoadingMetrics && <CircularProgress size={16} />}
    </div>
  );

  return (
    <div className={classes.resourcesContainer}>
      {header}
      <div>
        {canDisplayMetricsSelection && singleMetricSelection && (
          <SingleAutocompleteField
            className={classes.resources}
            disabled={!canEditField || isLoadingMetrics}
            getOptionItemLabel={getOptionLabel}
            getOptionLabel={getOptionLabel}
            isOptionEqualToValue={(option, selectedValue) =>
              equals(option?.id, selectedValue?.id)
            }
            label={t(labelSelectMetric)}
            options={metrics}
            value={head(selectedMetrics || []) || undefined}
            onChange={changeMetric}
          />
        )}
        {canDisplayMetricsSelection && !singleMetricSelection && (
          <MultiAutocompleteField
            chipProps={{
              color: 'primary',
              onDelete: (_, option): void => deleteMetricItem(option)
            }}
            className={classes.resources}
            disabled={!canEditField || isLoadingMetrics}
            getOptionDisabled={getMetricOptionDisabled}
            getOptionLabel={getOptionLabel}
            getOptionTooltipLabel={getOptionLabel}
            getTagLabel={getMultipleOptionLabel}
            label={t(labelSelectMetric)}
            options={metrics}
            value={selectedMetrics || []}
            onChange={changeMetrics}
          />
        )}
      </div>
      {hasTooManyMetrics && (
        <Typography sx={{ color: 'text.disabled' }}>
          {t(labelYouHaveTooManyMetrics)}
        </Typography>
      )}
      <div>
        {warningMessages.map((content) => (
          <Typography
            className={classes.warningText}
            key={content?.toString()}
            variant="body2"
          >
            {content}
          </Typography>
        ))}
      </div>
    </div>
  );
};

export default Metric;
