<<<<<<< HEAD
import { useState, useRef, useEffect } from 'react';

import axios from 'axios';
import * as yup from 'yup';
import { useAtomValue } from 'jotai/utils';

import { ClickAwayListener } from '@mui/material';
import makeStyles from '@mui/styles/makeStyles';

import { refreshIntervalAtom } from '@centreon/ui-context';

import MenuLoader from '../../components/MenuLoader';

export const useStyles = makeStyles((theme) => ({
  link: {
    textDecoration: 'none',
  },
  subMenuToggle: {
    backgroundColor: theme.palette.common.black,
    boxSizing: 'border-box',
    display: 'none',
    left: theme.spacing(0),
    padding: theme.spacing(1),
    position: 'absolute',
    textAlign: 'left',
    top: '100%',
    width: '100%',
    zIndex: theme.zIndex.mobileStepper,
  },
  subMenuToggleActive: {
    display: 'block',
  },
  wrapMiddleIcon: {
    display: 'flex',
  },
=======
import * as React from 'react';

import axios from 'axios';
import * as yup from 'yup';

import { ClickAwayListener, makeStyles } from '@material-ui/core';

import { useUserContext } from '@centreon/ui-context';

import MenuLoader from '../../components/MenuLoader';

export const useStyles = makeStyles(() => ({
  link: {
    textDecoration: 'none',
  },
>>>>>>> centreon/dev-21.10.x
}));

interface Props {
  children: (props) => JSX.Element;
  endpoint: string;
  loaderWidth: number;
  schema: yup.AnySchema;
}

const RessourceStatusCounter = <
  StatusCount extends {
    pending: number;
  },
>({
  endpoint,
  schema,
  children,
  loaderWidth,
}: Props): JSX.Element | null => {
<<<<<<< HEAD
  const [data, setData] = useState<StatusCount>();
  const [toggled, setToggled] = useState<boolean>();
  const [isAllowed, setIsAllowed] = useState<boolean>(true);

  const interval = useRef<number>();

  const refreshInterval = useAtomValue(refreshIntervalAtom);
=======
  const [data, setData] = React.useState<StatusCount>();
  const [toggled, setToggled] = React.useState<boolean>();
  const [isAllowed, setIsAllowed] = React.useState<boolean>(true);

  const interval = React.useRef<number>();

  const { refreshInterval } = useUserContext();
>>>>>>> centreon/dev-21.10.x

  const getData = (): void => {
    axios
      .get(`./api/${endpoint}`)
      .then(({ data: retrievedData }) => {
        schema.validate(retrievedData).then(() => {
          setData(retrievedData);
        });
      })
      .catch((error) => {
        if (error.response && error.response.status === 401) {
          setIsAllowed(false);
        }
      });
  };

<<<<<<< HEAD
  useEffect(() => {
=======
  React.useEffect(() => {
>>>>>>> centreon/dev-21.10.x
    getData();

    interval.current = window.setInterval(() => {
      getData();
    }, refreshInterval * 1000);

    return (): void => {
      clearInterval(interval.current);
    };
  }, []);

  const toggleDetailedView = (): void => {
    setToggled(!toggled);
  };

  if (!isAllowed) {
    return null;
  }

  if (!data) {
    return <MenuLoader width={loaderWidth} />;
  }

  const hasPending = data.pending > 0;

  return (
    <ClickAwayListener
      onClickAway={(): void => {
        if (!toggled) {
          return;
        }

        toggleDetailedView();
      }}
    >
      {children({ data, hasPending, toggleDetailedView, toggled })}
    </ClickAwayListener>
  );
};

export default RessourceStatusCounter;
