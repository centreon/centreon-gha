import React from 'react';

import { act, renderHook } from '@testing-library/react-hooks/dom';
import dayjs from 'dayjs';
import localizedFormatPlugin from 'dayjs/plugin/localizedFormat';
import timezonePlugin from 'dayjs/plugin/timezone';
import utcPlugin from 'dayjs/plugin/utc';
import { useAtomValue } from 'jotai';
import { equals } from 'ramda';

import { LocalizationProvider } from '@mui/x-date-pickers';

import { useLocaleDateTimeFormat } from '@centreon/ui';
import { userAtom } from '@centreon/ui-context';

import { CustomTimePeriodProperty } from '../../../Details/tabs/Graph/models';
import useDateTimePickerAdapter from '../../../useDateTimePickerAdapter';

import DateTimePickerInput from './DateTimePickerInput';

dayjs.extend(timezonePlugin);
dayjs.extend(utcPlugin);
dayjs.extend(localizedFormatPlugin);

const numberDaysInWeek = 7;

const days = [
  'Sunday',
  'Monday',
  'Tuesday',
  'Wednesday',
  'Thursday',
  'Friday',
  'Saturday'
];

const months2023 = [
  {
    January: {
      date: '2023-01-01T12:59:41.041Z',
      firstDay: { day: days[0], indexDayInRowWeek: 0, value: 1 },
      lastDay: { day: days[2], indexDayInRowWeek: 2, value: 31 },
      numberWeeks: 5
    }
  },
  {
    February: {
      date: '2023-02-01T12:59:41.041Z',
      firstDay: { day: days[3], indexDayInRowWeek: 3, value: 1 },
      lastDay: { day: days[2], indexDayInRowWeek: 2, value: 28 },
      numberWeeks: 5
    }
  },
  {
    March: {
      date: '2023-03-01T12:59:41.041Z',
      firstDay: { day: days[3], indexDayInRowWeek: 3, value: 1 },
      lastDay: { day: days[5], indexDayInRowWeek: 5, value: 31 },
      numberWeeks: 5
    }
  },
  {
    April: {
      date: '2023-04-01T12:59:41.041Z',
      firstDay: { day: days[6], indexDayInRowWeek: 6, value: 1 },
      lastDay: { day: days[0], indexDayInRowWeek: 0, value: 30 },
      numberWeeks: 6
    }
  },
  {
    May: {
      date: '2023-05-01T12:59:41.041Z',
      firstDay: { day: days[1], indexDayInRowWeek: 1, value: 1 },
      lastDay: { day: days[3], indexDayInRowWeek: 3, value: 31 },
      numberWeeks: 5
    }
  },
  {
    June: {
      date: '2023-06-01T12:59:41.041Z',
      firstDay: { day: days[4], indexDayInRowWeek: 4, value: 1 },
      lastDay: { day: days[5], indexDayInRowWeek: 5, value: 30 },
      numberWeeks: 5
    }
  },
  {
    July: {
      date: '2023-07-01T12:59:41.041Z',
      firstDay: { day: days[6], indexDayInRowWeek: 6, value: 1 },
      lastDay: { day: days[1], indexDayInRowWeek: 1, value: 31 },
      numberWeeks: 6
    }
  },
  {
    August: {
      date: '2023-08-01T12:59:41.041Z',
      firstDay: { day: days[2], indexDayInRowWeek: 2, value: 1 },
      lastDay: { day: days[4], indexDayInRowWeek: 4, value: 31 },
      numberWeeks: 5
    }
  },
  {
    September: {
      date: '2023-09-01T12:59:41.041Z',
      firstDay: { day: days[5], indexDayInRowWeek: 5, value: 1 },
      lastDay: { day: days[6], indexDayInRowWeek: 6, value: 30 },
      numberWeeks: 5
    }
  },
  {
    October: {
      date: '2023-10-01T12:59:41.041Z',
      firstDay: { day: days[0], indexDayInRowWeek: 0, value: 1 },
      lastDay: { day: days[2], indexDayInRowWeek: 2, value: 31 },
      numberWeeks: 5
    }
  },
  {
    November: {
      date: '2023-11-01T12:59:41.041Z',
      firstDay: { day: days[3], indexDayInRowWeek: 3, value: 1 },
      lastDay: { day: days[4], indexDayInRowWeek: 4, value: 30 },
      numberWeeks: 5
    }
  },
  {
    December: {
      date: '2023-12-01T12:59:41.041Z',
      firstDay: { day: days[5], indexDayInRowWeek: 5, value: 1 },
      lastDay: { day: days[0], indexDayInRowWeek: 0, value: 31 },
      numberWeeks: 6
    }
  }
];

