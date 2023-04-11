import { ReactElement, useEffect } from 'react';

import { BrowserRouter as Router } from 'react-router-dom';
import { not, startsWith, tail } from 'ramda';
import { createStore } from 'jotai';

import { Module, QueryProvider } from '@centreon/ui';

const store = createStore();

interface Props {
  children: ReactElement;
}

const Provider = ({ children }: Props): JSX.Element | null => {
  const basename =
    (document
      .getElementsByTagName('base')[0]
      ?.getAttribute('href') as string) || '';

  const pathStartsWithBasename = startsWith(basename, window.location.pathname);

  useEffect(() => {
    if (pathStartsWithBasename) {
      return;
    }

    const path = tail(window.location.pathname);
    window.location.href = `${basename}${path}`;
  }, []);

  if (not(pathStartsWithBasename)) {
    return null;
  }

  return (
    <Router basename={basename}>
      <Module maxSnackbars={2} seedName="centreon" store={store}>
        <QueryProvider>{children}</QueryProvider>
      </Module>
    </Router>
  );
};

export default Provider;
