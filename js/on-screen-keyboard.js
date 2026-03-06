/**
 * DS-Tracks - On-Screen Keyboard
 * Compact touch keyboard for Pi kiosk (no physical keyboard).
 * Auto-shows when text inputs are focused, docked to bottom of screen.
 */

(function() {
'use strict';

var activeInput = null;
var keyboardEl = null;
var shifted = false;
var capsLock = false;

var ROWS_LOWER = [
    ['q','w','e','r','t','y','u','i','o','p'],
    ['a','s','d','f','g','h','j','k','l'],
    ['{shift}','z','x','c','v','b','n','m','{backspace}'],
    ['{numbers}',',','{space}','.','{done}']
];

var ROWS_UPPER = [
    ['Q','W','E','R','T','Y','U','I','O','P'],
    ['A','S','D','F','G','H','J','K','L'],
    ['{shift}','Z','X','C','V','B','N','M','{backspace}'],
    ['{numbers}',',','{space}','.','{done}']
];

var ROWS_NUMBERS = [
    ['1','2','3','4','5','6','7','8','9','0'],
    ['-','/',':',';','(',')','$','&','@','"'],
    ['{symbols}','.',',','?','!','#','+','=','{backspace}'],
    ['{abc}',',','{space}','.','{done}']
];

var ROWS_SYMBOLS = [
    ['[',']','{','}','#','%','^','*','+','='],
    ['_','\\','|','~','<','>','!','?','@','"'],
    ['{numbers}','.',',',"'",'-','/',';',':','{backspace}'],
    ['{abc}',',','{space}','.','{done}']
];

var currentLayout = 'lower';

function getRows() {
    switch (currentLayout) {
        case 'lower': return ROWS_LOWER;
        case 'upper': return ROWS_UPPER;
        case 'numbers': return ROWS_NUMBERS;
        case 'symbols': return ROWS_SYMBOLS;
        default: return ROWS_LOWER;
    }
}

function buildKeyboard() {
    var el = document.createElement('div');
    el.id = 'ds-keyboard';
    el.className = 'ds-keyboard';
    document.body.appendChild(el);
    keyboardEl = el;

    el.addEventListener('mousedown', function(e) { e.preventDefault(); });
    el.addEventListener('touchstart', function(e) { e.preventDefault(); });

    renderKeys();
}

function renderKeys() {
    if (!keyboardEl) return;
    keyboardEl.innerHTML = '';
    var rows = getRows();
    for (var r = 0; r < rows.length; r++) {
        var rowEl = document.createElement('div');
        rowEl.className = 'ds-kb-row';
        var keys = rows[r];
        for (var k = 0; k < keys.length; k++) {
            var key = keys[k];
            var btn = document.createElement('button');
            btn.className = 'ds-kb-key';
            btn.setAttribute('type', 'button');

            var match = key.match(/^\{(.+)\}$/);
            if (match) {
                var special = match[1];
                btn.classList.add('ds-kb-special');
                btn.dataset.action = special;
                if (special === 'space') {
                    btn.textContent = ' ';
                    btn.classList.add('ds-kb-space');
                } else if (special === 'backspace') {
                    btn.innerHTML = '&#9003;';
                    btn.classList.add('ds-kb-backspace');
                } else if (special === 'shift') {
                    btn.innerHTML = '&#8679;';
                    btn.classList.add('ds-kb-shift');
                    if (shifted || capsLock) {
                        btn.classList.add('ds-kb-active');
                    }
                } else if (special === 'done') {
                    btn.textContent = 'Done';
                    btn.classList.add('ds-kb-done');
                } else if (special === 'numbers') {
                    btn.textContent = '123';
                    btn.classList.add('ds-kb-mode');
                } else if (special === 'symbols') {
                    btn.textContent = '#+=';
                    btn.classList.add('ds-kb-mode');
                } else if (special === 'abc') {
                    btn.textContent = 'ABC';
                    btn.classList.add('ds-kb-mode');
                }
            } else {
                btn.textContent = key;
                btn.dataset.key = key;
            }

            btn.addEventListener('click', onKeyPress);
            rowEl.appendChild(btn);
        }
        keyboardEl.appendChild(rowEl);
    }
}

function onKeyPress(e) {
    if (!activeInput) return;
    var btn = e.currentTarget;
    var action = btn.dataset.action;
    var key = btn.dataset.key;

    if (action) {
        if (action === 'backspace') {
            var start = activeInput.selectionStart;
            var end = activeInput.selectionEnd;
            var val = activeInput.value;
            if (start !== end) {
                activeInput.value = val.slice(0, start) + val.slice(end);
                activeInput.selectionStart = activeInput.selectionEnd = start;
            } else if (start > 0) {
                activeInput.value = val.slice(0, start - 1) + val.slice(start);
                activeInput.selectionStart = activeInput.selectionEnd = start - 1;
            }
        } else if (action === 'shift') {
            if (capsLock) {
                capsLock = false;
                shifted = false;
                currentLayout = 'lower';
            } else if (shifted) {
                capsLock = true;
            } else {
                shifted = true;
                currentLayout = 'upper';
            }
            renderKeys();
        } else if (action === 'numbers') {
            currentLayout = 'numbers';
            renderKeys();
        } else if (action === 'symbols') {
            currentLayout = 'symbols';
            renderKeys();
        } else if (action === 'abc') {
            currentLayout = shifted || capsLock ? 'upper' : 'lower';
            renderKeys();
        } else if (action === 'done') {
            hideKeyboard();
        }
    } else if (key) {
        var start = activeInput.selectionStart;
        var end = activeInput.selectionEnd;
        var val = activeInput.value;
        activeInput.value = val.slice(0, start) + key + val.slice(end);
        activeInput.selectionStart = activeInput.selectionEnd = start + 1;

        if (shifted && !capsLock) {
            shifted = false;
            currentLayout = 'lower';
            renderKeys();
        }
    }

    activeInput.dispatchEvent(new Event('input', { bubbles: true }));
    activeInput.focus();
}

function showKeyboard(inputEl) {
    activeInput = inputEl;
    if (!keyboardEl) buildKeyboard();
    keyboardEl.classList.add('ds-kb-visible');
    document.body.classList.add('ds-keyboard-open');

    shifted = false;
    capsLock = false;
    currentLayout = 'lower';
    renderKeys();

    setTimeout(function() {
        inputEl.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }, 100);
}

function hideKeyboard() {
    if (keyboardEl) {
        keyboardEl.classList.remove('ds-kb-visible');
    }
    document.body.classList.remove('ds-keyboard-open');
    if (activeInput) {
        activeInput.blur();
        activeInput = null;
    }
}

function init() {
    document.addEventListener('focusin', function(e) {
        var el = e.target;
        if (el.tagName === 'INPUT' && (el.type === 'text' || el.type === 'search' || el.type === 'email' || el.type === 'url' || !el.type)) {
            showKeyboard(el);
        }
    });

    document.addEventListener('click', function(e) {
        if (!keyboardEl || !keyboardEl.classList.contains('ds-kb-visible')) return;
        if (keyboardEl.contains(e.target)) return;
        if (e.target.tagName === 'INPUT') return;
        hideKeyboard();
    });
}

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
} else {
    init();
}

window.dsKeyboard = {
    show: showKeyboard,
    hide: hideKeyboard
};

})();
