import { useCallback, useEffect, useState } from 'react';

import {
  $isListNode,
  INSERT_ORDERED_LIST_COMMAND,
  INSERT_UNORDERED_LIST_COMMAND,
  ListNode,
  REMOVE_LIST_COMMAND
} from '@lexical/list';
import { $getSelection, $isRootOrShadowRoot } from 'lexical';
import {
  $findMatchingParent,
  $getNearestNodeOfType,
  mergeRegister
} from '@lexical/utils';
import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { equals, isNil } from 'ramda';

import UnorderedListIcon from '@mui/icons-material/FormatListBulleted';
import OrderedListIcon from '@mui/icons-material/FormatListNumbered';

import { Menu } from '../../../components';

import { useStyles } from './ToolbarPlugin.styles';

const options = [
  {
    Icon: UnorderedListIcon,
    label: 'Unordered List',
    value: 'bullet'
  },
  {
    Icon: OrderedListIcon,
    label: 'Ordered List',
    value: 'number'
  }
];

interface Props {
  disabled?: boolean;
}

const ListButton = ({ disabled }: Props): JSX.Element => {
  const { classes } = useStyles();

  const [editor] = useLexicalComposerContext();

  const [elementList, setElementList] = useState('bullet');

  const formatList = (value): void => {
    if (value === 'bullet') {
      editor.dispatchCommand(INSERT_UNORDERED_LIST_COMMAND, undefined);

      return;
    }
    if (value === 'number') {
      editor.dispatchCommand(INSERT_ORDERED_LIST_COMMAND, undefined);

      return;
    }

    editor.dispatchCommand(REMOVE_LIST_COMMAND, undefined);
  };

  const updateToolbar = useCallback(() => {
    const selection = $getSelection();
    const anchorNode = selection?.anchor.getNode();
    const element = equals(anchorNode?.getKey(), 'root')
      ? anchorNode
      : $findMatchingParent(anchorNode, (e) => {
          const parent = e.getParent();

          return parent !== null && $isRootOrShadowRoot(parent);
        }) || anchorNode?.getTopLevelElementOrThrow();

    const elementKey = element?.getKey();
    const elementDOM = editor.getElementByKey(elementKey);

    if (isNil(elementDOM)) {
      return;
    }

    if ($isListNode(element)) {
      const parentList = $getNearestNodeOfType(anchorNode, ListNode);
      const type = parentList
        ? parentList.getListType()
        : element.getListType();
      setElementList(type);
    }
  }, [editor]);

  const selectedList = options.find(({ value }) => equals(value, elementList));

  useEffect(() => {
    return editor.registerUpdateListener(({ editorState }) => {
      editorState.read(() => {
        updateToolbar();
      });
    });
  }, [editor, updateToolbar]);

  useEffect(() => {
    return mergeRegister(
      editor.registerUpdateListener(({ editorState }) => {
        editorState.read(() => {
          updateToolbar();
        });
      })
    );
  }, [editor, updateToolbar]);

  return (
    <Menu>
      <Menu.Button ariaLabel={elementList} disabled={disabled}>
        {selectedList && <selectedList.Icon />}
      </Menu.Button>
      <Menu.Items>
        <div className={classes.menu}>
          {options.map(({ Icon, value }) => (
            <Menu.Item
              isActive={equals(value, elementList)}
              key={value}
              onClick={() => formatList(value)}
            >
              <Icon />
            </Menu.Item>
          ))}
        </div>
      </Menu.Items>
    </Menu>
  );
};

export default ListButton;
