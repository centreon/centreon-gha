import { useAtomValue, useSetAtom } from 'jotai';

import { useMemoComponent } from '@centreon/ui';

import {
  getPanelConfigurationsDerivedAtom,
  getPanelOptionsAndDataDerivedAtom,
  setPanelOptionsAndDataDerivedAtom
} from '../../atoms';
import FederatedComponent from '../../../../components/FederatedComponents';
import { AddWidgetPanel } from '../../AddEditWidget';

interface Props {
  id: string;
  isAddWidgetPanel?: boolean;
}

const Panel = ({ id, isAddWidgetPanel }: Props): JSX.Element => {
  const getPanelOptionsAndData = useAtomValue(
    getPanelOptionsAndDataDerivedAtom
  );
  const getPanelConfigurations = useAtomValue(
    getPanelConfigurationsDerivedAtom
  );
  const setPanelOptions = useSetAtom(setPanelOptionsAndDataDerivedAtom);

  const panelOptionsAndData = getPanelOptionsAndData(id);

  const panelConfigurations = getPanelConfigurations(id);

  const changePanelOptions = (newPanelOptions): void => {
    setPanelOptions({ id, options: newPanelOptions });
  };

  return useMemoComponent({
    Component: isAddWidgetPanel ? (
      <AddWidgetPanel />
    ) : (
      <FederatedComponent
        isFederatedWidget
        id={id}
        panelData={panelOptionsAndData?.data}
        panelOptions={panelOptionsAndData?.options}
        path={panelConfigurations.path}
        setPanelOptions={changePanelOptions}
      />
    ),
    memoProps: [id, panelOptionsAndData]
  });
};

export default Panel;
