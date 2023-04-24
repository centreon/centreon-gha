import dayjs from 'dayjs';

import { DateTimePicker } from '@mui/x-date-pickers';

import { CustomTimePeriodProperty } from '../../../Details/tabs/Graph/models';

interface Props {
  changeDate: (props) => void;
  date: Date | dayjs.Dayjs | null;
  disabled?: boolean;
  maxDate?: Date | dayjs.Dayjs;
  minDate?: Date | dayjs.Dayjs;
  onClosePicker?: (isClosed: boolean) => void;
  property: CustomTimePeriodProperty;
}

const DateTimePickerInput = ({
  date,
  maxDate,
  minDate,
  property,
  changeDate,
  disabled = false
}: Props): JSX.Element => {
  const changeTime = (
    newValue: dayjs.Dayjs | null): void => {
      changeDate({ date: dayjs(newValue).toDate(), property });
  };

  return (
    <DateTimePicker<dayjs.Dayjs>
      disabled={disabled}
      maxDate={maxDate && dayjs(maxDate)}
      minDate={minDate && dayjs(minDate)}
      value={dayjs(date)}
      onChange={changeTime}
    />
  );
};

export default DateTimePickerInput;
