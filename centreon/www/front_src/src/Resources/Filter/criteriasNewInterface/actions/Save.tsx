import { useState } from 'react';

import { useTranslation } from 'react-i18next';

import ArrowDropDownIcon from '@mui/icons-material/ArrowDropDown';
import Button from '@mui/material/Button';
import ButtonGroup from '@mui/material/ButtonGroup';
import ClickAwayListener from '@mui/material/ClickAwayListener';
import MenuItem from '@mui/material/MenuItem';
import MenuList from '@mui/material/MenuList';
import Paper from '@mui/material/Paper';

import { PopoverData } from '../../Criterias/models';

interface Save {
  canSaveFilter;
  getIsCreateFilter;
  getIsUpdateFilter;
  isNewFilter;
  popoverData: PopoverData | undefined;
}

const Save = ({
  isNewFilter,
  canSaveFilter,
  getIsCreateFilter,
  getIsUpdateFilter,
  popoverData
}: Save): JSX.Element => {
  const { t } = useTranslation();
  const { setAnchorEl = undefined } = popoverData;
  const [open, setOpen] = useState(false);
  const [selectedOption, setSelectedOption] = useState();

  const handleToggle = (): void => {
    setOpen(!open);
  };

  const handleClose = (): void => {
    setOpen(false);
  };

  const saveAsNew = (): void => {
    getIsCreateFilter(true);
    setAnchorEl?.(undefined);
  };

  const saveAs = (): void => {
    getIsUpdateFilter(true);
    setAnchorEl?.(undefined);
  };

  const options = [
    {
      disabled: !isNewFilter,
      onClick: saveAsNew,
      title: 'Save as new'
    },
    {
      disabled: !canSaveFilter,
      onClick: saveAs,
      title: 'Save as'
    }
  ];

  const selectOption = (option): void => {
    setSelectedOption(option);
    handleToggle();
  };
  const defaultCurrentOption =
    options.find(({ disabled }) => !disabled) ||
    options.find(({ title }) => title === 'Save as new');

  return (
    <ClickAwayListener onClickAway={handleClose}>
      <ButtonGroup
        aria-label="split button"
        style={{ backgroundColor: '#255891', maxHeight: 36 }}
        variant="contained"
      >
        <div style={{ display: 'flex', flexDirection: 'column' }}>
          <div style={{ display: 'flex', flexDirection: 'row', minWidth: 99 }}>
            <Button
              disabled={
                selectedOption?.disabled ?? defaultCurrentOption?.disabled
              }
              style={{ height: 35 }}
              onClick={selectedOption?.onClick || defaultCurrentOption?.onClick}
            >
              {selectedOption?.title || defaultCurrentOption?.title}
            </Button>
            <Button size="small" onClick={handleToggle}>
              <ArrowDropDownIcon />
            </Button>
          </div>

          {open && (
            <Paper>
              <MenuList autoFocusItem id="split-button-menu">
                {options.map((option, index) => (
                  <MenuItem
                    disabled={option.disabled}
                    key={option.title}
                    onClick={() => selectOption(option)}
                  >
                    {option.title}
                  </MenuItem>
                ))}
              </MenuList>
            </Paper>
          )}
        </div>
      </ButtonGroup>
    </ClickAwayListener>
  );
};

export default Save;
