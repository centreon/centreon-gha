import { useRef } from 'react';

import { Pie } from '@visx/shape';
import { Group } from '@visx/group';
import { Text } from '@visx/text';
import numeral from 'numeral';
import { equals } from 'ramda';

import { useTheme } from '@mui/material';

import { Tooltip } from '../../components';
import { Legend as LegendComponent } from '../Legend';
import { LegendProps } from '../Legend/models';
import { getValueByUnit } from '../common/utils';

import { PieProps } from './models';
import { usePieStyles } from './PieChart.styles';
import { useResponsivePie } from './useResponsivePie';

const DefaultLengd = ({ scale }: LegendProps): JSX.Element => (
  <LegendComponent scale={scale} />
);

const ResponsivePie = ({
  title,
  variant = 'pie',
  width,
  height,
  data,
  unit = 'number',
  Legend = DefaultLengd,
  displayLegend = true,
  innerRadius = 40,
  onArcClick,
  displayValues,
  tooltipContent
}: PieProps & { height: number; width: number }): JSX.Element => {
  const theme = useTheme();

  const titleRef = useRef(null);
  const legendRef = useRef(null);

  const {
    half,
    legendScale,
    svgContainerSize,
    svgSize,
    svgWrapperWidth,
    total
  } = useResponsivePie({
    data,
    height,
    legendRef,
    titleRef,
    unit,
    width
  });

  const { classes } = usePieStyles({ svgSize });

  return (
    <div
      className={classes.container}
      style={{
        height,
        width
      }}
    >
      <div
        className={classes.svgWrapper}
        style={{
          height,
          width: svgWrapperWidth
        }}
      >
        {equals(variant, 'pie') && title && (
          <div className={classes.title} data-testid="Title" ref={titleRef}>
            {`${numeral(total).format('0a').toUpperCase()} `} {title}
          </div>
        )}
        <div
          className={classes.svgContainer}
          data-testid="pieChart"
          style={{
            height: svgContainerSize,
            width: svgContainerSize
          }}
        >
          <svg data-variant={variant} height={svgSize} width={svgSize}>
            <Group left={half} top={half}>
              <Pie
                data={data}
                innerRadius={() => {
                  return equals(variant, 'pie') ? 0 : half - innerRadius;
                }}
                outerRadius={half}
                padAngle={0.01}
                pieValue={(items) => items.value}
              >
                {(pie) => {
                  return pie.arcs.map((arc) => {
                    const [centroidX, centroidY] = pie.path.centroid(arc);
                    const midAngle = Math.atan2(centroidY, centroidX);

                    const labelRadius = half * 0.8;

                    const labelX = Math.cos(midAngle) * labelRadius;
                    const labelY = Math.sin(midAngle) * labelRadius;

                    const angle = arc.endAngle - arc.startAngle;
                    const minAngle = 0.2;

                    return (
                      <Tooltip
                        hasCaret
                        classes={{
                          tooltip: classes.pieChartTooltip
                        }}
                        followCursor={false}
                        key={arc.data.label}
                        label={tooltipContent?.({
                          color: arc.data.color,
                          label: arc.data.label,
                          title,
                          total,
                          value: arc.data.value
                        })}
                      >
                        <g
                          data-testid={arc.data.label}
                          onClick={() => {
                            onArcClick?.(arc.data);
                          }}
                        >
                          <path
                            d={pie.path(arc) as string}
                            fill={arc.data.color}
                          />
                          {displayValues && angle > minAngle && (
                            <Text
                              data-testid="value"
                              dy=".33em"
                              fill="#000"
                              fontSize={12}
                              pointerEvents="none"
                              textAnchor="middle"
                              x={equals(variant, 'donut') ? centroidX : labelX}
                              y={equals(variant, 'donut') ? centroidY : labelY}
                            >
                              {getValueByUnit({
                                total,
                                unit,
                                value: arc.data.value
                              })}
                            </Text>
                          )}
                        </g>
                      </Tooltip>
                    );
                  });
                }}
              </Pie>
              {equals(variant, 'donut') && title && (
                <>
                  <Text
                    className={classes.title}
                    dy={-15}
                    fill={theme.palette.text.primary}
                    textAnchor="middle"
                  >
                    {numeral(total).format('0a').toUpperCase()}
                  </Text>
                  <Text
                    className={classes.title}
                    data-testid="Title"
                    dy={15}
                    fill={theme.palette.text.primary}
                    textAnchor="middle"
                  >
                    {title}
                  </Text>
                </>
              )}
            </Group>
          </svg>
        </div>
      </div>
      {displayLegend && (
        <div data-testid="Legend" ref={legendRef}>
          {Legend({
            data,
            scale: legendScale,
            title,
            total
          })}
        </div>
      )}
    </div>
  );
};

export default ResponsivePie;
