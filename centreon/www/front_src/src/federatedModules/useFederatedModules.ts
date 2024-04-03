import { useCallback, useEffect } from 'react';

import { useAtom } from 'jotai';

import { getData, useRequest, useDeepCompare } from '@centreon/ui';

import usePlatformVersions from '../Main/usePlatformVersions';

import { federatedModulesAtom } from './atoms';
import { FederatedModule } from './models';
import { loadScript } from './utils';

export const getFederatedModuleFolder = (moduleName: string): string =>
  `./modules/${moduleName}/static`;

export const getFederatedModuleFederationFile = (moduleName: string): string =>
  `${getFederatedModuleFolder(moduleName)}/moduleFederation.json`;

interface UseFederatedModulesState {
  federatedModules: Array<FederatedModule> | null;
  getFederatedModulesConfigurations: () => void;
}

const useFederatedModules = (): UseFederatedModulesState => {
  const { sendRequest } = useRequest<FederatedModule>({
    request: getData
  });
  const [federatedModules, setFederatedModules] = useAtom(federatedModulesAtom);
  const { getModules } = usePlatformVersions();

  const modules = getModules();

  const getFederatedModulesConfigurations = useCallback((): void => {
    if (!modules) {
      return;
    }

    Promise.all(
      modules?.map((moduleName) =>
        sendRequest({ endpoint: getFederatedModuleFederationFile(moduleName) })
      ) || []
    ).then((federatedModuleConfigs: Array<FederatedModule>): void => {
      setFederatedModules(federatedModuleConfigs);

      federatedModuleConfigs
        .filter(({ preloadScript }) => preloadScript)
        .forEach(({ preloadScript, moduleName }) => {
          loadScript(
            `${getFederatedModuleFolder(moduleName)}/${preloadScript}`
          );
        });
    });
  }, [modules]);

  useEffect(
    () => {
      getFederatedModulesConfigurations();
    },
    useDeepCompare([modules])
  );

  return {
    federatedModules,
    getFederatedModulesConfigurations
  };
};

export default useFederatedModules;
