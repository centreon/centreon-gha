import { includes, map, prop, reject } from 'ramda';
import { makeStyles } from 'tss-react/mui';

import { Chip } from '@mui/material';
import { UseAutocompleteProps } from '@mui/material/useAutocomplete';

import Autocomplete, { Props as AutocompleteProps } from '..';
import { SelectEntry } from '../..';
import Option from '../../Option';

const useStyles = makeStyles()((theme) => ({
  deleteIcon: {
    height: theme.spacing(1.5),
    width: theme.spacing(1.5)
  },
  tag: {
    backgroundColor: theme.palette.divider,
    fontSize: theme.typography.caption.fontSize
  }
}));

type Multiple = boolean;
type DisableClearable = boolean;
type FreeSolo = boolean;

export interface Props
  extends Omit<AutocompleteProps, 'renderTags' | 'renderOption' | 'multiple'>,
    Omit<
      UseAutocompleteProps<SelectEntry, Multiple, DisableClearable, FreeSolo>,
      'multiple'
    > {
  disableSortedOptions?: boolean;
  customRenderTags?: Function;
}

const MultiAutocompleteField = ({
  value,
  options,
  disableSortedOptions = false,
  customRenderTags,
  ...props
}: Props): JSX.Element => {
  const { classes } = useStyles();

  const renderTags = (renderedValue, getTagProps): Array<JSX.Element> =>
    renderedValue.map((option, index) => (
      <Chip
        classes={{
          deleteIcon: classes.deleteIcon,
          root: classes.tag
        }}
        key={option.id}
        label={option.name}
        size="medium"
        {...getTagProps({ index })}
      />
    ));

  const getLimitTagsText = (more): JSX.Element => <Option>{`+${more}`}</Option>;

  const values = (value as Array<SelectEntry>) || [];

  const isOptionSelected = ({ id }): boolean => {
    const valueIds = map(prop('id'), values);

    return includes(id, valueIds);
  };

  const autocompleteOptions = disableSortedOptions
    ? options
    : [...values, ...reject(isOptionSelected, options)];

  return (
    <Autocomplete
      disableCloseOnSelect
      displayOptionThumbnail
      multiple
      getLimitTagsText={getLimitTagsText}
      options={autocompleteOptions}
      renderOption={(renderProps, option, { selected }): JSX.Element => (
        <li
          key={option.id}
          {...(renderProps as React.HTMLAttributes<HTMLLIElement>)}
        >
          <Option checkboxSelected={selected}>{option.name}</Option>
        </li>
      )}
      renderTags={(renderedValue, getTagProps) => customRenderTags
        ? customRenderTags(renderTags(renderedValue, getTagProps))
        : renderTags(renderedValue, getTagProps)
      }
      value={value}
      {...props}
    />
  );
};

export default MultiAutocompleteField;
