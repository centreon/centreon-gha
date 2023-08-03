import { MutableRefObject, useMemo, useRef } from 'react';

import { Group } from '@visx/visx';
import { isNil } from 'ramda';

import { ClickAwayListener, Skeleton } from '@mui/material';

import Axes from './BasicComponents/Axes';
import Grids from './BasicComponents/Grids';
import Lines from './BasicComponents/Lines';
import { canDisplayThreshold } from './BasicComponents/Lines/Threshold/models';
import LoadingProgress from './BasicComponents/LoadingProgress';
import useFilterLines from './BasicComponents/useFilterLines';
import { useStyles } from './Graph.styles';
import Header from './Header';
import InteractionWithGraph from './InteractiveComponents';
import TooltipAnchorPoint from './InteractiveComponents/AnchorPoint/TooltipAnchorPoint';
import GraphTooltip from './InteractiveComponents/Tooltip';
import useGraphTooltip from './InteractiveComponents/Tooltip/useGraphTooltip';
import Legend from './Legend';
import { margin } from './common';
import {
  Data,
  GlobalAreaLines,
  GraphInterval,
  GraphProps,
  LegendModel
} from './models';
import { getLeftScale, getRightScale, getXScale } from './timeSeries';
import { useIntersection } from './useGraphIntersection';
import { CurveType } from './BasicComponents/Lines/models';

interface Props extends GraphProps {
  curve: CurveType;
  graphData: Data;
  graphInterval: GraphInterval;
  graphRef: MutableRefObject<HTMLDivElement | null>;
  legend?: LegendModel;
  shapeLines?: GlobalAreaLines;
}

const Graph = ({
  graphData,
  height = 500,
  width,
  shapeLines,
  axis,
  displayAnchor,
  loading,
  zoomPreview,
  graphInterval,
  timeShiftZones,
  annotationEvent,
  tooltip,
  legend,
  graphRef,
  header,
  curve
}: Props): JSX.Element => {
  const graphSvgRef = useRef<SVGSVGElement | null>(null);

  const { classes } = useStyles();
  const { isInViewport } = useIntersection({ element: graphRef?.current });

  const legendRef = useRef<HTMLDivElement | null>(null);

  const graphWidth = width > 0 ? width - margin.left - margin.right : 0;
  const graphHeight =
    (height || 0) > 0
      ? (height || 0) -
        margin.top -
        margin.bottom -
        (legendRef.current?.getBoundingClientRect().height || 0)
      : 0;

  const { title, timeSeries, baseAxis, lines } = graphData;

  const { displayedLines, newLines } = useFilterLines({
    displayThreshold: canDisplayThreshold(shapeLines?.areaThresholdLines),
    lines
  });

  const xScale = useMemo(
    () =>
      getXScale({
        dataTime: timeSeries,
        valueWidth: graphWidth
      }),
    [timeSeries, graphWidth]
  );

  const leftScale = useMemo(
    () =>
      getLeftScale({
        dataLines: displayedLines,
        dataTimeSeries: timeSeries,
        valueGraphHeight: graphHeight - 35
      }),
    [displayedLines, timeSeries, graphHeight]
  );

  const rightScale = useMemo(
    () =>
      getRightScale({
        dataLines: displayedLines,
        dataTimeSeries: timeSeries,
        valueGraphHeight: graphHeight - 35
      }),
    [timeSeries, displayedLines, graphHeight]
  );

  const graphTooltipData = useGraphTooltip({
    graphWidth,
    timeSeries,
    xScale
  });

  const displayLegend = legend?.display ?? true;
  const displayTooltip = !isNil(tooltip?.renderComponent);

  if (!isInViewport) {
    return (
      <Skeleton
        height={graphSvgRef?.current?.clientHeight ?? graphHeight}
        variant="rectangular"
        width="100%"
      />
    );
  }

  return (
    <>
      <Header
        displayTimeTick={displayAnchor?.displayGuidingLines ?? true}
        header={header}
        timeSeries={timeSeries}
        title={title}
        xScale={xScale}
      />
      <ClickAwayListener onClickAway={graphTooltipData?.hideTooltip}>
        <div className={classes.container}>
          <LoadingProgress
            display={loading}
            height={graphHeight}
            width={width}
          />
          <svg height={graphHeight + margin.top} ref={graphSvgRef} width="100%">
            <Group.Group left={margin.left} top={margin.top}>
              <Grids
                height={graphHeight - margin.top}
                leftScale={leftScale}
                width={graphWidth}
                xScale={xScale}
              />
              <Axes
                data={{
                  baseAxis,
                  lines: displayedLines,
                  timeSeries,
                  ...axis
                }}
                graphInterval={graphInterval}
                height={graphHeight - margin.top}
                leftScale={leftScale}
                rightScale={rightScale}
                width={graphWidth}
                xScale={xScale}
              />

              <Lines
                curve={curve}
                displayAnchor={displayAnchor}
                displayedLines={displayedLines}
                graphSvgRef={graphSvgRef}
                height={graphHeight - margin.top}
                leftScale={leftScale}
                rightScale={rightScale}
                timeSeries={timeSeries}
                width={graphWidth}
                xScale={xScale}
                {...shapeLines}
              />

              <InteractionWithGraph
                annotationData={{ ...annotationEvent }}
                commonData={{
                  graphHeight,
                  graphSvgRef,
                  graphWidth,
                  timeSeries,
                  xScale
                }}
                timeShiftZonesData={{
                  ...timeShiftZones,
                  graphInterval,
                  loading
                }}
                zoomData={{ ...zoomPreview }}
              />
            </Group.Group>
          </svg>
          {displayTooltip && (
            <GraphTooltip {...tooltip} {...graphTooltipData} />
          )}
          {(displayAnchor?.displayTooltipsGuidingLines ?? true) && (
            <TooltipAnchorPoint
              baseAxis={baseAxis}
              graphHeight={graphHeight - 35}
              graphWidth={graphWidth}
              leftScale={leftScale}
              lines={displayedLines}
              rightScale={rightScale}
              timeSeries={timeSeries}
              xScale={xScale}
            />
          )}
        </div>
      </ClickAwayListener>
      {displayLegend && (
        <div ref={legendRef}>
          <Legend
            base={baseAxis}
            displayAnchor={displayAnchor?.displayGuidingLines ?? true}
            lines={newLines}
            renderExtraComponent={legend?.renderExtraComponent}
            timeSeries={timeSeries}
          />
        </div>
      )}
    </>
  );
};

export default Graph;
