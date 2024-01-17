import { equals } from 'ramda';

import { useTheme } from '@mui/material';

import { MemoizedListing, SeverityCode } from '@centreon/ui';

import { rowColorConditions } from './colors';
import useListing from './useListing';
import { defaultSelectedColumnIds } from './Columns';
import { DisplayType, Resource, SortOrder } from './models';

interface ListingProps {
  displayType: DisplayType;
  limit?: number;
  refreshCount: number;
  refreshIntervalToUse: number | false;
  resources: Array<Resource>;
  selectedColumnIds?: Array<string>;
  setPanelOptions: (field, value) => void;
  sortField?: string;
  sortOrder?: SortOrder;
  states: Array<string>;
  statuses: Array<string>;
}

const Listing = ({
  displayType,
  refreshCount,
  refreshIntervalToUse,
  resources,
  states,
  statuses,
  setPanelOptions,
  limit,
  selectedColumnIds,
  sortField,
  sortOrder
}: ListingProps): JSX.Element => {
  const theme = useTheme();

  const {
    areColumnsSortable,
    selectColumns,
    resetColumns,
    changeSort,
    changeLimit,
    changePage,
    columns,
    page,
    isLoading,
    data
  } = useListing({
    displayType,
    limit,
    refreshCount,
    refreshIntervalToUse,
    resources,
    setPanelOptions,
    sortField,
    sortOrder,
    states,
    statuses
  });

  return (
    <MemoizedListing
      columnConfiguration={{
        selectedColumnIds: selectedColumnIds || defaultSelectedColumnIds,
        sortable: areColumnsSortable
      }}
      columns={columns}
      currentPage={(page || 1) - 1}
      getHighlightRowCondition={({ status }): boolean =>
        equals(status?.severity_code, SeverityCode.High)
      }
      limit={limit}
      loading={isLoading}
      memoProps={[data, sortField, sortOrder, page, isLoading, columns]}
      rowColorConditions={rowColorConditions(theme)}
      rows={data?.result}
      sortField={sortField}
      sortOrder={sortOrder}
      subItems={{
        canCheckSubItems: true,
        enable: true,
        getRowProperty: (): string => 'parent_resource',
        labelCollapse: 'Collapse',
        labelExpand: 'Expand'
      }}
      totalRows={data?.meta?.total}
      onLimitChange={changeLimit}
      onPaginate={changePage}
      onResetColumns={resetColumns}
      onSelectColumns={selectColumns}
      onSort={changeSort}
    />
  );
};

export default Listing;
