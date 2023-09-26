import { SelectEntry } from '@centreon/ui';

export interface Resource {
  criticalHighThreshold: number | null;
  criticalLowThreshold: number | null;
  currentValue: number | null;
  id: number;
  max: number | null;
  min: number | null;
  name: string;
  warningHighThreshold: number | null;
  warningLowThreshold: number | null;
}

export interface MetricsTop {
  name: string;
  resources: Array<Resource>;
  unit: string;
}

export interface NamedEntity {
  id: number;
  name: string;
}

export interface Metric extends NamedEntity {
  unit: string;
}

export enum WidgetResourceType {
  host = 'host',
  hostCategory = 'host-category',
  hostGroup = 'host-group',
  service = 'service'
}

export interface WidgetDataResource {
  resourceType: 'host-group' | 'host-category' | 'host' | 'service';
  resources: Array<SelectEntry>;
}

export interface Data {
  metric: Metric;
  resources: Array<WidgetDataResource>;
}

export type ValueFormat = 'human' | 'raw';

export interface TopBottomSettings {
  numberOfValues: number;
  order: 'top' | 'bottom';
  showLabels: boolean;
}
