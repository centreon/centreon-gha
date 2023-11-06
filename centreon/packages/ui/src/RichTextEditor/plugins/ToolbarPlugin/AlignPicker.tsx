import { useCallback, useEffect, useState } from 'react';

import {
  $getSelection,
  $isElementNode,
  $isRangeSelection,
  ElementFormatType,
  FORMAT_ELEMENT_COMMAND
} from 'lexical';
import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { equals } from 'ramda';

import FormatAlignLeftIcon from '@mui/icons-material/FormatAlignLeft';
import FormatAlignCenterIcon from '@mui/icons-material/FormatAlignCenter';
import FormatAlignRightIcon from '@mui/icons-material/FormatAlignRight';
import { OverridableComponent } from '@mui/material/OverridableComponent';
import { SvgIconTypeMap } from '@mui/material';

import { Menu } from '../../../components';
import { getSelectedNode } from '../../utils/getSelectedNode';

const formatOptions: Array<{
  Icon: OverridableComponent<SvgIconTypeMap<object, 'svg'>>;
  label: string;
  value: ElementFormatType;
}> = [
  {
    Icon: FormatAlignLeftIcon,
    label: 'Left',
    value: 'left'
  },
  {
    Icon: FormatAlignCenterIcon,
    label: 'Center',
    value: 'center'
  },
  {
    Icon: FormatAlignRightIcon,
    label: 'Right',
    value: 'right'
  }
];

interface Props {
  disabled: boolean;
}

const AlignPicker = ({ disabled }: Props): JSX.Element => {
  const [elementFormat, setElementFormat] = useState<ElementFormatType>('left');

  const [editor] = useLexicalComposerContext();

  const dispatchAlignment = (alignment: ElementFormatType) => () => {
    editor.dispatchCommand(FORMAT_ELEMENT_COMMAND, alignment);
  };

  const updateElementFormat = useCallback(() => {
    const selection = $getSelection();

    if (!$isRangeSelection(selection)) {
      return;
    }

    const node = getSelectedNode(selection);
    const parent = node.getParent();

    setElementFormat(
      ($isElementNode(node) ? node.getFormatType() : parent?.getFormatType()) ||
        'left'
    );
  }, [editor]);

  const selectedFormat = formatOptions.find(({ value }) =>
    equals(value, elementFormat)
  );

  useEffect(() => {
    return editor.registerUpdateListener(({ editorState }) => {
      editorState.read(() => {
        updateElementFormat();
      });
    });
  }, [editor, updateElementFormat]);

  return (
    <Menu>
      <Menu.Button ariaLabel={elementFormat} disabled={disabled}>
        {selectedFormat && <selectedFormat.Icon />}
      </Menu.Button>
      <Menu.Items>
        <div
          style={{
            display: 'flex',
            flexDirection: 'row'
          }}
        >
          {formatOptions.map(({ Icon, value }) => (
            <Menu.Item
              isActive={equals(value, elementFormat)}
              key={value}
              onClick={dispatchAlignment(value)}
            >
              <Icon />
            </Menu.Item>
          ))}
        </div>
      </Menu.Items>
    </Menu>
  );
};

export default AlignPicker;
