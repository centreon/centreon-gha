import { equals, inc } from 'ramda';
import { Link } from 'react-router-dom';

import { Box, Typography } from '@mui/material';

import { LineChartData, SingleBar } from '@centreon/ui';

import useThresholds from '../../useThresholds';
import { FormThreshold } from '../../models';

import { Resource } from './models';
import { useTopBottomStyles } from './TopBottom.styles';
import useGoToResourceStatus from './useLinkToResourceStatus';

interface MetricTopProps {
  displayAsRaw: boolean;
  index: number;
  isEditingDashboard: boolean;
  metricTop: Resource;
  showLabels: boolean;
  thresholds: FormThreshold;
  unit: string;
}

const MetricTop = ({
  metricTop,
  index,
  unit,
  thresholds,
  displayAsRaw,
  showLabels,
  isEditingDashboard
}: MetricTopProps): JSX.Element => {
  const { classes } = useTopBottomStyles();
  const formattedData: LineChartData = {
    global: {},
    metrics: [
      {
        average_value: null,
        crit: metricTop.criticalHighThreshold,
        critical_high_threshold: metricTop.criticalHighThreshold,
        critical_low_threshold: metricTop.criticalLowThreshold,
        data: [metricTop.currentValue],
        legend: metricTop.name,
        maximum_value: metricTop.max,
        metric: metricTop.name,
        metric_id: metricTop.id,
        minimum_value: metricTop.min,
        unit,
        warning_high_threshold: metricTop.warningHighThreshold,
        warning_low_threshold: metricTop.warningLowThreshold
      }
    ],
    times: []
  };
  const { getResourcesStatusUrl } = useGoToResourceStatus();

  const formattedThresholds = useThresholds({
    data: formattedData,
    displayAsRaw,
    metricName: `${metricTop.parentName}_${metricTop.name}`,
    thresholds
  });

  return (
    <>
      <Typography className={classes.resourceLabel}>
        <Link
          data-testid={`link to ${metricTop?.name}`}
          style={{ all: 'unset' }}
          target="_blank"
          to={getResourcesStatusUrl(metricTop)}
          onClick={(e) =>
            equals(isEditingDashboard, undefined) && e.preventDefault()
          }
        >
          <strong>
            #{inc(index)} {`${metricTop.parentName}_${metricTop.name}`}
          </strong>
        </Link>
      </Typography>
      <Box className={classes.singleBarContainer} style={{ height: 50 }}>
        <Link
          data-testid={`link to ${metricTop?.name}`}
          style={{ all: 'unset' }}
          target="_blank"
          to={getResourcesStatusUrl(metricTop)}
          onClick={(e) =>
            equals(isEditingDashboard, undefined) && e.preventDefault()
          }
        >
          <SingleBar
            data={formattedData}
            displayAsRaw={displayAsRaw}
            showLabels={showLabels}
            size="small"
            thresholds={formattedThresholds}
          />
        </Link>
      </Box>
    </>
  );
};

export default MetricTop;
