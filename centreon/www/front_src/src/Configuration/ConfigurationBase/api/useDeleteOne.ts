import { useQueryClient } from '@tanstack/react-query';

import { Method, ResponseError, useMutationQuery } from '@centreon/ui';
import { useAtomValue } from 'jotai';
import { configurationAtom } from '../../atoms';

interface UseDeleteOneProps {
  deleteOneMutation: ({ id }) => Promise<object | ResponseError>;
  isMutating: boolean;
}

const useDeleteOne = (): UseDeleteOneProps => {
  const queryClient = useQueryClient();

  const configuration = useAtomValue(configurationAtom);
  const endpoint = configuration?.api?.endpoints?.deleteOne;

  const { isMutating, mutateAsync } = useMutationQuery({
    getEndpoint: endpoint,
    method: Method.DELETE,
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['listHostGroups'] });
    }
  });

  const deleteOneMutation = ({
    id
  }: {
    id: number;
  }) => {
    return mutateAsync({ _meta: { id } }, {});
  };

  return {
    deleteOneMutation,
    isMutating
  };
};

export default useDeleteOne;
