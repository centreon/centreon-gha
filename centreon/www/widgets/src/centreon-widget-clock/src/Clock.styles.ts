import { makeStyles } from 'tss-react/mui';

export const useClockStyles = makeStyles()((theme) => ({
  background: {
    '&[data-hasDescription="true"]': {
      top: 24
    },
    bottom: 0,
    left: 0,
    position: 'absolute',
    right: 0,
    top: 0,
    transition: `background-color linear ${theme.transitions.duration.short}ms`
  },
  clockHourMinuteSubLabel: {
    alignItems: 'center',
    display: 'grid',
    position: 'absolute',
    width: '100%'
  },
  clockInformation: {
    '&[data-isSmall="true"]': {
      gridTemplateColumns: '0.8fr minmax(30px, 0.2fr) 1fr'
    },
    alignItems: 'center',
    display: 'grid',
    gridTemplateColumns: '0.8fr minmax(110px, 0.5fr) 1fr',
    width: '100%'
  },
  clockLabel: {
    alignItems: 'flex-start',
    display: 'flex',
    height: '100%',
    justifyContent: 'center'
  },
  container: {
    color: theme.palette.common.white,
    display: 'grid',
    gridTemplateColumns: '100%',
    gridTemplateRows: '30px 1fr',
    height: '100%',
    overflow: 'hidden',
    position: 'relative',
    width: '100%',
    zIndex: 1
  },
  date: {
    justifySelf: 'start'
  },
  icon: {
    justifySelf: 'end'
  },
  timezone: {
    justifySelf: 'center'
  }
}));