const month2023Reverse = [
  {
    December: {
      date: '2023-12-01T12:59:41.041Z',
      firstDay: { day: days[5], indexDayInRowWeek: 5, value: 1 },
      lastDay: { day: days[0], indexDayInRowWeek: 0, value: 31 },
      numberWeeks: 6
    }
  },
  {
    November: {
      date: '2023-11-01T12:59:41.041Z',
      firstDay: { day: days[3], indexDayInRowWeek: 3, value: 1 },
      lastDay: { day: days[4], indexDayInRowWeek: 4, value: 30 },
      numberWeeks: 5
    }
  },
  {
    October: {
      date: '2023-10-01T12:59:41.041Z',
      firstDay: { day: days[0], indexDayInRowWeek: 0, value: 1 },
      lastDay: { day: days[2], indexDayInRowWeek: 2, value: 31 },
      numberWeeks: 5
    }
  },
  {
    September: {
      date: '2023-09-01T12:59:41.041Z',
      firstDay: { day: days[5], indexDayInRowWeek: 5, value: 1 },
      lastDay: { day: days[6], indexDayInRowWeek: 6, value: 30 },
      numberWeeks: 5
    }
  },
  {
    August: {
      date: '2023-08-01T12:59:41.041Z',
      firstDay: { day: days[2], indexDayInRowWeek: 2, value: 1 },
      lastDay: { day: days[4], indexDayInRowWeek: 4, value: 31 },
      numberWeeks: 5
    }
  },
  {
    July: {
      date: '2023-07-01T12:59:41.041Z',
      firstDay: { day: days[6], indexDayInRowWeek: 6, value: 1 },
      lastDay: { day: days[1], indexDayInRowWeek: 1, value: 31 },
      numberWeeks: 6
    }
  },
  {
    June: {
      date: '2023-06-01T12:59:41.041Z',
      firstDay: { day: days[4], indexDayInRowWeek: 4, value: 1 },
      lastDay: { day: days[5], indexDayInRowWeek: 5, value: 30 },
      numberWeeks: 5
    }
  },
  {
    May: {
      date: '2023-05-01T12:59:41.041Z',
      firstDay: { day: days[1], indexDayInRowWeek: 1, value: 1 },
      lastDay: { day: days[3], indexDayInRowWeek: 3, value: 31 },
      numberWeeks: 5
    }
  },
  {
    April: {
      date: '2023-04-01T12:59:41.041Z',
      firstDay: { day: days[6], indexDayInRowWeek: 6, value: 1 },
      lastDay: { day: days[0], indexDayInRowWeek: 0, value: 30 },
      numberWeeks: 6
    }
  },
  {
    March: {
      date: '2023-03-01T12:59:41.041Z',
      firstDay: { day: days[3], indexDayInRowWeek: 3, value: 1 },
      lastDay: { day: days[5], indexDayInRowWeek: 5, value: 31 },
      numberWeeks: 5
    }
  },
  {
    February: {
      date: '2023-02-01T12:59:41.041Z',
      firstDay: { day: days[3], indexDayInRowWeek: 3, value: 1 },
      lastDay: { day: days[2], indexDayInRowWeek: 2, value: 28 },
      numberWeeks: 5
    }
  },
  {
    January: {
      date: '2023-01-01T12:59:41.041Z',
      firstDay: { day: days[0], indexDayInRowWeek: 0, value: 1 },
      lastDay: { day: days[2], indexDayInRowWeek: 2, value: 31 },
      numberWeeks: 5
    }
  }
];

