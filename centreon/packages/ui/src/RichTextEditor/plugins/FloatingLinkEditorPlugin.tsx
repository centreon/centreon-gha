import { useCallback, useEffect, useState } from 'react';

import { $isLinkNode, TOGGLE_LINK_COMMAND } from '@lexical/link';
import { useLexicalComposerContext } from '@lexical/react/LexicalComposerContext';
import { mergeRegister } from '@lexical/utils';
import { useAtom, useAtomValue, useSetAtom } from 'jotai';
import {
  $getSelection,
  $isRangeSelection,
  $isTextNode,
  LexicalEditor
} from 'lexical';
import { dec, equals, gt, isNil, replace } from 'ramda';
import { useTranslation } from 'react-i18next';

import EditIcon from '@mui/icons-material/Edit';
import { Box, IconButton, Link, Paper, Popper } from '@mui/material';

import InputField from '../../InputField/Text';
import { editLinkModeAtom, isInsertingLinkAtom, linkValueAtom } from '../atoms';
import {
  labelEditLink,
  labelInputLink,
  labelSavedLink
} from '../translatedLabels';
import { getDOMRangeRect } from '../utils/getDOMRangeRect';
import { getSelectedNode } from '../utils/getSelectedNode';

interface FloatingLinkEditorPluginProps {
  editable: boolean;
  openLinkInNewTab: boolean;
}

interface UseFloatingLinkEditorProps extends FloatingLinkEditorPluginProps {
  editor: LexicalEditor;
}

interface FloatingLinkEditorProps {
  editor: LexicalEditor;
  openLinkInNewTab: boolean;
}

interface TooltipPosition {
  x: number;
  y: number;
}

