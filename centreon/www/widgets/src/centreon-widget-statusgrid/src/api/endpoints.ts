import { equals, flatten, includes, isEmpty, pluck } from 'ramda';

import {
  ListingParameters,
  QueryParameter,
  buildListingEndpoint
} from '@centreon/ui';

import { Resource } from '../../../models';
import { formatStatus } from '../../../utils';

export const resourcesEndpoint = '/monitoring/resources';

interface BuildResourcesEndpointProps {
  baseEndpoint: string;
  limit?: number;
  page?: number;
  resources: Array<Resource>;
  sortBy?: string;
  states?: Array<string>;
  statuses?: Array<string>;
  type?: string;
}

const resourceTypesCustomParameters = [
  'host-group',
  'host-category',
  'service-group',
  'service-category'
];
const resourceTypesSearchParameters = ['host', 'service'];

const categories = ['host-category', 'service-category'];

const resourcesSearchMapping = {
  host: 'parent_name',
  service: 'name'
};

interface GetCustomQueryParametersProps {
  resources: Array<Resource>;
  states?: Array<string>;
  statuses?: Array<string>;
  types?: Array<string>;
}

export const getListingCustomQueryParameters = ({
  types,
  statuses,
  states,
  resources
}: GetCustomQueryParametersProps): Array<QueryParameter> => {
  const resourcesToApplyToCustomParameters = resources.filter(
    ({ resourceType }) => includes(resourceType, resourceTypesCustomParameters)
  );

  return [
    ...(types && !isEmpty(types) ? [{ name: 'types', value: types }] : []),
    ...(statuses && !isEmpty(statuses)
      ? [{ name: 'statuses', value: statuses }]
      : []),
    ...(states && !isEmpty(states) ? [{ name: 'states', value: states }] : []),
    ...resourcesToApplyToCustomParameters.map(
      ({ resourceType, resources: resourcesToApply }) => ({
        name: includes(resourceType, categories)
          ? `${resourceType.replace('-', '_')}_names`
          : `${resourceType.replace('-', '')}_names`,
        value: pluck('name', resourcesToApply)
      })
    )
  ];
};

interface GetListingQueryParametersProps {
  limit?: number;
  page?: number;
  resources: Array<Resource>;
  sortBy?: string;
  sortOrder?: string;
}

export const getListingQueryParameters = ({
  resources,
  sortBy,
  sortOrder,
  limit,
  page
}: GetListingQueryParametersProps): ListingParameters => {
  const resourcesToApplyToSearchParameters = resources.filter(
    ({ resourceType }) => includes(resourceType, resourceTypesSearchParameters)
  );

  const searchConditions = resourcesToApplyToSearchParameters.map(
    ({ resourceType, resources: resourcesToApply }) => {
      return resourcesToApply.map((resource) => ({
        field: resourcesSearchMapping[resourceType],
        values: {
          $rg: `^${resource.name}$`
        }
      }));
    }
  );

  return {
    limit,
    page,
    search: {
      conditions: flatten(searchConditions)
    },
    sort:
      sortBy && sortOrder
        ? {
            [sortBy]: sortOrder
          }
        : undefined
  };
};

export const buildResourcesEndpoint = ({
  type,
  statuses,
  states,
  sortBy,
  limit,
  resources,
  baseEndpoint,
  page = 1
}: BuildResourcesEndpointProps): string => {
  const formattedStatuses = formatStatus(statuses || []);

  const sortOrder = equals(sortBy, 'status_severity_code') ? 'DESC' : 'ASC';

  return buildListingEndpoint({
    baseEndpoint,
    customQueryParameters: getListingCustomQueryParameters({
      resources,
      states,
      statuses: formattedStatuses,
      types: [type]
    }),
    parameters: getListingQueryParameters({
      limit,
      page,
      resources,
      sortBy,
      sortOrder
    })
  });
};
