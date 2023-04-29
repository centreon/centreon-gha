import { equals, isNil } from 'ramda';

import MailIcon from '@mui/icons-material/LocalPostOfficeOutlined';

import { ChannelsEnum, ResourcesTypeEnum, TimeperiodType } from '../models';

import { MessageType, NotificationType } from './models';

export const formatEntityNamed = ({
  id,
  name
}: TimeperiodType): { checked: boolean; label: string } => {
  return {
    checked: true,
    label: name
  };
};

const formatMessages = ({ channel, message, subject }: MessageType): any => {
  return {
    channel: {
      Icon: MailIcon,
      checked: !!equals(ChannelsEnum.Mail, channel),
      label: ChannelsEnum.Mail
    },
    message,
    subject
  };
};

const formatResource = ({ resources, resourceType }) => {
  const resource = resources.find((elm) => equals(elm.type, resourceType));

  if (!isNil(resource?.extra)) {
    return {
      ...resource,
      extra: {
        ...resource.extra,
        includeServices: {
          checked: false,
          label: 'Include servives for this host'
        }
      }
    };
  }

  return resource;
};

export const getInitialValues = ({
  id,
  name,
  isActivated,
  users,
  timeperiod,
  messages,
  resources
}: NotificationType): any => ({
  businessViews: formatResource({
    resourceType: ResourcesTypeEnum.BV,
    resources
  }),
  hostGroups: formatResource({ resourceType: ResourcesTypeEnum.HG, resources }),
  id,
  isActivated,
  messages: formatMessages(messages[0]),
  name,
  serviceGroups: formatResource({
    resourceType: ResourcesTypeEnum.SG,
    resources
  }),
  timeperiod: formatEntityNamed(timeperiod),
  users
});

export const emptyInitialValues = {
  businessViews: {
    events: [],
    ids: [],
    type: 'Business views'
  },
  hostGroups: {
    events: [],
    extra: {
      eventsServices: [],
      includeServices: {
        checked: false,
        label: 'Include servives for this host'
      }
    },
    ids: [],
    type: 'Host groups'
  },
  isActivated: false,
  messages: {
    channel: { Icon: MailIcon, checked: false, label: ChannelsEnum.Mail },
    message: '',
    subject: ''
  },
  name: '',
  serviceGroups: {
    events: [],
    ids: [],
    type: 'Service groups'
  },
  timeperiod: { checked: true, label: '24h/24 - 7/7 days' },
  users: [
    // { checked: false, label: 'Sir Kenny' },
    // { checked: false, label: 'Stevie G' },
    // { checked: false, label: 'Big Sammy' }
  ]
};
