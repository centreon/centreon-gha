import { withTranslation } from 'react-i18next';

import ServiceIcon from '@mui/icons-material/Grain';

import { MenuSkeleton } from '@centreon/ui';

import ItemLayout from '../../sharedUI/ItemLayout';
import ResourceCounters from '../../sharedUI/ResourceCounters';
import ResourceSubMenu from '../../sharedUI/ResourceSubMenu';
import useResourceCounters from '../useResourceCounters';
import { serviceStatusDecoder } from '../../api/decoders';
import type { ServiceStatusResponse } from '../../api/decoders';
import { serviceStatusEndpoint } from '../../api/endpoints';

import type { ServicesPropsAdapterOutput } from './getServicePropsAdapter';
import getServicePropsAdapter from './getServicePropsAdapter';

const ServiceStatusCounter = (): JSX.Element | null => {
  const { isLoading, data, isAllowed } = useResourceCounters<
    ServiceStatusResponse,
    ServicesPropsAdapterOutput
  >({
    adapter: getServicePropsAdapter,
    decoder: serviceStatusDecoder,
    endPoint: serviceStatusEndpoint,
    queryName: 'services-counters'
  });

  if (!isAllowed) {
    return null;
  }

  if (isLoading) {
    return <MenuSkeleton width={20} />;
  }

  return (
    data && (
      <ItemLayout
        Icon={ServiceIcon}
        renderIndicators={(): JSX.Element => (
          <ResourceCounters counters={data.counters} />
        )}
        renderSubMenu={(): JSX.Element => (
          <ResourceSubMenu items={data.items} />
        )}
        showPendingBadge={data.hasPending}
        title="Services"
      />
    )
  );
};

export default withTranslation()(ServiceStatusCounter);
