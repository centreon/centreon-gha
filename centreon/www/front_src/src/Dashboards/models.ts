// TODO merge cleanup

export enum ContactType {
  contact = 'contact',
  contactGroup = 'contact_group'
}

export interface DashboardShare extends NamedEntity {
  email: string | null;
  fullname: string | null;
  role: DashboardRole;
  type: ContactType;
}
