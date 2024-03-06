import { ChangeEvent, useMemo } from 'react';

import { useAtom } from 'jotai';
import { useFormikContext } from 'formik';
import {
  T,
  always,
  cond,
  equals,
  flatten,
  init,
  isEmpty,
  isNil,
  last,
  path,
  pluck,
  prop,
  propEq,
  reject
} from 'ramda';

import {
  QueryParameter,
  SelectEntry,
  buildListingEndpoint
} from '@centreon/ui';

import { Dataset, ResourceAccessRule, ResourceTypeEnum } from '../../../models';
import {
  labelAllResources,
  labelHost,
  labelHostCategory,
  labelHostGroup,
  labelMetaService,
  labelPleaseSelectAResource,
  labelService,
  labelServiceCategory,
  labelServiceGroup
} from '../../../translatedLabels';
import { baseEndpoint } from '../../../../api/endpoint';
import { selectedDatasetsAtom } from '../../../atom';

type UseDatasetFilterState = {
  addResource: () => void;
  changeResource: (index: number) => (_, resource: SelectEntry) => void;
  changeResourceType: (
    index: number
  ) => (e: ChangeEvent<HTMLInputElement>) => void;
  changeResources: (
    index: number
  ) => (_, resources: Array<SelectEntry>) => void;
  deleteResource: (index: number) => () => void;
  deleteResourceItem: ({ index, option, resources }) => void;
  error: string | null;
  getResourceBaseEndpoint: (
    resourceType: ResourceTypeEnum
  ) => (parameters) => string;
  getResourceTypeOptions: (index: number) => Array<SelectEntry>;
  getSearchField: (resourceType: ResourceTypeEnum) => string;
  lowestResourceTypeReached: () => boolean;
};

const resourceTypeOptions = [
  {
    availableResourceTypeOptions: [],
    id: ResourceTypeEnum.All,
    name: labelAllResources
  },
  {
    availableResourceTypeOptions: [
      { id: ResourceTypeEnum.ServiceGroup, name: labelServiceGroup },
      { id: ResourceTypeEnum.ServiceCategory, name: labelServiceCategory },
      { id: ResourceTypeEnum.Service, name: labelService }
    ],
    id: ResourceTypeEnum.Host,
    name: labelHost
  },
  {
    availableResourceTypeOptions: [
      { id: ResourceTypeEnum.HostGroup, name: labelHostGroup },
      { id: ResourceTypeEnum.Host, name: labelHost },
      { id: ResourceTypeEnum.ServiceGroup, name: labelServiceGroup },
      { id: ResourceTypeEnum.ServiceCategory, name: labelServiceCategory },
      { id: ResourceTypeEnum.Service, name: labelService }
    ],
    id: ResourceTypeEnum.HostCategory,
    name: labelHostCategory
  },
  {
    availableResourceTypeOptions: [
      { id: ResourceTypeEnum.HostCategory, name: labelHostCategory },
      { id: ResourceTypeEnum.Host, name: labelHost },
      { id: ResourceTypeEnum.ServiceGroup, name: labelServiceGroup },
      { id: ResourceTypeEnum.ServiceCategory, name: labelServiceCategory },
      { id: ResourceTypeEnum.Service, name: labelService }
    ],
    id: ResourceTypeEnum.HostGroup,
    name: labelHostGroup
  },
  {
    availableResourceTypeOptions: [],
    id: ResourceTypeEnum.MetaService,
    name: labelMetaService
  },
  {
    availableResourceTypeOptions: [],
    id: ResourceTypeEnum.Service,
    name: labelService
  },
  {
    availableResourceTypeOptions: [
      { id: ResourceTypeEnum.ServiceGroup, name: labelServiceGroup },
      { id: ResourceTypeEnum.Service, name: labelService }
    ],
    id: ResourceTypeEnum.ServiceCategory,
    name: labelServiceCategory
  },
  {
    availableResourceTypeOptions: [
      { id: ResourceTypeEnum.ServiceCategory, name: labelServiceCategory },
      { id: ResourceTypeEnum.Service, name: labelService }
    ],
    id: ResourceTypeEnum.ServiceGroup,
    name: labelServiceGroup
  }
];

