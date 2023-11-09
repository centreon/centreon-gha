import { Column, ColumnType } from '@centreon/ui';

import {
  labelActions,
  labelDescription,
  labelRules,
  labelStatus
} from '../../translatedLabels';

export const defaultQueryParams = {
  limit: 10,
  page: 1,
  search: {
    regex: {
      fields: ['rule'],
      value: ''
    }
  },
  sort: { name: 'asc' },
  total: 64
};

export const fillResourceAccessRules = (numberOfRows: number): unknown => {
  return Array.from(Array(numberOfRows).keys()).map((index) => ({
    description: `resourceAccessRule${index + 1}`,
    id: index + 1,
    is_enabled: !!(index % 2),
    name: `rule${index}`
  }));
};

export const getListingResponse = ({
  limit = 10,
  page = 1,
  rows = 64
}: {
  limit?: number;
  page?: number;
  rows?: number;
}): object => {
  return {
    meta: {
      limit,
      page,
      search: {},
      sort_by: {},
      total: 64
    },
    result: fillResourceAccessRules(rows)
  };
};

export const getListingColumns = (): Array<Column> => {
  return [
    {
      disablePadding: false,
      getFormattedString: ({ name }): string => name,
      id: 'rule',
      label: labelRules,
      sortField: 'rule',
      sortable: true,
      type: ColumnType.string
    },
    {
      disablePadding: false,
      getFormattedString: ({ description }): string => description,
      id: 'description',
      label: labelDescription,
      sortField: 'description',
      sortable: true,
      type: ColumnType.string
    },
    {
      // Component:
      clickable: true,
      disablePadding: true,
      id: 'actions',
      label: labelActions,
      type: ColumnType.component
    },
    {
      // Component:
      clickable: true,
      disablePadding: true,
      id: 'isEnabled',
      label: labelStatus,
      // sorting ?
      type: ColumnType.component
    }
  ];
};
