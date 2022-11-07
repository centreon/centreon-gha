<<<<<<< HEAD
import { useState } from 'react';

import { useTranslation } from 'react-i18next';
import { prop, isEmpty, path, isNil } from 'ramda';
import { useAtomValue } from 'jotai/utils';

import { Paper } from '@mui/material';
import makeStyles from '@mui/styles/makeStyles';
=======
import * as React from 'react';

import { useTranslation } from 'react-i18next';
import { prop, isEmpty, path, isNil } from 'ramda';

import { makeStyles, Paper } from '@material-ui/core';
>>>>>>> centreon/dev-21.10.x

import {
  useRequest,
  ListingModel,
  MultiAutocompleteField,
  SearchParameter,
} from '@centreon/ui';

import { labelEvent } from '../../../translatedLabels';
import { TabProps } from '..';
import InfiniteScroll from '../../InfiniteScroll';
import TimePeriodButtonGroup from '../../../Graph/Performance/TimePeriods';
<<<<<<< HEAD
import {
  customTimePeriodAtom,
  getDatesDerivedAtom,
  selectedTimePeriodAtom,
} from '../../../Graph/Performance/TimePeriods/timePeriodAtoms';
=======
import { useResourceContext } from '../../../Context';
>>>>>>> centreon/dev-21.10.x

import { types } from './Event';
import { TimelineEvent, Type } from './models';
import { listTimelineEventsDecoder } from './api/decoders';
import { listTimelineEvents } from './api';
import Events from './Events';
import LoadingSkeleton from './LoadingSkeleton';

type TimelineListing = ListingModel<TimelineEvent>;

const useStyles = makeStyles((theme) => ({
  filter: {
    padding: theme.spacing(2),
    paddingTop: 0,
  },
}));

const TimelineTab = ({ details }: TabProps): JSX.Element => {
  const classes = useStyles();
  const { t } = useTranslation();

<<<<<<< HEAD
=======
  const { getIntervalDates, selectedTimePeriod, customTimePeriod } =
    useResourceContext();

  const [start, end] = getIntervalDates();

>>>>>>> centreon/dev-21.10.x
  const translatedTypes = types.map((type) => ({
    ...type,
    name: t(type.name),
  })) as Array<Type>;

  const [selectedTypes, setSelectedTypes] =
<<<<<<< HEAD
    useState<Array<Type>>(translatedTypes);
=======
    React.useState<Array<Type>>(translatedTypes);
  const limit = 30;
>>>>>>> centreon/dev-21.10.x

  const { sendRequest, sending } = useRequest<TimelineListing>({
    decoder: listTimelineEventsDecoder,
    request: listTimelineEvents,
  });

<<<<<<< HEAD
  const getIntervalDates = useAtomValue(getDatesDerivedAtom);
  const selectedTimePeriod = useAtomValue(selectedTimePeriodAtom);
  const customTimePeriod = useAtomValue(customTimePeriodAtom);

  const [start, end] = getIntervalDates(selectedTimePeriod);

  const limit = 30;

=======
>>>>>>> centreon/dev-21.10.x
  const getSearch = (): SearchParameter | undefined => {
    if (isEmpty(selectedTypes)) {
      return undefined;
    }

    return {
      conditions: [
        {
          field: 'date',
          values: {
            $gt: start,
            $lt: end,
          },
        },
      ],
      lists: [
        {
          field: 'type',
          values: selectedTypes.map(prop('id')),
        },
      ],
    };
  };

  const timelineEndpoint = path(['links', 'endpoints', 'timeline'], details);

  const listTimeline = ({
    atPage,
  }: {
    atPage?: number;
  }): Promise<TimelineListing> => {
    return sendRequest({
      endpoint: timelineEndpoint,
      parameters: {
        limit,
        page: atPage,
        search: getSearch(),
      },
    });
  };

  const changeSelectedTypes = (_, typeIds): void => {
    setSelectedTypes(typeIds);
  };

  return (
    <InfiniteScroll
      details={details}
      filter={
        <Paper className={classes.filter}>
          <TimePeriodButtonGroup disableGraphOptions disablePaper />
          <MultiAutocompleteField
            fullWidth
            label={t(labelEvent)}
            limitTags={3}
            options={translatedTypes}
            value={selectedTypes}
            onChange={changeSelectedTypes}
          />
        </Paper>
      }
      limit={limit}
      loading={sending}
      loadingSkeleton={<LoadingSkeleton />}
      reloadDependencies={[
        selectedTypes,
        selectedTimePeriod?.id || customTimePeriod,
<<<<<<< HEAD
        timelineEndpoint,
=======
>>>>>>> centreon/dev-21.10.x
      ]}
      sendListingRequest={isNil(timelineEndpoint) ? undefined : listTimeline}
    >
      {({ infiniteScrollTriggerRef, entities }): JSX.Element => {
        return (
          <Events
            infiniteScrollTriggerRef={infiniteScrollTriggerRef}
            timeline={entities}
          />
        );
      }}
    </InfiniteScroll>
  );
};

export default TimelineTab;
