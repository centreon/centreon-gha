import { T, always, cond, equals, map } from 'ramda';

import { ResourceListing, Visualization } from './Listing/models';

export const formatStatusFilter = cond([
  [equals('success'), always(['ok', 'up'])],
  [equals('warning'), always(['warning'])],
  [equals('malfunction'), always(['down', 'critical'])],
  [equals('undefined'), always(['unreachable', 'unknown'])],
  [equals('pending'), always(['pending'])],
  [T, always([])]
]);

export const formatRessourcesResponse = ({
  data,
  displayType
}): ResourceListing => {
  if (!equals(displayType, Visualization.Host)) {
    return data;
  }

  const result = map(
    (item) => {
      return {
        ...item,
        children: item?.children?.resources,
        childrenCount: item?.children?.status_count
      };
    },
    data?.result || []
  );

  const hostsResponse = { ...data, result };

  return hostsResponse;
};
