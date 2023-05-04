import { MutableRefObject } from 'react';

import { ScaleTime } from 'd3-scale';

import { ZoomPreview } from '../../models';

export interface ZoomBoundaries {
  end: string;
  start: string;
}

export interface ZoomPreviewData extends ZoomPreview {
  graphHeight: number;
  graphSvgRef: MutableRefObject<SVGSVGElement | null>;
  graphWidth: number;
  xScale: ScaleTime<number, number>;
}
