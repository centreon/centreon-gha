import { ReactElement } from 'react';

import { useStyles } from './AccessRightsForm.styles';
import {
  AccessRightsFormProvider,
  AccessRightsFormProviderProps
} from './useAccessRightsForm';
import {
  ContactAccessRightInput,
  ContactAccessRightInputProps
} from './Input/ContactAccessRightInput';
import {
  ContactAccessRightsList,
  ContactAccessRightsListProps
} from './List/ContactAccessRightsList';
import {
  AccessRightsFormActions,
  AccessRightsFormActionsProps
} from './AccessRightsFormActions';
import {
  AccessRightsStats,
  AccessRightsStatsProps
} from './Stats/AccessRightsStats';

export type AccessRightsFormProps = {
  initialValues?: AccessRightsFormProviderProps['initialValues'];
  labels: AccessRightsFormLabels;
  onCancel?: AccessRightsFormActionsProps['onCancel'];
  onCopyLink?: AccessRightsFormActionsProps['onCopyLink'];
  onSubmit?: AccessRightsFormProviderProps['onSubmit'];
  options?: AccessRightsFormProviderProps['options'];
};

export type AccessRightsFormLabels = {
  actions: AccessRightsFormActionsProps['labels'];
  input: ContactAccessRightInputProps['labels'];
  list: ContactAccessRightsListProps['labels'];
  stats: AccessRightsStatsProps['labels'];
};

const AccessRightsForm = ({
  labels,
  initialValues,
  options,
  onSubmit,
  onCancel,
  onCopyLink
}: AccessRightsFormProps): ReactElement => {
  const { classes } = useStyles();

  return (
    <AccessRightsFormProvider
      initialValues={initialValues}
      options={options}
      onSubmit={onSubmit}
    >
      <div className={classes.accessRightsForm}>
        <ContactAccessRightInput labels={labels.input} />
        <span className={classes.accessRightsFormList}>
          <ContactAccessRightsList labels={labels.list} />
          <AccessRightsStats labels={labels.stats} />
        </span>
        <AccessRightsFormActions
          labels={labels.actions}
          onCancel={onCancel}
          onCopyLink={onCopyLink}
        />
      </div>
    </AccessRightsFormProvider>
  );
};

export { AccessRightsForm };
