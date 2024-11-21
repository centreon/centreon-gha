import { GraphOptionId } from '../Graph/Performance/models';
import type {
  Acknowledgement,
  Downtime,
  NamedEntity,
  Parent,
  ResourceLinks,
  ResourceType,
  Severity,
  Status
} from '../models';

import type { CustomTimePeriod, TimePeriodId } from './tabs/Graph/models';

export interface Group extends NamedEntity {
  configuration_uri: string | null;
}

export interface Category extends NamedEntity {
  configuration_uri: string | null;
}

export interface Sensitivity {
  current_value: number;
  default_value: number;
  maximum_value: number;
  minimum_value: number;
}

export interface ResourceDetails extends NamedEntity {
  acknowledged: boolean;
  acknowledgement?: Acknowledgement;
  active_checks: boolean;
  alias?: string;
  calculation_type?: string;
  categories?: Array<Category>;
  command_line?: string;
  downtimes: Array<Downtime>;
  duration: string;
  execution_time: number;
  flapping: boolean;
  fqdn?: string;
  groups?: Array<Group>;
  in_downtime: boolean;
  information: string;
  last_check: string | number;
  last_notification: string | number;
  last_status_change: string | number;
  last_time_with_no_issue: string | number;
  latency: number;
  links: ResourceLinks;
  monitoring_server_name?: string;
  next_check: string | number;
  notification_number: number;
  parent: Parent;
  passive_checks?: boolean;
  percent_state_change: number;
  performance_data?: string;
  sensitivity?: Sensitivity;
  service_id?: number;
  severity: Severity;
  severity_level: number;
  status: Status;
  timezone?: string;
  tries: string;
  type: ResourceType;
  uuid: string;
}

export interface ResourceDetailsAtom {
  parentResourceId?: number;
  parentResourceType?: string;
  resourceId?: number;
  resourcesDetailsEndpoint?: string;
}

export interface GraphOption {
  id: GraphOptionId;
  label: string;
  value: boolean;
}

export interface GraphOptions {
  [GraphOptionId.displayEvents]: GraphOption;
}

export interface GraphTabParameters {
  options?: GraphOptions;
}

export interface ServicesTabParameters {
  options: GraphOptions;
}

export interface TabParameters {
  graph?: GraphTabParameters;
  services?: ServicesTabParameters;
}

export interface DetailsUrlQueryParameters {
  customTimePeriod?: CustomTimePeriod;
  id: number;
  parentId?: number;
  parentType?: string;
  resourcesDetailsEndpoint?: string;
  selectedTimePeriodId?: TimePeriodId;
  tab?: string;
  tabParameters?: TabParameters;
  type?: string;
  uuid: string;
}