export const resourceTypeBaseEndpoints = {
  [ResourceTypeEnum.Host]: '/configuration/hosts',
  [ResourceTypeEnum.HostCategory]: '/configuration/hosts/categories',
  [ResourceTypeEnum.HostGroup]: '/configuration/hosts/groups',
  [ResourceTypeEnum.MetaService]: '/configuration/metaservices',
  [ResourceTypeEnum.Service]: '/configuration/services',
  [ResourceTypeEnum.ServiceCategory]: '/configuration/services/categories',
  [ResourceTypeEnum.ServiceGroup]: '/configuration/services/groups'
};

const searchParametersBySelectedResourceType = {
  [ResourceTypeEnum.HostGroup]: {
    [ResourceTypeEnum.HostCategory]: 'hostcategory.id'
  },
  [ResourceTypeEnum.HostCategory]: {
    [ResourceTypeEnum.HostGroup]: 'hostgroup.id'
  },
  [ResourceTypeEnum.Host]: {
    [ResourceTypeEnum.HostGroup]: 'group.id',
    [ResourceTypeEnum.HostCategory]: 'category.id'
  },
  [ResourceTypeEnum.ServiceGroup]: {
    [ResourceTypeEnum.HostGroup]: 'hostgroup.id',
    [ResourceTypeEnum.HostCategory]: 'hostcategory.id',
    [ResourceTypeEnum.Host]: 'host.id',
    [ResourceTypeEnum.ServiceCategory]: 'category.id'
  },
  [ResourceTypeEnum.ServiceCategory]: {
    [ResourceTypeEnum.HostGroup]: 'hostgroup.id',
    [ResourceTypeEnum.HostCategory]: 'hostcategory.id',
    [ResourceTypeEnum.Host]: 'host.id',
    [ResourceTypeEnum.ServiceGroup]: 'group.id'
  },
  [ResourceTypeEnum.Service]: {
    [ResourceTypeEnum.HostGroup]: 'hostgroup.id',
    [ResourceTypeEnum.HostCategory]: 'hostcategory.id',
    [ResourceTypeEnum.Host]: 'host.id',
    [ResourceTypeEnum.ServiceGroup]: 'group.id',
    [ResourceTypeEnum.ServiceCategory]: 'category.id'
  }
};

