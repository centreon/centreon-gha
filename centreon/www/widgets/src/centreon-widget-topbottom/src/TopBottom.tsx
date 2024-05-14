import { equals } from 'ramda';

import { LoadingSkeleton } from '@centreon/ui';

import {
  CommonWidgetProps,
  FormThreshold,
  GlobalRefreshInterval,
  Metric,
  Resource
} from '../../models';
import NoResources from '../../NoResources';
import { areResourcesFullfilled } from '../../utils';

import { TopBottomSettings } from './models';
import useTopBottom from './useTopBottom';
import MetricTop from './MetricTop';
import { useTopBottomStyles } from './TopBottom.styles';

interface TopBottomProps
  extends Pick<
    CommonWidgetProps<object>,
    'playlistHash' | 'dashboardId' | 'id'
  > {
  globalRefreshInterval: GlobalRefreshInterval;
  isFromPreview?: boolean;
  metrics: Array<Metric>;
  refreshCount: number;
  refreshInterval: 'default' | 'custom' | 'manual';
  refreshIntervalCustom?: number;
  resources: Array<Resource>;
  threshold: FormThreshold;
  topBottomSettings: TopBottomSettings;
  valueFormat: 'raw' | 'human';
}

const TopBottom = ({
  metrics,
  refreshInterval,
  topBottomSettings,
  globalRefreshInterval,
  refreshIntervalCustom,
  resources,
  valueFormat,
  threshold,
  refreshCount,
  isFromPreview,
  id,
  dashboardId,
  playlistHash
}: TopBottomProps): JSX.Element => {
  const { classes } = useTopBottomStyles();

  const areResourcesOk = areResourcesFullfilled(resources);

  const { isLoading, metricsTop, isMetricEmpty } = useTopBottom({
    dashboardId,
    globalRefreshInterval,
    id,
    metrics,
    playlistHash,
    refreshCount,
    refreshInterval,
    refreshIntervalCustom,
    resources,
    topBottomSettings
  });

  if (!areResourcesOk || isMetricEmpty) {
    return <NoResources />;
  }

  if (isLoading && !metricsTop) {
    return (
      <div className={classes.loader}>
        <LoadingSkeleton height={50} width="100%" />
        <LoadingSkeleton height={50} width="100%" />
        <LoadingSkeleton height={50} width="100%" />
      </div>
    );
  }

  return (
    <div className={classes.container}>
      {(metricsTop?.resources || []).map((metricTop, index) => (
        <MetricTop
          displayAsRaw={equals('raw', valueFormat)}
          index={index}
          isFromPreview={isFromPreview}
          key={`${metricTop.name}_${metricTop.id}`}
          metricTop={metricTop}
          showLabels={topBottomSettings.showLabels}
          thresholds={threshold}
          unit={metricsTop?.unit || ''}
        />
      ))}
    </div>
  );
};

export default TopBottom;
