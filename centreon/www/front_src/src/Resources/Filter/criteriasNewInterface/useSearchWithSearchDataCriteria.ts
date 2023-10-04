import { useMemo } from 'react';

import { useAtom } from 'jotai';
import { isEmpty, isNil, not, pipe } from 'ramda';

import { getFoundFields } from '@centreon/ui';

import { searchAtom } from '../filterAtoms';

const useSearchWihSearchDataCriteria = ({ selectableCriterias }) => {
  const [search, setSearch] = useAtom(searchAtom);

  const getBuiltCustomSearchedFields = useMemo(() => {
    return selectableCriterias
      ?.filter(pipe(({ searchData }) => searchData, isNil, not))
      .map(({ searchData }) => {
        const formattedCustomSearchedFields = searchData?.values?.map(
          ({ value }) => value
        );

        return {
          content: formattedCustomSearchedFields,
          field: searchData?.field
        };
      });
  }, [selectableCriterias]);

  const updatedSearchInput = useMemo(
    () =>
      getBuiltCustomSearchedFields?.reduce(
        (accumulator, currentValue) => {
          const { content } = currentValue;
          const { field } = currentValue;
          const target = `${field}:${content?.join(',')}`;

          const fieldInSearchInput = `${field}:`;
          const { updatedSearch } = accumulator;

          if (!isEmpty(content)) {
            if (search?.includes(fieldInSearchInput)) {
              const result = getFoundFields({ fields: [field], value: search });
              const formattedResult = `${result[0].field}:${result[0].value}`;

              const newSearch = updatedSearch || search;

              return {
                ...accumulator,
                updatedSearch: newSearch?.replace(formattedResult, target)
              };
            }

            return !updatedSearch
              ? { ...accumulator, updatedSearch: search.concat(' ', target) }
              : { ...accumulator };
          }

          return search;
        },
        { updatedSearch: '' }
      ),
    [search, getBuiltCustomSearchedFields]
  );

  const newSearch = isNil(updatedSearchInput?.updatedSearch)
    ? search
    : updatedSearchInput?.updatedSearch;

  setSearch(newSearch);

  return { newSearch };
};

export default useSearchWihSearchDataCriteria;
