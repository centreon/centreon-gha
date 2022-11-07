<<<<<<< HEAD
import { createStore, applyMiddleware, compose } from 'redux';
import thunk from 'redux-thunk';

import createRootReducer from '../redux/reducers';

const createAppStore = (initialState = {}) => {
  const middlewares = [thunk];
=======
/* eslint-disable import/no-extraneous-dependencies */

import { createStore, applyMiddleware, compose } from 'redux';
import { routerMiddleware } from 'connected-react-router';
import { batchDispatchMiddleware } from 'redux-batched-actions';
import thunk from 'redux-thunk';
import { createBrowserHistory } from 'history';

import createRootReducer from '../redux/reducers';

export const history = createBrowserHistory({
  basename: document.baseURI.replace(window.location.origin, ''),
});

const createAppStore = (initialState = {}) => {
  const middlewares = [
    routerMiddleware(history),
    thunk,
    batchDispatchMiddleware,
  ];
>>>>>>> centreon/dev-21.10.x

  const composeEnhancers =
    typeof window === 'object' && window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__
      ? window.__REDUX_DEVTOOLS_EXTENSION_COMPOSE__({})
      : compose;

  const enhancer = composeEnhancers(applyMiddleware(...middlewares));

<<<<<<< HEAD
  const store = createStore(createRootReducer(), initialState, enhancer);
=======
  const store = createStore(createRootReducer(history), initialState, enhancer);
>>>>>>> centreon/dev-21.10.x

  return store;
};

export default createAppStore;
