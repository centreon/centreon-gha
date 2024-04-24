import { JsonDecoder } from 'ts.data.json';

import { buildListingDecoder } from '@centreon/ui';

import { PersonalInformation, Token } from '../TokenListing/models';
import { CreatedToken } from '../TokenCreation/models';

import { DeletedToken, DeletedTokens } from './models';

const personalInformationDecoder = (
  decoderName = 'personalInformation'
): JsonDecoder.Decoder<PersonalInformation> =>
  JsonDecoder.object<PersonalInformation>(
    {
      id: JsonDecoder.number,
      name: JsonDecoder.string
    },
    decoderName
  );

const tokenDecoder = JsonDecoder.object<Token>(
  {
    creationDate: JsonDecoder.string,
    creator: personalInformationDecoder('creator'),
    expirationDate: JsonDecoder.string,
    isRevoked: JsonDecoder.boolean,
    name: JsonDecoder.string,
    user: personalInformationDecoder('user')
  },
  'ListedToken',
  {
    creationDate: 'creation_date',
    expirationDate: 'expiration_date',
    isRevoked: 'is_revoked'
  }
);

export const listTokensDecoder = buildListingDecoder<Token>({
  entityDecoder: tokenDecoder,
  entityDecoderName: 'Tokens',
  listingDecoderName: 'listTokens'
});

const deletedTokenDecoder = JsonDecoder.object<DeletedToken>(
  {
    message: JsonDecoder.nullable(JsonDecoder.string),
    self: JsonDecoder.string,
    status: JsonDecoder.number
  },
  'deletedToken'
);

export const deletedTokensDecoder = JsonDecoder.object<DeletedTokens>(
  {
    results: JsonDecoder.array<DeletedToken>(
      deletedTokenDecoder,
      'deletedTokensResult'
    )
  },
  'DeletedTokens'
);
export const createdTokenDecoder = JsonDecoder.object<CreatedToken>(
  {
    creationDate: JsonDecoder.string,
    creator: personalInformationDecoder('creator'),
    expirationDate: JsonDecoder.string,
    isRevoked: JsonDecoder.boolean,
    name: JsonDecoder.string,
    token: JsonDecoder.string,
    user: personalInformationDecoder('user')
  },
  'CreatedToken',
  {
    creationDate: 'creation_date',
    expirationDate: 'expiration_date',
    isRevoked: 'is_revoked'
  }
);

const PersonalInformation = JsonDecoder.object<PersonalInformation>(
  {
    id: JsonDecoder.number,
    name: JsonDecoder.string
  },
  'PersonalInformation'
);

export const PersonalInformationDecoder =
  buildListingDecoder<PersonalInformation>({
    entityDecoder: PersonalInformation,
    entityDecoderName: 'PersonalInformationn',
    listingDecoderName: 'listPersonalInformation'
  });
