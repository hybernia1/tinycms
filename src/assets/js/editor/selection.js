(() => {
const app = window.tinycms = window.tinycms || {};
const editor = app.editor = app.editor || {};

const eventElement = (event) => {
    const target = event && event.target ? event.target : null;
    return target && target.nodeType === Node.TEXT_NODE ? target.parentElement : target;
};

const rememberSelection = () => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) {
        return null;
    }
    return selection.getRangeAt(0).cloneRange();
};

const restoreSelection = (range, editor) => {
    if (!range) {
        return;
    }
    editor.focus();
    const selection = window.getSelection();
    if (!selection) {
        return;
    }
    selection.removeAllRanges();
    selection.addRange(range);
};

const focusEditorEnd = (editor) => {
    editor.focus();
    const selection = window.getSelection();
    if (!selection) {
        return;
    }
    const range = document.createRange();
    range.selectNodeContents(editor);
    range.collapse(false);
    selection.removeAllRanges();
    selection.addRange(range);
};

const isSelectionInside = (editor) => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0) {
        return false;
    }
    return editor.contains(selection.anchorNode);
};

const getCurrentLink = (editor) => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || !editor.contains(selection.anchorNode)) {
        return null;
    }
    let source = selection.anchorNode;
    if (source.nodeType === Node.TEXT_NODE) {
        source = source.parentElement;
    }
    return source ? source.closest('a') : null;
};

const getSelectionContainer = (editor) => {
    const selection = window.getSelection();
    if (!selection || selection.rangeCount === 0 || !editor.contains(selection.anchorNode)) {
        return null;
    }
    const node = selection.anchorNode;
    return node && node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
};

const isSelectionInsideTag = (editor, tagName) => {
    const container = getSelectionContainer(editor);
    return !!(container && container.closest(tagName));
};

const isSelectionInsideHeading = (editor) => {
    const container = getSelectionContainer(editor);
    return !!(container && container.closest('h1, h2, h3, h4, h5, h6'));
};

const placeCaret = (paragraph) => {
    if (!paragraph) {
        return;
    }
    const selection = window.getSelection();
    if (!selection) {
        return;
    }
    const range = document.createRange();
    range.selectNodeContents(paragraph);
    range.collapse(true);
    selection.removeAllRanges();
    selection.addRange(range);
};

editor.selection = {
    eventElement,
    focusEditorEnd,
    getCurrentLink,
    getSelectionContainer,
    isSelectionInside,
    isSelectionInsideHeading,
    isSelectionInsideTag,
    placeCaret,
    rememberSelection,
    restoreSelection,
};
})();
