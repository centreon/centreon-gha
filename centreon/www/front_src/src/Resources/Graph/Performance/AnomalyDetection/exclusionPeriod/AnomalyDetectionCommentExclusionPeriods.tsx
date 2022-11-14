import { ChangeEvent, useState } from 'react';

import { makeStyles } from 'tss-react/mui';

import { TextField, Typography } from '@mui/material';
import Checkbox from '@mui/material/Checkbox';
import FormControlLabel from '@mui/material/FormControlLabel';

const useStyles = makeStyles()((theme) => ({
  container: {
    margin: theme.spacing(2, 0),
  },
  field: {
    width: '100%',
  },
}));

interface Props {
  isExclusionPeriodChecked: boolean;
  onChangeCheckedExclusionPeriod: (
    event: ChangeEvent<HTMLInputElement>,
  ) => void;
}

const AnomalyDetectionCommentExclusionPeriod = ({
  onChangeCheckedExclusionPeriod,
  isExclusionPeriodChecked,
}: Props): JSX.Element => {
  const { classes } = useStyles();
  const [comment, setComment] = useState(null);

  const changeComment = (event): void => {
    console.log(event);
  };

  return (
    <>
      <FormControlLabel
        control={
          <Checkbox
            checked={isExclusionPeriodChecked}
            onChange={onChangeCheckedExclusionPeriod}
          />
        }
        label="From beginning"
      />
      <div className={classes.container}>
        <Typography>Comment </Typography>
        <TextField
          multiline
          className={classes.field}
          rows={3}
          value={comment}
          variant="filled"
          onChange={changeComment}
        />
      </div>
    </>
  );
};

export default AnomalyDetectionCommentExclusionPeriod;
