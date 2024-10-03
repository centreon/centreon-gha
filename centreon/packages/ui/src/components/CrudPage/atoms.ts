import { atom } from 'jotai';
import { ItemToDelete } from './models';

export const pageAtom = atom(0);
export const limitAtom = atom(10);
export const searchAtom = atom('');
export const sortOrderAtom = atom('asc');
export const sortFieldAtom = atom('name');
export const openFormModalAtom = atom<number | 'add' | null>(null);
export const itemToDeleteAtom = atom<ItemToDelete | null>(null);
export const isDeleteEnabledAtom = atom<boolean>(false);

export const changeSortAtom = atom(
  null,
  (_get, set, { sortOrder, sortField }) => {
    set(sortOrderAtom, sortOrder);
    set(sortFieldAtom, sortField);
  }
);
