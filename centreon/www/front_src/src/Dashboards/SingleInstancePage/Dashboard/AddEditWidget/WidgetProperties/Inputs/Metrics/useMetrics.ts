import { useCallback, useEffect, useMemo } from 'react';

import { useFormikContext } from 'formik';
import { useAtomValue } from 'jotai';
import {
  equals,
  identity,
  innerJoin,
  isEmpty,
  isNil,
  map,
  omit,
  pick,
  pluck,
  propEq,
  reject
} from 'ramda';

import { SelectEntry, useDeepCompare } from '@centreon/ui';

import {
  resourcesInputKeyDerivedAtom,
  widgetPropertiesAtom
} from '../../../atoms';
import {
  FormMetric,
  Metric,
  ServiceMetric,
  Widget,
  WidgetDataResource
} from '../../../models';
import { getDataProperty } from '../utils';

import { getIsMetaServiceSelected } from '../../../../Widgets/utils';
import { useListMetrics } from './useListMetrics';
import { useRenderOptions } from './useRenderOptions';

export const formatMetricName = (metric: FormMetric): string =>
  `${metric.name}${metric.unit ? ` (${metric.unit})` : ''}`;

interface UseMetricsOnlyState {
  changeMetric: (_, newMetric: SelectEntry | null) => void;
  changeMetrics: (_, newMetrics: Array<SelectEntry> | null) => void;
  deleteMetricItem: (index) => void;
  error?: string;
  getOptionLabel: (metric: FormMetric) => string;
  getTagLabel: (metric: FormMetric) => string;
  hasMultipleUnitsSelected: boolean;
  hasNoResources: () => boolean;
  hasTooManyMetrics: boolean;
  isLoadingMetrics: boolean;
  isTouched?: boolean;
  metricCount?: number;
  metricWithSeveralResources?: false | string;
  metrics: Array<Metric>;
  renderOptionsForMultipleMetricsAndResources: (
    _,
    option: FormMetric
  ) => JSX.Element;
  renderOptionsForSingleMetric: (_, option: FormMetric) => JSX.Element;
  resources: Array<WidgetDataResource>;
  selectedMetrics?: Array<Metric>;
}

