import { useAtomValue } from 'jotai';

import {
  ListingModel,
  buildListingEndpoint,
  useFetchQuery
} from '@centreon/ui';

import { hostGroupsListEndpoint } from '../api/endpoints';

import { equals } from 'ramda';
import {
  filtersAtom,
  limitAtom,
  pageAtom,
  sortFieldAtom,
  sortOrderAtom
} from './atoms';
import { HostGroupListItem, List } from './models';

interface LoadDataState {
  data?: List<HostGroupListItem>;
  isLoading: boolean;
}

const useLoadData = (): LoadDataState => {
  const sortOrder = useAtomValue(sortOrderAtom);
  const sortField = useAtomValue(sortFieldAtom);
  const page = useAtomValue(pageAtom);
  const limit = useAtomValue(limitAtom);
  const filters = useAtomValue(filtersAtom);

  const sort = { [sortField]: sortOrder };

  const searchConditions = [
    {
      field: 'name',
      values: {
        $rg: filters.name
      }
    },
    {
      field: 'alias',
      values: {
        $rg: filters.alias
      }
    },
    ...(equals(filters.enabled, filters.disabled)
      ? []
      : [
          {
            field: 'is_activated',
            values: {
              $eq: filters.enabled
            }
          }
        ])
  ];

  const { data, isFetching } = useFetchQuery<ListingModel<HostGroupListItem>>({
    getEndpoint: () =>
      buildListingEndpoint({
        baseEndpoint: hostGroupsListEndpoint,
        parameters: {
          limit: limit || 10,
          page: page || 1,
          search: {
            conditions: searchConditions
          },
          sort
        }
      }),
    getQueryKey: () => ['listHostGroups', sortField, sortOrder, limit, page],
    queryOptions: {
      refetchOnMount: false,
      staleTime: 0,
      suspense: false
    }
  });

  return { data, isLoading: isFetching };
};

export default useLoadData;
