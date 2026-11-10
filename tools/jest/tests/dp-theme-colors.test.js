/**
 * @jest-environment jsdom
 */
const fs = require('fs');
const path = require('path');

const src = fs.readFileSync(path.resolve(__dirname, '../../../js/dp-theme-colors.js'), 'utf8');

// Expected values from includes/dark_colors.php dPdarkColor() (null: the colour stays).
// The script must map colours exactly as the page output filter does.
const PHP = [
        ["#ffffff", "bg", "#1A202B"],
        ["#ffffff", "fg", null],
        ["#ffffff", "border", "#2D3443"],
        ["#FFFFCC", "bg", "#434316"],
        ["#FFFFCC", "fg", null],
        ["#FFFFCC", "border", "#595926"],
        ["#FFCCCC", "bg", "#431616"],
        ["#FFCCCC", "fg", null],
        ["#FFCCCC", "border", "#592626"],
        ["#ECECEC", "bg", "#202734"],
        ["#ECECEC", "fg", null],
        ["#ECECEC", "border", "#313A4A"],
        ["#ECF4F9", "bg", "#142C3B"],
        ["#ECF4F9", "fg", null],
        ["#ECF4F9", "border", "#244154"],
        ["#ffebcd", "bg", "#433116"],
        ["#ffebcd", "fg", null],
        ["#ffebcd", "border", "#594526"],
        ["#999", "bg", "#39455D"],
        ["#999", "fg", null],
        ["#999", "border", "#455168"],
        ["#316AC5", "bg", null],
        ["#316AC5", "fg", "#6491D8"],
        ["#316AC5", "border", null],
        ["#000000", "bg", null],
        ["#000000", "fg", "#E7E9EE"],
        ["#000000", "border", "#576175"],
        ["#777777", "bg", null],
        ["#777777", "fg", "#A1AABD"],
        ["#777777", "border", null],
        ["#17BECF", "bg", null],
        ["#17BECF", "fg", null],
        ["#17BECF", "border", null],
        ["#CC6666", "bg", "#712626"],
        ["#CC6666", "fg", "#CF6E6E"],
        ["#CC6666", "border", "#793434"],
        ["#2CA02C", "bg", null],
        ["#2CA02C", "fg", null],
        ["#2CA02C", "border", null],
        ["lightyellow", "bg", "#3D3D14"],
        ["lightyellow", "fg", null],
        ["lightyellow", "border", "#555524"]
];

describe('dPthemeColor', () => {
    beforeAll(() => {
        window.eval(src);
    });

    afterEach(() => {
        document.documentElement.removeAttribute('data-theme');
    });

    test('leaves colours alone outside the dark theme', () => {
        expect(window.dPthemeIsDark()).toBe(false);
        expect(window.dPthemeColor('#FFFFCC', 'bg')).toBe('#FFFFCC');
    });

    test.each(PHP)('dark theme: %s as %s matches the PHP filter', (color, role, expected) => {
        document.documentElement.setAttribute('data-theme', 'dark');
        expect(window.dPthemeColor(color, role)).toBe(expected === null ? color : expected);
    });

    test('rgb() values, as scripts build them', () => {
        document.documentElement.setAttribute('data-theme', 'dark');
        expect(window.dPthemeColor('rgb(255, 255, 204)', 'bg')).toBe(window.dPthemeColor('#FFFFCC', 'bg'));
        expect(window.dPthemeColor('rgba(0, 0, 0, 0)', 'bg')).toBe('rgba(0, 0, 0, 0)');
        expect(window.dPthemeColor('', 'bg')).toBe('');
    });
});
