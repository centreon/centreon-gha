import dayjs from 'dayjs';
import humanizeDuration from 'humanize-duration';
import { useAtomValue } from 'jotai';
import localizedFormat from 'dayjs/plugin/localizedFormat';
import timezonePlugin from 'dayjs/plugin/timezone';
import utc from 'dayjs/plugin/utc';

import { userAtom } from '@centreon/ui-context';

import shortLocales from './sortLocales';

interface FormatParameters {
  date: Date | string;
  formatString: string;
}

export interface LocaleDateTimeFormat {
  format: (dateFormat: FormatParameters) => string;
  toDate: (date: Date | string) => string;
  toDateTime: (date: Date | string) => string;
  toHumanizedDuration: (duration: number) => string;
  toIsoString: (date: Date) => string;
  toTime: (date: Date | string) => string;
}

dayjs.extend(localizedFormat);
dayjs.extend(timezonePlugin);
dayjs.extend(utc);

const dateFormat = 'L';
const timeFormat = 'LT';
const dateTimeFormat = `${dateFormat} ${timeFormat}`;

const useLocaleDateTimeFormat = (): LocaleDateTimeFormat => {
  const { locale, timezone } = useAtomValue(userAtom);

  const format = ({ date, formatString }: FormatParameters): string => {
    const normalizedLocale = locale.substring(0, 2);

    const timezoneDate = dayjs(date).tz(timezone).locale(normalizedLocale);

    return timezoneDate.format(formatString);
  };

  const toDateTime = (date: Date | string): string => {
    return format({
      date,
      formatString: dateTimeFormat
    });
  };

  const toDate = (date: Date | string): string => {
    return format({
      date,
      formatString: dateFormat
    });
  };

  const toTime = (date: Date | string): string => {
    return format({
      date,
      formatString: timeFormat
    });
  };

  const toIsoString = (date: Date): string => {
    return `${new Date(date).toISOString().substring(0, 19)}Z`;
  };

  const toHumanizedDuration = (duration: number): string => {
    const humanizer = humanizeDuration.humanizer();
    humanizer.languages = shortLocales;
    const normalizedLocale = locale.substring(0, 2).toUpperCase();

    return humanizer(duration * 1000, {
      delimiter: ' ',
      fallbacks: ['shortEN'],
      language: `short${normalizedLocale}`,
      round: true,
      serialComma: false,
      spacer: ''
    });
  };

  return {
    format,
    toDate,
    toDateTime,
    toHumanizedDuration,
    toIsoString,
    toTime
  };
};

export { dateTimeFormat, dateFormat, timeFormat, useLocaleDateTimeFormat };
