<<<<<<< HEAD
=======
import * as React from 'react';

>>>>>>> centreon/dev-21.10.x
import { ComponentColumnProps } from '@centreon/ui';

import ShortTypeChip from '../../ShortTypeChip';

const SeverityColumn = ({ row }: ComponentColumnProps): JSX.Element | null => {
  if (!row.severity_level) {
    return null;
  }

  return <ShortTypeChip label={row.severity_level?.toString()} />;
};

export default SeverityColumn;
