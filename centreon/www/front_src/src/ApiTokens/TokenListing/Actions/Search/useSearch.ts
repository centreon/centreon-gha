import { useEffect, useMemo, useRef } from 'react';

import { useAtom, useAtomValue } from 'jotai';
import { isEmpty, isNil } from 'ramda';

import { getFoundFields } from '@centreon/ui';

import {
  creationDateAtom,
  creatorsAtom,
  expirationDateAtom,
  isRevokedAtom,
  usersAtom
} from '../Filter/atoms';
import { Fields } from '../Filter/models';

import { searchAtom } from './atoms';
import { fieldDelimiter } from './models';
import { adjustData } from './utils';

const useSearch = (): void => {
  const [search, setSearch] = useAtom(searchAtom);
  const users = useAtomValue(usersAtom);
  const creators = useAtomValue(creatorsAtom);
  const expirationDate = useAtomValue(expirationDateAtom);
  const creationDate = useAtomValue(creationDateAtom);
  const isRevoked = useAtomValue(isRevokedAtom);

  const newSearch = useRef('');

  const matchFieldDelimiter = new RegExp(`\\w+${fieldDelimiter}\\w*`, 'g');

  const matchSpecificWord = (word): RegExp =>
    new RegExp(`(?<=\\s|^)${word}(?=\\s|$)`, 'g');

  const searchableFieldData = [
    { data: users, field: Fields.UserName },
    { data: creators, field: Fields.CreatorName },
    {
      data: !isNil(creationDate) ? adjustData(creationDate) : [],
      field: Fields.CreationDate
    },
    {
      data: !isNil(expirationDate) ? adjustData(expirationDate) : [],
      field: Fields.ExpirationDate
    },
    {
      data: !isNil(isRevoked) ? adjustData(isRevoked) : [],
      field: Fields.IsRevoked
    }
  ];

  const constructData = ({ data, field }): string => {
    if (!isEmpty(data)) {
      return `${[field]}:${data.map(({ name }) => name).join(',')}`;
    }

    return '';
  };

  const clearEmptyFields = (input): string | null => {
    const fieldValueToDelete = input
      .map(({ data, field }) => {
        if (!isEmpty(data)) {
          return null;
        }

        const [searchData] = getFoundFields({
          fields: [field],
          value: search
        });

        if (!searchData) {
          return null;
        }

        return `${searchData?.field}:${searchData?.value}`;
      })
      .filter((item) => item);

    const updatedSearch = search
      .split(' ')
      .map((word) => {
        return fieldValueToDelete.some((wordToDelete) => wordToDelete === word)
          ? ''
          : word;
      })
      .filter((item) => item)
      .join(' ');

    return !isEmpty(fieldValueToDelete) ? updatedSearch : null;
  };

  const buildData = (): string => {
    newSearch.current = search;

    return searchableFieldData
      .map(({ data, field }) => {
        return constructData({ data, field });
      })
      .filter((item) => item)
      .join(' ');
  };

  useMemo(() => {
    const updatedSearch = clearEmptyFields(searchableFieldData);

    const data = buildData();

    newSearch.current = updatedSearch ?? search;

    const wordsIncomingData = data.split(' ').filter((item) => item);

    if (isEmpty(wordsIncomingData)) {
      return;
    }
    wordsIncomingData.forEach((word) => {
      const wordsWithFieldDelimiter = word.match(matchFieldDelimiter);

      if (!wordsWithFieldDelimiter) {
        const matchedSimpleWord = newSearch.current.match(
          matchSpecificWord(word)
        );
        if (!isEmpty(matchedSimpleWord)) {
          return;
        }
        newSearch.current = newSearch.current.concat(
          newSearch.current ? ' ' : '',
          word
        );
      }
      const searchableFieldIncomingData = getFoundFields({
        fields: Object.values(Fields),
        value: word
      });

      if (isEmpty(searchableFieldIncomingData)) {
        newSearch.current = newSearch.current.concat(
          newSearch.current ? ' ' : '',
          word
        );

        return;
      }
      const [incomingData] = searchableFieldIncomingData;

      const matchedSearchData = getFoundFields({
        fields: [incomingData.field],
        value: newSearch.current
      });

      if (isEmpty(matchedSearchData)) {
        newSearch.current = newSearch.current.concat(
          newSearch.current ? ' ' : '',
          `${incomingData.field}:${incomingData.value}`
        );

        return;
      }

      const [searchData] = matchedSearchData;

      if (incomingData.value === searchData.value) {
        return;
      }

      newSearch.current = newSearch.current.replace(
        `${searchData.field}:${searchData.value}`,
        `${searchData.field}:${incomingData.value}`
      );
    });
  }, [users.length, creators.length, creationDate, expirationDate, isRevoked]);

  useEffect(() => {
    setSearch(newSearch.current);
  }, [newSearch.current]);
};

export default useSearch;
