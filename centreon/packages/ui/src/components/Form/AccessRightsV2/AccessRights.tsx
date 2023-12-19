import { SelectEntry } from '../../..';

import { useAccessRightsStyles } from './AccessRights.styles';
import Actions from './Actions/Actions';
import List from './List/List';
import ListSkeleton from './List/ListSkeleton';
import Provider from './Provider';
import ShareInput from './ShareInput/ShareInput';
import Stats from './Stats/Stats';
import { AccessRightInitialValues, Endpoints, Labels } from './models';
import { useAccessRightsInitValues } from './useAccessRightsInitValues';

interface Props {
  cancel: ({ dirty, values }) => void;
  endpoints: Endpoints;
  initialValues: Array<AccessRightInitialValues>;
  labels: Labels;
  link?: string;
  loading?: boolean;
  roles: Array<SelectEntry>;
  submit: (values: Array<AccessRightInitialValues>) => void;
}

export const AccessRights = ({
  initialValues,
  roles,
  endpoints,
  submit,
  cancel,
  link,
  loading,
  labels
}: Props): JSX.Element => {
  const { classes } = useAccessRightsStyles();
  useAccessRightsInitValues({ initialValues });

  return (
    <div className={classes.container}>
      <ShareInput endpoints={endpoints} labels={labels.add} roles={roles} />
      {loading ? <ListSkeleton /> : <List labels={labels.list} roles={roles} />}
      <Stats labels={labels.list} />
      <Actions
        cancel={cancel}
        labels={labels.actions}
        link={link}
        submit={submit}
      />
    </div>
  );
};

export default (props: Props): JSX.Element => (
  <Provider>
    <AccessRights {...props} />
  </Provider>
);
