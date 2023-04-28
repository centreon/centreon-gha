import { always, cond, equals, T } from 'ramda';

import MailIcon from '@mui/icons-material/MailOutline';
import SMSIcon from '@mui/icons-material/TextsmsOutlined';
import { Grid, Box } from '@mui/material';

import type { ComponentColumnProps } from '@centreon/ui';

import { ResourcesType, ResourcesTypeEnum, ChannelsEnum } from '../models';

interface FormatChannelProps {
  channel: ChannelsEnum;
}

const formatSingleResource = cond([
  [equals(ResourcesTypeEnum.SG), always('SG')],
  [equals(ResourcesTypeEnum.HG), always('HG')],
  [equals(ResourcesTypeEnum.BV), always('BV')],
  [T, always('N/A')]
]);

export const formatResourcesForListing = (
  resources: Array<ResourcesType>
): string => {
  const result = resources
    .map(({ type, count }) => {
      return `${count} ${formatSingleResource(type)}`;
    })
    .join(', ');

  return result;
};

export const FormatChannel = ({ channel }: FormatChannelProps): JSX.Element => {
  switch (channel) {
    case ChannelsEnum.Mail:
      return <MailIcon fontSize="small" />;
    case ChannelsEnum.Sms:
      return <SMSIcon fontSize="small" />;
    default:
      return <Box />;
  }
};

export const FormatChannels = ({ row }: ComponentColumnProps): JSX.Element => {
  return (
    <Grid container spacing={1}>
      {row.channels.map((channel) => {
        return (
          <Grid item key={channel}>
            <FormatChannel channel={channel} />
          </Grid>
        );
      })}
    </Grid>
  );
};
