import { makeStyles } from 'tss-react/mui';

interface StyleProps {
  windowHeight: number;
}

export const useStyles = makeStyles<StyleProps>()(
  (theme, { windowHeight }) => ({
    popper: {
      height: windowHeight / 2,
      overflow: 'auto'
    },
    root: {
      '> div': {
        '& div:nth-child(1)': {
          '> div': {
            '& .MuiDateCalendar-root': {
              height: theme.spacing(38)
            },
            '& .MuiMultiSectionDigitalClock-root': {
              maxHeight: theme.spacing(38)
            }
          }
        }
      }
    }
  })
);
