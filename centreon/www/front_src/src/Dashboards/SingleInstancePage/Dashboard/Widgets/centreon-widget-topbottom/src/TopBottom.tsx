import { equals } from 'ramda';

import { LoadingSkeleton } from '@centreon/ui';

import NoResources from '../../NoResources';
import {
  CommonWidgetProps,
  FormThreshold,
  GlobalRefreshInterval,
  Metric,
  Resource
} from '../../models';
import { areResourcesFullfilled } from '../../utils';

import { useRef } from 'react';
import Label from './Label';
import MetricTop from './MetricTop';
import { useTopBottomStyles } from './TopBottom.styles';
import { TopBottomSettings } from './models';
import useSingleBarCurrentWidth from './useSingleBarCurrentWidth';
import useTopBottom from './useTopBottom';

interface TopBottomProps
  extends Pick<
    CommonWidgetProps<object>,
    'playlistHash' | 'dashboardId' | 'id' | 'widgetPrefixQuery'
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
  playlistHash,
  widgetPrefixQuery
}: TopBottomProps): JSX.Element => {
  const { classes } = useTopBottomStyles();

  const containerRef = useRef(null);
  const labelRef = useRef(null);

  const areResourcesOk = areResourcesFullfilled(resources);

  const singleBarCurrentWidth = useSingleBarCurrentWidth({
    containerRef,
    labelRef
  });

  console.log({ singleBarCurrentWidth });

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
    topBottomSettings,
    widgetPrefixQuery
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
    <div
      ref={containerRef}
      style={{
        display: 'flex',
        flexDirection: 'row',
        containerType: 'inline-size',
        overflow:'hidden'
      }}
    >
      <div
        style={{
          display: 'flex',
          flexDirection: 'column',
        }}
      >
        {(metricsTop?.resources || []).map((metricTop, index) => (
          <Label
            ref={labelRef}
            key={`label_${metricTop.name}_${metricTop.id}`}
            metricTop={metricTop}
            index={index}
          />
        ))}
      </div>

      <div
        style={{
          display: 'flex',
          flexDirection: 'column',
          width: singleBarCurrentWidth 
        }}
      >
        {(metricsTop?.resources || []).map((metricTop) => (
          <MetricTop
            displayAsRaw={equals('raw', valueFormat)}
            isFromPreview={isFromPreview}
            key={`${metricTop.name}_${metricTop.id}`}
            metricTop={metricTop}
            showLabels={topBottomSettings.showLabels}
            thresholds={threshold}
            unit={metricsTop?.unit || ''}
          />
        ))}
      </div>
    </div>
  );
};

export default TopBottom;
