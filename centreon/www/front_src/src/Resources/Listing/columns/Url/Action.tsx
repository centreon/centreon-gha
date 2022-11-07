<<<<<<< HEAD
import { path } from 'ramda';

import IconAction from '@mui/icons-material/FlashOn';
=======
import * as React from 'react';

import { path } from 'ramda';

import IconAction from '@material-ui/icons/FlashOn';
>>>>>>> centreon/dev-21.10.x

import { ComponentColumnProps } from '@centreon/ui';

import UrlColumn from '.';

const ActionUrlColumn = ({ row }: ComponentColumnProps): JSX.Element => {
  const endpoint = path<string | undefined>(
    ['links', 'externals', 'action_url'],
    row,
  );

  return (
    <UrlColumn endpoint={endpoint} icon={<IconAction fontSize="small" />} />
  );
};

export default ActionUrlColumn;