const useDatasetFilter = (
  datasetFilter: Array<Dataset>,
  datasetFilterIndex: number
): UseDatasetFilterState => {
  const [selectedDatasets, setSelectedDatasets] = useAtom(selectedDatasetsAtom);

  const { values, setFieldValue, setFieldTouched, touched } =
    useFormikContext<ResourceAccessRule>();

  const value = useMemo<Array<Dataset> | undefined>(
    () =>
      path<Array<Dataset> | undefined>(
        ['datasetFilters', datasetFilterIndex],
        values
      ),
    [
      path<Array<Dataset> | undefined>(
        ['datasetFilters', datasetFilterIndex],
        values
      )
    ]
  );

  const lowestResourceTypeReached = (): boolean =>
    equals(last(datasetFilter)?.resourceType, ResourceTypeEnum.Service) ||
    equals(last(datasetFilter)?.resourceType, ResourceTypeEnum.MetaService);

  const getResourceTypeOptions = (index: number): Array<SelectEntry> => {
    if (isNil(value)) {
      return resourceTypeOptions;
    }

    const filteredResourceTypeOptions = flatten(
      pluck('availableResourceTypeOptions')(
        resourceTypeOptions.filter((option) =>
          equals(option.id, value[index - 1]?.resourceType)
        )
      )
    );

    return isEmpty(filteredResourceTypeOptions)
      ? resourceTypeOptions
      : filteredResourceTypeOptions;
  };

  const isTouched = useMemo<boolean | undefined>(
    () =>
      path<boolean | undefined>(
        ['datasetFilters', datasetFilterIndex],
        touched
      ),
    [path<boolean | undefined>(['datasetFilters', datasetFilterIndex], touched)]
  );

  const errorToDisplay =
    isTouched && isEmpty(datasetFilter) ? labelPleaseSelectAResource : null;

  const addResource = (): void => {
    setFieldValue(`datasetFilters.${datasetFilterIndex}`, [
      ...(datasetFilter || []),
      {
        resourceType: '',
        resources: []
      }
    ]);

    setSelectedDatasets([
      ...selectedDatasets,
      {
        ids: [],
        type: ResourceTypeEnum.Empty
      }
    ]);
  };

  const changeResource = (index: number) => (_, resource: SelectEntry) => {
    setFieldValue(
      `datasetFilters.${datasetFilterIndex}.${index}.resources`,
      resource
    );
    setFieldTouched(`datasetFilters.${datasetFilterIndex}`, true, false);
    setSelectedDatasets(
      selectedDatasets.map((dataset, datasetIndex) => {
        if (equals(datasetIndex, index)) {
          return {
            ids: [...dataset.ids, resource.id as number],
            type: dataset.type
          };
        }

        return dataset;
      })
    );
  };

  const changeResources =
    (index: number) => (_, resources: Array<SelectEntry>) => {
      setFieldValue(
        `datasetFilters.${datasetFilterIndex}.${index}.resources`,
        resources
      );
      setFieldTouched(`datasetFilters.${datasetFilterIndex}`, true, false);
      setSelectedDatasets(
        selectedDatasets.map((dataset, datasetIndex) => {
          if (equals(datasetIndex, index)) {
            return {
              ids: pluck('id', resources) as Array<number>,
              type: dataset.type
            };
          }

          return dataset;
        })
      );
    };

  const changeResourceType =
    (index: number) => (e: ChangeEvent<HTMLInputElement>) => {
      setFieldValue(`datasetFilters.${datasetFilterIndex}.${index}`, {
        resourceType: e.target.value,
        resources: []
      });

      setSelectedDatasets(
        selectedDatasets.map((dataset, datasetIndex) => {
          if (equals(datasetIndex, index)) {
            return { ids: [], type: e.target.value as ResourceTypeEnum };
          }

          return dataset;
        })
      );
    };

  const deleteResource = (index: number) => (): void => {
    setFieldValue(
      `datasetFilters.${datasetFilterIndex}`,
      (datasetFilter || []).filter((_, i) => !equals(i, index))
    );
    setFieldTouched(`datasetFilters.${datasetFilterIndex}`, true, false);
    setSelectedDatasets(selectedDatasets.filter((_, i) => !equals(i, index)));
  };

  const deleteResourceItem = ({ index, option, resources }): void => {
    const newResource = reject(propEq(option.id, 'id'), resources);

    setFieldValue(
      `datasetFilters.${datasetFilterIndex}.${index}.resources`,
      newResource
    );
    setFieldTouched(`datasetFilters.${datasetFilterIndex}`, true, false);
    setSelectedDatasets(
      selectedDatasets.map((dataset, i) => {
        if (equals(i, index)) {
          return {
            ids: dataset.ids.filter((id) => !equals(id, option.id)),
            type: dataset.type
          };
        }

        return dataset;
      })
    );
  };

  const buildSearchParameters = (): Array<QueryParameter> | undefined => {
    const previousDataset = last(
      init(values.datasetFilters[datasetFilterIndex])
    );
    if (isNil(previousDataset)) {
      return undefined;
    }

    const ids = previousDataset?.resources.map((resource) =>
      prop('id', resource)
    );

    const currentResourceType = last(value as Array<Dataset>)?.resourceType;
    if (isNil(currentResourceType)) {
      return undefined;
    }

    const searchParameter =
      searchParametersBySelectedResourceType[currentResourceType][
        previousDataset?.resourceType
      ];

    return [
      {
        name: 'search',
        value: { [searchParameter]: { $in: ids } }
      }
    ];
  };

  const getResourceBaseEndpoint =
    (resourceType: ResourceTypeEnum) =>
    (parameters): string => {
      return buildListingEndpoint({
        baseEndpoint: `${baseEndpoint}${resourceTypeBaseEndpoints[resourceType]}`,
        customQueryParameters: buildSearchParameters(),
        parameters: {
          ...parameters,
          limit: 30
        }
      });
    };

  const getSearchField = (resourceType: ResourceTypeEnum): string =>
    cond([
      [equals('host'), always('host.name')],
      [T, always('name')]
    ])(resourceType);

  return {
    addResource,
    changeResource,
    changeResourceType,
    changeResources,
    deleteResource,
    deleteResourceItem,
    error: errorToDisplay,
    getResourceBaseEndpoint,
    getResourceTypeOptions,
    getSearchField,
    lowestResourceTypeReached
  };
};

export default useDatasetFilter;
