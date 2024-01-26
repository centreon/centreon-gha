import { useRef } from 'react';

import { useAtomValue } from 'jotai';

import { buildListingEndpoint, useFetchQuery } from '@centreon/ui';

import {
  limitAtom,
  pageAtom,
  sortFieldAtom,
  sortOrderAtom,
  searchAtom
} from '../components/DashboardLibrary/DashboardListing/atom';

import { Dashboard, resource } from './models';
import { dashboardsEndpoint } from './endpoints';
import { dashboardListDecoder } from './decoders';
import { List, ListQueryParams } from './meta.models';

type UseListDashboardProps<
  TQueryFnData extends List<Dashboard> = List<Dashboard>,
  TError = ResponseError,
  TData = TQueryFnData,
  TQueryKey extends QueryKey = QueryKey
> = {
  options?: Omit<
    UseQueryOptions<TQueryFnData, TError, TData, TQueryKey>,
    'queryKey' | 'queryFn' | 'initialData'
  >;
  params: ListQueryParams;
};

type UseListDashboards<
  TError = ResponseError,
  TData extends List<Dashboard> = List<Dashboard>
> = UseQueryResult<TData | TError, TError>;

const useListDashboards = (): UseListDashboards => {
  const isMounted = useRef(true);

  const page = useAtomValue(pageAtom);
  const limit = useAtomValue(limitAtom);
  const sortField = useAtomValue(sortFieldAtom);
  const sortOrder = useAtomValue(sortOrderAtom);
  const searchValue = useAtomValue(searchAtom);

  const sort = { [sortField]: sortOrder };
  const search = {
    regex: {
      fields: ['name'],
      value: searchValue
    }
  };

  const { data, isLoading } = useFetchQuery<List<Omit<Dashboard, 'refresh'>>>({
    decoder: dashboardListDecoder,
    doNotCancelCallsOnUnmount: true,
    getEndpoint: () =>
      buildListingEndpoint({
        baseEndpoint: dashboardsEndpoint,
        parameters: {
          limit: limit || 10,
          page: page || 1,
          search,
          sort
        }
      }),
    getQueryKey: () => [
      resource.dashboards,
      sortField,
      sortOrder,
      page,
      limit,
      search
    ],
    queryOptions: {
      suspense: isMounted.current
    }
  });

  if (isMounted) {
    isMounted.current = false;
  }

  return {
    ...queryData,
    data
  };
};

export { useListDashboards };
