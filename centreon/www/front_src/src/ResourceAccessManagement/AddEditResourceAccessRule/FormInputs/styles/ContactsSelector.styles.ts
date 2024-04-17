import { makeStyles } from 'tss-react/mui';

export const useContactsSelectorStyles = makeStyles()((theme) => ({
  checkbox: {
    padding: theme.spacing(0.5)
  },
  container: {
    display: 'flex',
    flexDirection: 'column',
    justifyContent: 'space-evenly'
  },
  label: {
    marginLeft: theme.spacing(-0.25)
  },
  selector: {
    width: '100%'
  }
}));