const FloatingLinkEditor = ({
  editor,
  openLinkInNewTab
}: FloatingLinkEditorProps): JSX.Element | null => {
  const nativeSelection = window.getSelection();
  const rootElement = editor.getRootElement();
  const { t } = useTranslation();
  const [tooltipPosition, setTooltipPosition] = useState<TooltipPosition>({
    x: 0,
    y: 0
  });
  const [editedUrl, setEditedUrl] = useState('');

  const [editMode, setEditMode] = useAtom(editLinkModeAtom);
  const [linkUrl, setLinkUrl] = useAtom(linkValueAtom);

  const rangeRect = getDOMRangeRect(nativeSelection, rootElement);

  const acceptOrCancelNewLinkValue = useCallback(
    (event): void => {
      const { value } = event.target;

      event.preventDefault();

      if (event.key === 'Enter') {
        if (value !== '') {
          editor.dispatchCommand(TOGGLE_LINK_COMMAND, {
            target: openLinkInNewTab ? '_blank' : undefined,
            url: value
          });
        }
        setEditMode(false);
      }

      if (event.key === 'Escape') {
        setEditMode(false);
      }
    },
    [setEditMode, setLinkUrl]
  );

  const enterInEditMode = useCallback(() => {
    setEditedUrl(linkUrl);
    setEditMode(true);
  }, [linkUrl]);

  const changeValue = useCallback((event): void => {
    const { value } = event.target;

    const matched = value.match(/https?:\/\//g);

    if (gt(matched.length, 1)) {
      setEditedUrl(
        replace(matched.join(''), matched[dec(matched.length)], value)
      );

      return;
    }

    setEditedUrl(value);
  }, []);

  useEffect(() => {
    const isPositioned =
      rangeRect && (!equals(rangeRect.x, 0) || !equals(rangeRect.y, 0));

    if (!isPositioned || !nativeSelection) {
      return;
    }

    const nodeX = rangeRect.x;
    const nodeY = rangeRect.y;
    const nodeHeight = rangeRect.height;

    setTooltipPosition({ x: nodeX, y: nodeY + nodeHeight });
  }, [rangeRect?.x, rangeRect?.y]);

  if (isNil(rangeRect)) {
    return null;
  }

  const xOffset =
    tooltipPosition.x - (rootElement?.getBoundingClientRect()?.x || 0);

  const rootElementY = rootElement?.getBoundingClientRect()?.y || 0;
  const yOffset = tooltipPosition.y - rootElementY + 30;

  return (
    <Popper
      open
      anchorEl={rootElement}
      placement="top-start"
      sx={{ zIndex: 'tooltip' }}
    >
      <Paper
        sx={{
          transform: `translate3d(${xOffset}px, ${
            editMode ? yOffset + 10 : yOffset
          }px, 0px)`
        }}
      >
        {editMode ? (
          <InputField
            autoFocus
            dataTestId="InputLinkField"
            label={t(labelInputLink)}
            size="small"
            value={editedUrl}
            onBlur={(event): void => {
              const { value } = event.target;

              event.preventDefault();

              if (value !== '') {
                editor.dispatchCommand(TOGGLE_LINK_COMMAND, value);
              }
              setEditMode(false);
            }}
            onChange={changeValue}
            onKeyUp={acceptOrCancelNewLinkValue}
          />
        ) : (
          <Box component="span" sx={{ margin: '10px' }}>
            <Link
              aria-label={labelSavedLink}
              href={linkUrl}
              rel="noreferrer noopener"
              target={openLinkInNewTab ? '_blank' : undefined}
              variant="button"
            >
              {linkUrl}
            </Link>
            <IconButton
              aria-label={labelEditLink}
              size="small"
              sx={{ marginLeft: '5px' }}
              onClick={enterInEditMode}
            >
              <EditIcon fontSize="small" />
            </IconButton>
          </Box>
        )}
      </Paper>
    </Popper>
  );
};

const useFloatingTextFormatToolbar = ({
  editor,
  editable,
  openLinkInNewTab
}: UseFloatingLinkEditorProps): JSX.Element | null => {
  const [isText, setIsText] = useState(false);
  const isInsertingLink = useAtomValue(isInsertingLinkAtom);
  const editLinkMode = useAtomValue(editLinkModeAtom);
  const setLinkUrl = useSetAtom(linkValueAtom);

  const updatePopup = useCallback(() => {
    editor.getEditorState().read(() => {
      if (editor.isComposing()) {
        return;
      }
      const selection = $getSelection();
      const nativeSelection = window.getSelection();
      const rootElement = editor.getRootElement();

      if (
        nativeSelection !== null &&
        (!$isRangeSelection(selection) ||
          rootElement === null ||
          !rootElement.contains(nativeSelection.anchorNode)) &&
        !editLinkMode
      ) {
        setIsText(false);

        return;
      }

      if (!$isRangeSelection(selection) || editLinkMode) {
        return;
      }

      const node = getSelectedNode(selection);
      const parent = node.getParent();
      if ($isLinkNode(parent)) {
        setLinkUrl(parent.getURL());
      } else if ($isLinkNode(node)) {
        setLinkUrl(node.getURL());
      } else {
        setLinkUrl('');
      }

      if (selection.getTextContent() !== '') {
        setIsText($isTextNode(node) || $isLinkNode(node));
      } else {
        setIsText(false);
      }
    });
  }, [editor, editLinkMode]);

  useEffect(() => {
    document.addEventListener('selectionchange', updatePopup);

    return (): void => {
      document.removeEventListener('selectionchange', updatePopup);
    };
  }, [updatePopup]);

  useEffect(() => {
    return mergeRegister(
      editor.registerUpdateListener(() => {
        updatePopup();
      }),
      editor.registerRootListener(() => {
        if (editor.getRootElement() === null) {
          setIsText(false);
        }
      })
    );
  }, [editor, updatePopup]);

  if (!editable || !isText || !isInsertingLink) {
    return null;
  }

  return (
    <FloatingLinkEditor editor={editor} openLinkInNewTab={openLinkInNewTab} />
  );
};

const FloatingActionsToolbarPlugin = ({
  editable,
  openLinkInNewTab
}: FloatingLinkEditorPluginProps): JSX.Element | null => {
  const [editor] = useLexicalComposerContext();

  return useFloatingTextFormatToolbar({ editable, editor, openLinkInNewTab });
};

export default FloatingActionsToolbarPlugin;
