(function () {
    function rememberSelection() {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return null;
        }
        return selection.getRangeAt(0).cloneRange();
    }

    function restoreSelection(range, editor) {
        if (!range) {
            return;
        }
        editor.focus();
        var selection = window.getSelection();
        if (!selection) {
            return;
        }
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function focusEditorEnd(editor) {
        editor.focus();
        var selection = window.getSelection();
        if (!selection) {
            return;
        }
        var range = document.createRange();
        range.selectNodeContents(editor);
        range.collapse(false);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    function isSelectionInside(editor) {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0) {
            return false;
        }
        return editor.contains(selection.anchorNode);
    }

    function getCurrentLink(editor) {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || !editor.contains(selection.anchorNode)) {
            return null;
        }
        var source = selection.anchorNode;
        if (source.nodeType === Node.TEXT_NODE) {
            source = source.parentElement;
        }
        return source ? source.closest('a') : null;
    }

    function getSelectionContainer(editor) {
        var selection = window.getSelection();
        if (!selection || selection.rangeCount === 0 || !editor.contains(selection.anchorNode)) {
            return null;
        }
        var node = selection.anchorNode;
        return node && node.nodeType === Node.TEXT_NODE ? node.parentElement : node;
    }

    function isSelectionInsideTag(editor, tagName) {
        var container = getSelectionContainer(editor);
        return !!(container && container.closest(tagName));
    }

    function isSelectionInsideHeading(editor) {
        var container = getSelectionContainer(editor);
        return !!(container && container.closest('h1, h2, h3, h4, h5, h6'));
    }

    function placeCaret(paragraph) {
        if (!paragraph) {
            return;
        }
        var selection = window.getSelection();
        if (!selection) {
            return;
        }
        var range = document.createRange();
        range.selectNodeContents(paragraph);
        range.collapse(true);
        selection.removeAllRanges();
        selection.addRange(range);
    }

    window.tinycmsEditorSelection = {
        rememberSelection: rememberSelection,
        restoreSelection: restoreSelection,
        focusEditorEnd: focusEditorEnd,
        isSelectionInside: isSelectionInside,
        getCurrentLink: getCurrentLink,
        getSelectionContainer: getSelectionContainer,
        isSelectionInsideTag: isSelectionInsideTag,
        isSelectionInsideHeading: isSelectionInsideHeading,
        placeCaret: placeCaret,
    };
})();
