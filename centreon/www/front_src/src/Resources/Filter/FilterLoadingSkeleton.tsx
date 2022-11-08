import * as React from 'react';

import { Skeleton } from '@material-ui/lab';

const FilterLoadingSkeleton = (): JSX.Element => {
  return <Skeleton height={33} style={{ transform: 'none' }} width={175} />;
};

export default FilterLoadingSkeleton;
