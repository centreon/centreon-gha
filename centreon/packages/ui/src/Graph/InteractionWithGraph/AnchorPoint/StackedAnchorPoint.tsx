import { memo } from 'react';

import { ScaleLinear, ScaleTime } from 'd3-scale';
import { equals, isNil, map, not, pipe } from 'ramda';

import { bisectDate } from '../../timeSeries';
import { MousePosition } from '../mouseTimeValueAtoms';

import { StackValue } from './models';

import AnchorPoint from '.';

interface Props {
  areaColor: string;
  displayTimeValues: boolean;
  graphHeight: number;
  graphWidth: number;
  lineColor: string;
  position: MousePosition;
  positionX?: number;
  positionY?: number;
  stackValues: Array<StackValue>;
  timeTick?: Date;
  transparency: number;
  xScale: ScaleTime<number, number>;
  yScale: ScaleLinear<number, number>;
}

const key = 'data';

const getStackedDates = (stackValues: Array<StackValue>): Array<Date> => {
  const toTimeTick = (stackValue: StackValue): string => {
    return key in stackValue ? stackValue[key].timeTick : '';
  };
  const toDate = (tick: string): Date => new Date(tick);

  return pipe(map(toTimeTick), map(toDate))(stackValues);
};

const getYAnchorPoint = ({
  timeTick,
  stackValues,
  yScale
}: Pick<Props, 'timeTick' | 'stackValues' | 'yScale'>): number => {
  const index = bisectDate(getStackedDates(stackValues), timeTick);
  const timeValue = stackValues[index];

  return yScale(timeValue[1] as number);
};

const StackedAnchorPoint = ({
  xScale,
  yScale,
  stackValues,
  timeTick,
  areaColor,
  transparency,
  lineColor,
  displayTimeValues,
  ...rest
}: Props): JSX.Element | null => {
  if (isNil(timeTick) || not(displayTimeValues)) {
    return null;
  }
  if (isNil(timeTick)) {
    return null;
  }
  const xAnchorPoint = xScale(timeTick);

  const yAnchorPoint = getYAnchorPoint({
    stackValues,
    timeTick,
    yScale
  });

  if (isNil(yAnchorPoint)) {
    return null;
  }

  return (
    <AnchorPoint
      areaColor={areaColor}
      lineColor={lineColor}
      transparency={transparency}
      x={xAnchorPoint}
      y={yAnchorPoint}
      {...rest}
    />
  );
};

export default memo(
  StackedAnchorPoint,
  (prevProps, nextProps) =>
    equals(prevProps.timeTick, nextProps.timeTick) &&
    equals(prevProps.stackValues, nextProps.stackValues) &&
    equals(prevProps.position, nextProps.position)
);