const dataTest = [
  {
    button: 'Next month',
    data: months2023,
    initialDate: '2023-01-01T12:59:41.041Z',
    timezone: 'Europe/Paris'
  },
  {
    button: 'Previous month',
    data: month2023Reverse,
    initialDate: '2023-12-01T12:59:41.041Z',
    timezone: 'Europe/Paris'
  }
];

enum ButtonCalendar {
  NMONTH = 'Next month',
  PMONTH = 'Previous month'
}
interface GetPreviousNextMonth {
  currentMonth: string;
  labelButton: string;
}

const getPreviousNextMonth = ({
  currentMonth,
  labelButton
}: GetPreviousNextMonth): void => {
  switch (labelButton) {
    case ButtonCalendar.PMONTH:
      if (!equals(currentMonth, 'January')) {
        cy.get(`[aria-label="${labelButton}"]`).click();
      }

      break;
    case ButtonCalendar.NMONTH:
      if (!equals(currentMonth, 'December')) {
        cy.get(`[aria-label="${labelButton}"]`).click();
      }
      break;
    default:
      break;
  }
};

const changeDate = (): void => undefined;

const setStart = undefined;
const checkIfDuplicateExists = (arr: Array<unknown>): boolean => {
  return new Set(arr).size !== arr.length;
};

