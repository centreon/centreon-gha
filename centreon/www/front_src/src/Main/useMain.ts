import { useEffect } from 'react';

import { useAtom, useSetAtom, useAtomValue } from 'jotai';
import { and, includes, isEmpty, isNil, not, or } from 'ramda';
import { useLocation, useNavigate, useSearchParams } from 'react-router-dom';

import { getData, useRequest, useSnackbar } from '@centreon/ui';
import { userAtom } from '@centreon/ui-context';

import { platformInstallationStatusDecoder } from '../api/decoders';
import { platformInstallationStatusEndpoint } from '../api/endpoint';
import { PlatformInstallationStatus } from '../api/models';
import reactRoutes from '../reactRoutes/routeMap';
import useFederatedModules from '../federatedModules/useFederatedModules';
import useFederatedWidgets from '../federatedModules/useFederatedWidgets';

import { platformInstallationStatusAtom } from './atoms/platformInstallationStatusAtom';
import useUser, { areUserParametersLoadedAtom } from './useUser';
import usePlatformVersions from './usePlatformVersions';
import useInitializeTranslation from './useInitializeTranslation';

export const router = {
  useNavigate
};

const useMain = (): void => {
  const { sendRequest: getPlatformInstallationStatus } =
    useRequest<PlatformInstallationStatus>({
      decoder: platformInstallationStatusDecoder,
      request: getData
    });
  const { showErrorMessage } = useSnackbar();

  const { getBrowserLocale, getInternalTranslation, i18next } =
    useInitializeTranslation();

  const [areUserParametersLoaded, setAreUserParametersLoaded] = useAtom(
    areUserParametersLoadedAtom
  );
  const user = useAtomValue(userAtom);

  const setPlatformInstallationStatus = useSetAtom(
    platformInstallationStatusAtom
  );

  const loadUser = useUser();
  const location = useLocation();
  const navigate = router.useNavigate();
  const [searchParameter] = useSearchParams();
  const { getPlatformVersions } = usePlatformVersions();
  useFederatedModules();
  useFederatedWidgets();

  const displayAuthenticationError = (): void => {
    const authenticationError = searchParameter.get('authenticationError');

    if (or(isNil(authenticationError), isEmpty(authenticationError))) {
      return;
    }

    showErrorMessage(authenticationError as string);
  };

  useEffect(() => {
    displayAuthenticationError();

    getPlatformInstallationStatus({
      endpoint: platformInstallationStatusEndpoint
    }).then((retrievedPlatformInstallationStatus) => {
      setPlatformInstallationStatus(retrievedPlatformInstallationStatus);

      if (
        !retrievedPlatformInstallationStatus?.isInstalled ||
        retrievedPlatformInstallationStatus.hasUpgradeAvailable
      ) {
        setAreUserParametersLoaded(false);

        return;
      }
      loadUser();
      getPlatformVersions();
    });
  }, []);

  useEffect((): void => {
    if (not(areUserParametersLoaded)) {
      return;
    }

    getInternalTranslation();
  }, [areUserParametersLoaded]);

  useEffect(() => {
    const canChangeToBrowserLanguage = and(
      isNil(areUserParametersLoaded),
      i18next.isInitialized
    );
    if (canChangeToBrowserLanguage) {
      i18next?.changeLanguage(getBrowserLocale());
    }

    const canRedirectToUserDefaultPage = and(
      areUserParametersLoaded,
      includes(location.pathname, [reactRoutes.login, '/'])
    );

    if (not(canRedirectToUserDefaultPage)) {
      return;
    }

    navigate(user.default_page as string);
  }, [location, areUserParametersLoaded, user]);
};

export default useMain;
