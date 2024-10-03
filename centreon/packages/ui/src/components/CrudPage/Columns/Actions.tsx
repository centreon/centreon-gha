import { EditOutlined } from '@mui/icons-material';
import DeleteOutline from '@mui/icons-material/DeleteOutline';
import { Box } from '@mui/material';
import { useAtomValue, useSetAtom } from 'jotai';
import { isNil } from 'ramda';
import { useCallback } from 'react';
import { useTranslation } from 'react-i18next';
import { IconButton } from '../../Button';
import {
  isDeleteEnabledAtom,
  itemToDeleteAtom,
  openFormModalAtom
} from '../atoms';

interface Props<TData> {
  row: TData & {
    internalListingParentId?: number;
    internalListingParentRow: TData;
  };
}

const labelDelete = 'Delete';
const labelUpdate = 'Update';

const Actions = <TData extends { id: number; name: string }>({
  row
}: Props<TData>): JSX.Element => {
  const { t } = useTranslation();
  const isDeleteEnabled = useAtomValue(isDeleteEnabledAtom);
  const setItemToDelete = useSetAtom(itemToDeleteAtom);
  const setOpenFormModal = useSetAtom(openFormModalAtom);

  const askBeforeDelete = (): void => {
    setItemToDelete({
      id: row.id,
      name: row.name,
      parent: isNil(row.internalListingParentRow)
        ? undefined
        : {
            id: row.internalListingParentRow.id,
            name: row.internalListingParentRow.name
          }
    });
  };

  const updateRow = useCallback(() => setOpenFormModal(row.id), [row.id]);

  return (
    <Box
      sx={{
        display: 'flex',
        flexDirection: 'row',
        gap: 1,
        width: '100%',
        justifyContent: 'flex-end'
      }}
    >
      {isNil(row.internalListingParentRow) && (
        <IconButton
          size="small"
          icon={<EditOutlined fontSize="small" color="primary" />}
          onClick={updateRow}
          title={t(labelUpdate)}
        />
      )}
      {isDeleteEnabled && (
        <IconButton
          size="small"
          icon={<DeleteOutline fontSize="small" color="error" />}
          onClick={askBeforeDelete}
          title={t(labelDelete)}
        />
      )}
    </Box>
  );
};

export default Actions;