dataTest.forEach((item) =>
  describe(`calendar`, () => {
    before(() => {
      const userData = renderHook(() => useAtomValue(userAtom));

      act(() => {
        userData.result.current.timezone = item.timezone;
        userData.result.current.locale = 'en_US';
      });
    });

    beforeEach(() => {
      const { result } = renderHook(() => useDateTimePickerAdapter());

      act(() => {
        const { Adapter } = result.current;
        cy.mount(
          <LocalizationProvider adapterLocale="en" dateAdapter={Adapter}>
            <DateTimePickerInput
              changeDate={changeDate}
              date={new Date(item.initialDate)}
              property={CustomTimePeriodProperty.start}
              setDate={setStart}
            />
          </LocalizationProvider>
        );
      });
    });

    it('check input calendar value contains correct date', () => {
      const { result } = renderHook(() => useLocaleDateTimeFormat());
      act(() => {
        const { format } = result.current;

        const dateInput = format({
          date: dayjs(item.initialDate).tz(item.timezone),
          formatString: 'L hh:mm A'
        });

        cy.get('input').should('have.value', dateInput);
      });
    });

    it(`check number of days in current month , when clicking on ${item.button} button`, () => {
      cy.get('input').click();
      item.data.forEach((element) => {
        const { lastDay } = Object.values(element)[0];

        cy.get('[role="rowgroup"]').children().as('listWeeks');
        cy.get('@listWeeks').children('button').as('days');
        cy.get('@days').should('have.length', lastDay.value);

        const currentMonth = Object.keys(element)[0];
        getPreviousNextMonth({ currentMonth, labelButton: item.button });
      });
    });

    it(`days should not be duplicated in each month of the year, when clicking on ${item.button} button`, () => {
      cy.get('input').click();
      item.data.forEach((element) => {
        const daysInCurrentMonth: Array<string> = [];
        cy.get('[role="rowgroup"]').first().children().as('listWeeks');
        cy.get('@listWeeks').children('button').as('days');

        cy.get('@days')
          .each(($day) => daysInCurrentMonth.push($day.text()))
          .as('currentDays');

        cy.get('@currentDays').then(() => {
          const isDuplicateExist = checkIfDuplicateExists(daysInCurrentMonth);

          return expect(isDuplicateExist).to.be.false;
        });

        const currentMonth = Object.keys(element)[0];
        getPreviousNextMonth({ currentMonth, labelButton: item.button });
      });
    });

    it(`the first/last day of the current month ,must correspond to the beginning/end of the week to this current month , when clicking on ${item.button} button`, () => {
      cy.get('input').click();

      item.data.forEach((element) => {
        const { firstDay, lastDay, numberWeeks } = Object.values(element)[0];

        cy.get('[role="rowgroup"]').children().as('listWeeks');

        cy.get('@listWeeks').should('have.length', numberWeeks);
        cy.get('@listWeeks').eq(0).as('firstWeek');
        cy.get('@firstWeek').children().as('listDaysInFirstWeek');

        cy.get('@listDaysInFirstWeek').should('have.length', numberDaysInWeek);
        cy.get('@listDaysInFirstWeek')
          .eq(firstDay.indexDayInRowWeek)
          .as('firstDayInWeek');
        cy.get('@firstDayInWeek').contains(firstDay.value);

        cy.get('@listWeeks')
          .eq(numberWeeks - 1)
          .as('lastWeek');

        cy.get('@lastWeek').children().as('listDaysInLastWeek');
        cy.get('@listDaysInLastWeek').should('have.length', numberDaysInWeek);

        cy.get('@listDaysInLastWeek')
          .eq(lastDay.indexDayInRowWeek)
          .as('lastDayInWeek');
        cy.get('@lastDayInWeek').contains(lastDay.value);
        const currentMonth = Object.keys(element)[0];
        getPreviousNextMonth({ currentMonth, labelButton: item.button });
      });
    });

    it(`the correspond month and year must be displayed in the calendars header ,when clicking on ${item.button} button`, () => {
      cy.get('input').click();
      const { result } = renderHook(() => useLocaleDateTimeFormat());

      item.data.forEach((element) => {
        const { date } = Object.values(element)[0];

        act(() => {
          const { format } = result.current;
          const monthAndYear = format({
            date: dayjs(date).tz(item.timezone),
            formatString: 'MMMM YYYY'
          });
          cy.contains(monthAndYear);
        });
        const currentMonth = Object.keys(element)[0];
        getPreviousNextMonth({ currentMonth, labelButton: item.button });
      });
    });

    it(`the appropriate name of days should be displayed on calendar header, when clicking on ${item.button} button`, () => {
      cy.get('input').click();
      const { result } = renderHook(() => useLocaleDateTimeFormat());

      item.data.forEach((element) => {
        const { date } = Object.values(element)[0];
        const dateByTimeZone = dayjs(date).tz(item.timezone);
        const firstDay = dateByTimeZone.isUTC()
          ? dateByTimeZone.utc().startOf('month').startOf('week')
          : dateByTimeZone.startOf('month').startOf('week');

        act(() => {
          const { format } = result.current;
          const daysArray = [0, 1, 2, 3, 4, 5, 6].map((diff) =>
            format({
              date: firstDay.add(diff, 'day'),
              formatString: 'dd'
            })
          );
          daysArray.forEach((day) => cy.contains(day.toUpperCase()));
          const currentMonth = Object.keys(element)[0];
          getPreviousNextMonth({ currentMonth, labelButton: item.button });
        });
      });
    });
    it(`check calendar for current month , tz=${item.timezone} when clicking on ${item.button}`, () => {
      cy.get('input').click();

      item.data.forEach((element) => {
        cy.matchImageSnapshot(
          `calendar-${item.timezone}-${
            Object.keys(element)[0]
          }-when-clicking-on-${item.button}`
        );

        const currentMonth = Object.keys(element)[0];
        getPreviousNextMonth({ currentMonth, labelButton: item.button });
      });
    });
  })
);
