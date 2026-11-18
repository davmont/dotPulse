<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/ui.class.php';
require_once DP_BASE_DIR . '/includes/main_functions.php';

/**
 * dPlocaleEntries(): a locale file read with the tokenizer, with its "// MT" marks, which the
 * translation page keeps when it saves an entry unchanged (modules/system/translate_save.php).
 */
class LocaleEntriesTest extends TestCase {
    private function entries($source) {
        $file = tempnam(sys_get_temp_dir(), 'loc');
        file_put_contents($file, $source);
        $entries = dPlocaleEntries($file);
        unlink($file);
        return $entries;
    }

    private function marked($source) {
        $marked = array();
        foreach ($this->entries($source) as $key => $e) {
            if ($e['mt']) {
                $marked[$key] = $e['text'];
            }
        }
        return $marked;
    }

    function testMarks() {
        $source = "##\n## DO NOT MODIFY THIS FILE BY HAND!\n##\n"
            . "\"Add note\"=>\"Ajouter une note\", // MT\n"
            . "\"Save\"=>\"Enregistrer\",\n"
            . "\"Added new\"=>\"Nouvel ajout\",   // MT  \n"
            . "\"Next\"=>\"Suivant\",\n// MT\n";
        $this->assertEquals(array('Add note' => 'Ajouter une note', 'Added new' => 'Nouvel ajout'), $this->marked($source));
        $this->assertEquals(4, count($this->entries($source)));
    }

    function testEnglishEntries() {
        $this->assertEquals(array('Bare' => array('text' => 'Bare', 'mt' => false),
            'abbr' => array('text' => 'Abbreviated', 'mt' => false)),
            $this->entries("\"Bare\",\n'abbr'=>'Abbreviated',\n"));
    }

    function testQuotesAndEscapesAsPhpReadsThem() {
        $marks = $this->marked("\"Say \\\"hi\\\"\"=>\"Dis \\\"salut\\\"\", // MT\n"
            . "'It\\'s'=>'C\\'est', // MT\n");
        $this->assertEquals('Dis "salut"', $marks['Say "hi"']);
        $this->assertEquals("C'est", $marks["It's"]);
        foreach (array('"a\\nb\\t\\\\c\\$d"', '"\\q \\x41\\101 \\u{e9}"', "'a\\\\b\\nc\\'d'", '"\\400"') as $literal) {
            $this->assertEquals(eval("return $literal;"), dPlocaleLiteral($literal), $literal);
        }
    }

    function testWhatTheTranslationPageWritesReadsBack() {
        // var_export() writes a line break as is: an entry over two lines.
        $source = var_export("Don't \\ stop", true) . '=>' . var_export("N'arrête \\ pas", true) . ", // MT\n"
            . var_export("One\nTwo", true) . '=>' . var_export("Un\nDeux", true) . ", // MT\n";
        $this->assertEquals(array("Don't \\ stop" => "N'arrête \\ pas", "One\nTwo" => "Un\nDeux"), $this->marked($source));
    }

    function testNoCodeRuns() {
        $this->assertEquals(array(), $this->entries("system('id')=>phpinfo(), // MT\n\"x\$y\"=>'z', // MT\n"));
        $this->assertEquals(array(), dPlocaleEntries('/nonexistent/file.inc'));
    }

    // Every locale file of the repository reads as locales/core.php and translate.php read it.
    function testLocaleFilesReadAsPhpReadsThem() {
        $files = array_merge(glob(DP_BASE_DIR . '/locales/*/*.inc'), glob(DP_BASE_DIR . '/modules/*/locales/*.inc'));
        $this->assert(count($files) > 100, 'locale files found');
        foreach ($files as $file) {
            $source = file_get_contents($file);
            if (!dPisTranslationSource($source)) {
                continue;
            }
            $php = eval('return array(' . $source . "\n);");
            $read = array();
            foreach (dPlocaleEntries($file) as $key => $e) {
                $read[$key] = $e['text'];
            }
            $expected = array();
            foreach ($php as $k => $v) {
                $expected[is_int($k) ? $v : $k] = $v;
            }
            $this->assertEquals($expected, $read, $file);
        }
    }
}
