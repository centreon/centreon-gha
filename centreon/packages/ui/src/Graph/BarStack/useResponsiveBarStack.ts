import { useMemo } from 'react';

import { scaleOrdinal } from '@visx/scale';
import { equals, isNil, pluck } from 'ramda';

import { LegendScale } from '../Legend/models';
import { getValueByUnit } from '../common/utils';

import { BarType } from './models';

interface UseBarStackProps {
  data: Array<BarType>;
  height: number;
  legendDirection?: 'column' | 'row';
  unit?: 'percentage' | 'number';
  variant?: 'vertical' | 'horizontal';
  width: number;
}

interface UseBarStackState {
  colorScale;
  formattedLegendDirection: 'column' | 'row';
  isSmall: boolean;
  isVerticalBar: boolean;
  legendScale: LegendScale;
  titleVariant: 'xs' | 'sm' | 'md';
  total: number;
}

const useResponsiveBarStack = ({
  data,
  variant,
  height,
  width,
  unit = 'number',
  legendDirection
}: UseBarStackProps): UseBarStackState => {
  const total = useMemo(
    () => Math.floor(data.reduce((acc, { value }) => acc + value, 0)),
    [data]
  );

  const isVerticalBar = useMemo(() => equals(variant, 'vertical'), [variant]);

  const isSmall = useMemo(
    () => Math.floor(height) < 90,
    [isVerticalBar, height]
  );

  const titleVariant = useMemo(() => {
    if (width <= 105) {
      return 'xs';
    }

    if (width <= 150 || isSmall) {
      return 'sm';
    }

    return 'md';
  }, [isSmall, width]);

  const keys = useMemo(() => pluck('label', data), [data]);

  const colorsRange = useMemo(() => pluck('color', data), [data]);

  const colorScale = useMemo(
    () =>
      scaleOrdinal({
        domain: keys,
        range: colorsRange
      }),
    [keys, colorsRange]
  );

  const legendScale = useMemo(
    () => ({
      domain: data.map(({ value }) => getValueByUnit({ total, unit, value })),
      range: colorsRange
    }),
    [data, colorsRange]
  );

  const formattedLegendDirection = useMemo(() => {
    if (!isNil(legendDirection)) {
      return legendDirection;
    }

    if (equals(variant, 'horizontal')) {
      return 'row';
    }

    return 'column';
  }, [legendDirection, variant]);

  return {
    colorScale,
    formattedLegendDirection,
    isSmall,
    isVerticalBar,
    legendScale,
    titleVariant,
    total
  };
};

export default useResponsiveBarStack;
