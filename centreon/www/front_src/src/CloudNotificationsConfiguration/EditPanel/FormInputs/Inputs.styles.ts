import { makeStyles } from 'tss-react/mui';
import { equals } from 'ramda';

import { ThemeMode } from '@centreon/ui-context';

interface StyleProps {
  isExtraFieldHiden: boolean;
}

export const useStyles = makeStyles<StyleProps>()(
  (theme, { isExtraFieldHiden }) => ({
    additionalLabel: {
      color: theme.palette.primary.main,
      fontSize: theme.typography.h6.fontSize,
      fontweight: theme.typography.fontWeightMedium,
      marginBottom: theme.spacing(1),
      marginTop: theme.spacing(1)
    },
    channels: {
      paddingBottom: theme.spacing(1),
      paddingTop: theme.spacing(3)
    },
    divider: {
      background: theme.palette.divider,
      height: theme.spacing(0.125)
    },
    emailTemplateTitle: {
      fontWeight: theme.typography.fontWeightBold
    },
    grid: {
      '& > div:nth-child(3)': {
        marginTop: theme.spacing(4)
      },
      rowGap: theme.spacing(1)
    },
    hostInput: {
      backgroundColor: equals(ThemeMode.light, theme.palette.mode)
        ? theme.palette.background.panelGroups
        : 'default',
      padding: theme.spacing(1.5, 1)
    },
    hostsGrid: {
      rowGap: isExtraFieldHiden ? 0 : theme.spacing(3)
    },
    input: {
      backgroundColor: equals(ThemeMode.light, theme.palette.mode)
        ? theme.palette.background.panelGroups
        : 'default',
      padding: theme.spacing(1)
    }
  })
);