const useMetrics = (propertyName: string): UseMetricsOnlyState => {
  const { values, setFieldValue, setFieldTouched, errors, touched } =
    useFormikContext<Widget>();

  const widgetProperties = useAtomValue(widgetPropertiesAtom);
  const resourcesInputKey = useAtomValue(resourcesInputKeyDerivedAtom);

  const resources = (
    resourcesInputKey ? values.data?.[resourcesInputKey] : []
  ) as Array<WidgetDataResource>;

  const value = useMemo<Array<FormMetric> | undefined>(
    () => getDataProperty({ obj: values, propertyName }),
    [getDataProperty({ obj: values, propertyName })]
  );

  const error = useMemo<string | undefined>(
    () => getDataProperty({ obj: errors, propertyName }),
    [getDataProperty({ obj: errors, propertyName })]
  );

  const isTouched = useMemo<boolean | undefined>(
    () => getDataProperty({ obj: touched, propertyName }),
    [getDataProperty({ obj: touched, propertyName })]
  );

  const {
    hasMultipleUnitsSelected,
    hasTooManyMetrics,
    isLoadingMetrics,
    metrics,
    metricCount,
    servicesMetrics
  } = useListMetrics({ resources, selectedMetrics: value });

  const getResourcesByMetricName = (
    metricName: string
  ): Array<{ metricId?: number } & Omit<ServiceMetric, 'metrics'>> => {
    const resourcesByMetricName = (servicesMetrics?.result || []).map(
      (service) =>
        service.metrics.find((metric) => equals(metric.name, metricName))
          ? {
              ...omit(['metrics'], service),
              metricId: service.metrics.find((metric) =>
                equals(metric.name, metricName)
              )?.id
            }
          : null,
      []
    );

    return resourcesByMetricName.filter(identity) as Array<
      { metricId?: number } & Omit<ServiceMetric, 'metrics'>
    >;
  };

  const {
    renderOptionsForSingleMetric,
    renderOptionsForMultipleMetricsAndResources
  } = useRenderOptions({
    getResourcesByMetricName,
    propertyName,
    value: value || []
  });

  const changeMetric = useCallback(
    (_, newMetric: SelectEntry | null): void => {
      setFieldValue(`data.${propertyName}`, [
        {
          ...newMetric,
          excludedMetrics: [],
          includeAllMetrics: true
        }
      ]);
      setFieldTouched(`data.${propertyName}`, true, false);
    },
    [propertyName]
  );

  const deleteMetricItem = useCallback(
    (option): void => {
      const newMetrics = reject(propEq(option.id, 'id'), value || []);

      setFieldValue(`data.${propertyName}`, newMetrics);
      setFieldTouched(`data.${propertyName}`, true, false);
    },
    [propertyName, value]
  );

  const changeMetrics = useCallback(
    (_, newMetrics: Array<SelectEntry> | null): void => {
      setFieldValue(`data.${propertyName}`, newMetrics || []);
      setFieldTouched(`data.${propertyName}`, true, false);
    },
    [propertyName]
  );

  const hasNoResources = useCallback(
    (): boolean => {
      if (!resources.length) {
        return true;
      }

      return resources.every((resource) => !resource.resources.length);
    },
    useDeepCompare([resources])
  );

  const getTagLabel = useCallback(
    (metric: FormMetric): string => {
      if (isNil(metric)) {
        return '';
      }

      const metricResources = getResourcesByMetricName(metric.name);

      const resourcesWithoutExcludedMetrics = reject(
        ({ metricId }) => (metric.excludedMetrics || []).includes(metricId),
        metricResources
      );

      return `${formatMetricName(metric)}/${resourcesWithoutExcludedMetrics.length}`;
    },
    [getResourcesByMetricName]
  );

  const getOptionLabel = useCallback((metric: FormMetric): string => {
    if (isNil(metric)) {
      return '';
    }

    return formatMetricName(metric);
  }, []);

  const getNumberOfResourcesRelatedToTheMetric = useCallback(
    (metricName: string): number =>
      (servicesMetrics?.result || []).reduce(
        (acc, service) =>
          acc +
          (service.metrics.find((metric) => equals(metric.name, metricName))
            ? 1
            : 0),
        0
      ),
    useDeepCompare([servicesMetrics])
  );

  const getFirstUsedResourceForMetric = useCallback(
    (metricName?: string): string | undefined => {
      if (!metricName) {
        return undefined;
      }

      const resource = (servicesMetrics?.result || []).filter((service) =>
        service.metrics.filter((metric) => equals(metric.name, metricName))
      )[0];

      return `${resource.parentName}:${resource.name}`;
    },
    useDeepCompare([servicesMetrics])
  );

  const metricWithSeveralResources = useMemo(
    () =>
      widgetProperties?.singleResourceSelection &&
      value?.some(
        ({ name }) => getNumberOfResourcesRelatedToTheMetric(name) > 1
      ) &&
      getFirstUsedResourceForMetric(value[0].name),
    useDeepCompare([
      widgetProperties?.singleResourceSelection,
      value,
      getNumberOfResourcesRelatedToTheMetric,
      getFirstUsedResourceForMetric
    ])
  );

  useEffect(
    () => {
      const isMetaServiceOnly = getIsMetaServiceSelected(resources);

      if (isNil(servicesMetrics)) {
        return;
      }

      if (isEmpty(resources)) {
        setFieldValue(`data.${propertyName}`, []);

        return;
      }

      if (isMetaServiceOnly && (isNil(value) || isEmpty(value))) {
        setFieldValue(
          `data.${propertyName}`,
          widgetProperties?.singleMetricSelection && !isEmpty(metrics)
            ? [metrics[0]]
            : metrics
        );

        return;
      }

      const baseMetricNames = pluck('name', metrics);
      const baseMetricIds = (servicesMetrics?.result || []).reduce(
        (acc, service) => {
          return [...acc, ...pluck('id', service.metrics)];
        },
        []
      );

      const intersectionBetweenMetricsIdsAndValues = innerJoin(
        (metric, name) => equals(metric.name, name),
        value || [],
        baseMetricNames
      );

      const intersectionFilteredExcludedMetrics =
        intersectionBetweenMetricsIdsAndValues
          .filter((item) => {
            if (isNil(item.excludedMetrics)) {
              return true;
            }
            const resourcesByMetricName = getResourcesByMetricName(item.name);

            return !equals(
              item.excludedMetrics.sort(),
              pluck('metricId', resourcesByMetricName).sort()
            );
          })
          .map((item) => {
            return {
              ...item,
              excludedMetrics:
                item.excludedMetrics?.filter((metric) =>
                  baseMetricIds.includes(metric)
                ) || []
            };
          });

      setFieldValue(
        `data.${propertyName}`,
        isEmpty(intersectionFilteredExcludedMetrics)
          ? []
          : intersectionFilteredExcludedMetrics
      );
    },
    useDeepCompare([servicesMetrics, resources])
  );

  useEffect(() => {
    const isMetaServiceOnly = getIsMetaServiceSelected(resources);

    if (isMetaServiceOnly || isEmpty(resources)) {
      return;
    }

    const services = map(
      pick(['uuid', 'id', 'name', 'parentName']),
      servicesMetrics?.result || []
    );

    setFieldValue('data.services', services);
  }, [values?.data?.[propertyName], resources]);

  return {
    changeMetric,
    changeMetrics,
    deleteMetricItem,
    error,
    getOptionLabel,
    getTagLabel,
    hasMultipleUnitsSelected,
    hasNoResources,
    hasTooManyMetrics,
    isLoadingMetrics,
    isTouched,
    metricCount,
    metricWithSeveralResources,
    metrics,
    renderOptionsForMultipleMetricsAndResources,
    renderOptionsForSingleMetric,
    resources,
    selectedMetrics: value
  };
};

export default useMetrics;
