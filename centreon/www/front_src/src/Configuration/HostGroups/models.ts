export type NamedEntity = {
  id: number;
  name: string;
};

export interface HostGroupListItem extends NamedEntity {
  alias: string | null;
  enabledHostsCount: number;
  disabledHostsCount: number;
  isActivated: boolean;
}

export type ListMeta = {
  limit: number;
  page: number;
  total: number;
};
export type List<TEntity> = {
  meta: ListMeta;
  result: Array<TEntity>;
};

export interface HostGroupItem extends NamedEntity {
  alias: string | null;
  geoCoords: string | null;
  comment: string | null;
  isActivated: boolean;
  hosts: Array<NamedEntity>;
  resourceAccessRules?: Array<NamedEntity>;
}
