import { atom } from 'jotai';
import { atomWithStorage } from 'jotai/utils';

import { filtersDefaultValue } from '../utils';

import { AdditionalConnectorListItem, NamedEntity } from './models';

type SortOrder = 'asc' | 'desc';

export const limitAtom = atom<number | undefined>(10);
export const pageAtom = atom<number | undefined>(undefined);
export const sortOrderAtom = atom<SortOrder>('asc');
export const sortFieldAtom = atom<string>('name');

export const connectorsToDeleteAtom = atom<AdditionalConnectorListItem | null>(
  null
);
export const connectorsToDuplicateAtom =
  atom<AdditionalConnectorListItem | null>(null);

export const filtersAtom = atomWithStorage<{
  name: string;
  pollers: Array<NamedEntity>;
  type: NamedEntity;
}>('acc-filters', filtersDefaultValue);
