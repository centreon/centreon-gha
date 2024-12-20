import { ReactElement } from 'react';

interface Tile<TData> {
  backgroundColor: string;
  data: TData;
  id: string;
}

interface ChildrenProps<TData> {
  backgroundColor: string;
  data: TData;
  id: string;
  isMediumSize?: boolean;
  isSmallestSize: boolean;
  tileSize?: number;
}

export interface HeatMapProps<TData> {
  arrowClassName?: string;
  children: ({
    backgroundColor,
    id,
    data,
    isSmallestSize,
    tileSize,
    isMediumSize
  }: ChildrenProps<TData>) => ReactElement | boolean | null;
  displayTooltipCondition?: (data: TData) => boolean;
  tileSizeFixed?: boolean;
  tiles: Array<Tile<TData>>;
  tooltipContent?: ({
    backgroundColor,
    id,
    data,
    isSmallestSize
  }: ChildrenProps<TData>) => ReactElement | boolean | null;
}
