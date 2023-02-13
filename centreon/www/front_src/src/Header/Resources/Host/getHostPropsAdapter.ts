import numeral from 'numeral';

import { SeverityCode } from '@centreon/ui';
import type { SelectEntry } from '@centreon/ui';

import {
  getHostResourcesUrl,
  upCriterias,
  unreachableCriterias,
  downCriterias,
  pendingCriterias,
  unhandledStateCriterias,
  hostCriterias
} from '../getResourcesUrl';
import getDefaultCriterias from '../../../Resources/Filter/Criterias/default';
import type { Adapter } from '../useResourceCounters';
import type { Criteria } from '../../../Resources/Filter/Criterias/models';
import type { SubMenuProps } from '../../sharedUI/ResourceSubMenu';
import type { CounterProps } from '../../sharedUI/ResourceCounters';
import type { HostStatusResponse } from '../../api/decoders';

import {
  downStatusHosts,
  unreachableStatusHosts,
  upStatusHosts,
  allLabel,
  downLabel,
  pendingLabel,
  unreachableLabel,
  upLabel
} from './translatedLabels';

type ChangeFilterAndNavigate = (
  link: string,
  criterias: Array<Criteria>
) => (e: React.MouseEvent<HTMLLinkElement>) => void;

export interface HostPropsAdapterOutput {
  counters: CounterProps['counters'];
  hasPending: boolean;
  items: SubMenuProps['items'];
}

type GetHostPropsAdapter = Adapter<HostStatusResponse, HostPropsAdapterOutput>;

const formatCount = (
  unhandled: number | string,
  total: number | string
): string =>
  `${numeral(unhandled).format('0a')}/${numeral(total).format('0a')}`;

const getHostPropsAdapter: GetHostPropsAdapter = ({
  useDeprecatedPages,
  applyFilter,
  navigate,
  t,
  data
}) => {
  const changeFilterAndNavigate: ChangeFilterAndNavigate =
    (link, criterias) => (e) => {
      e.preventDefault();
      if (!useDeprecatedPages) {
        applyFilter({ criterias, id: '', name: 'New Filter' });
      }

      navigate(link);
    };

  const unhandledDownHostsCriterias = getDefaultCriterias({
    resourceTypes: hostCriterias.value,
    states: unhandledStateCriterias.value,
    statuses: downCriterias.value as Array<SelectEntry>
  });
  const unhandledDownHostsLink = useDeprecatedPages
    ? '/main.php?p=20202&o=h_down&search='
    : getHostResourcesUrl({
        stateCriterias: unhandledStateCriterias,
        statusCriterias: downCriterias
      });

  const unhandledUnreachableHostsCriterias = getDefaultCriterias({
    resourceTypes: hostCriterias.value,
    states: unhandledStateCriterias.value,
    statuses: unreachableCriterias.value as Array<SelectEntry>
  });
  const unhandledUnreachableHostsLink = useDeprecatedPages
    ? '/main.php?p=20202&o=h_unreachable&search='
    : getHostResourcesUrl({
        stateCriterias: unhandledStateCriterias,
        statusCriterias: unreachableCriterias
      });

  const upHostsCriterias = getDefaultCriterias({
    resourceTypes: hostCriterias.value,
    statuses: upCriterias.value as Array<SelectEntry>
  });
  const upHostsLink = useDeprecatedPages
    ? '/main.php?p=20202&o=h_up&search='
    : getHostResourcesUrl({
        statusCriterias: upCriterias
      });

  const hostsCriterias = getDefaultCriterias({
    resourceTypes: hostCriterias.value
  });
  const hostsLink = useDeprecatedPages
    ? '/main.php?p=20202&o=h&search='
    : getHostResourcesUrl();

  const pendingHostsCriterias = getDefaultCriterias({
    resourceTypes: hostCriterias.value,
    statuses: pendingCriterias.value as Array<SelectEntry>
  });
  const pendingHostsLink = useDeprecatedPages
    ? '/main.php?p=20202&o=h_pending&search='
    : getHostResourcesUrl({
        statusCriterias: pendingCriterias
      });

  const config = {
    all: {
      count: numeral(data.total).format('0a'),
      label: t(allLabel),
      onClick: changeFilterAndNavigate(hostsLink, hostsCriterias),
      serverityCode: null,
      shortCount: data.total,
      to: hostsLink
    },
    down: {
      count: formatCount(data.down.unhandled, data.down.total),
      label: t(downLabel),
      onClick: changeFilterAndNavigate(
        unhandledDownHostsLink,
        unhandledDownHostsCriterias
      ),
      severityCode: SeverityCode.High,
      shortCount: data.down.unhandled,
      to: unhandledDownHostsLink,
      topCounterAriaLabel: t(downStatusHosts)
    },
    pending: {
      count: numeral(data.pending).format('0a'),
      label: t(pendingLabel),
      onClick: changeFilterAndNavigate(pendingHostsLink, pendingHostsCriterias),
      severityCode: SeverityCode.Pending,
      shortCount: data.pending,
      to: pendingHostsLink
    },
    unreachable: {
      count: formatCount(data.unreachable.unhandled, data.unreachable.total),
      label: t(unreachableLabel),
      onClick: changeFilterAndNavigate(
        unhandledUnreachableHostsLink,
        unhandledUnreachableHostsCriterias
      ),
      severityCode: SeverityCode.Medium,
      shortCount: data.unreachable.unhandled,
      to: unhandledUnreachableHostsLink,
      topCounterAriaLabel: t(unreachableStatusHosts)
    },
    up: {
      count: numeral(data.ok).format('0a'),
      label: t(upLabel),
      onClick: changeFilterAndNavigate(upHostsLink, upHostsCriterias),
      severityCode: SeverityCode.Ok,
      shortCount: data.ok,
      to: upHostsLink,
      topCounterAriaLabel: t(upStatusHosts)
    }
  };

  return {
    counters: ['down', 'unreachable', 'up'].map((statusName) => {
      const { to, shortCount, topCounterAriaLabel, onClick, severityCode } =
        config[statusName];

      return {
        ariaLabel: topCounterAriaLabel,
        count: shortCount,
        onClick,
        severityCode,
        to
      };
    }),
    hasPending: Number(data.pending) > 0,
    items: ['down', 'unreachable', 'up', 'pending', 'all'].map((status) => {
      const { onClick, severityCode, count, label, to } = config[status];

      return {
        onClick,
        severityCode,
        submenuCount: count,
        submenuTitle: label,
        to
      };
    })
  };
};

export default getHostPropsAdapter;
