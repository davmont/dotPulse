<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/ui.class.php';
require_once DP_BASE_DIR . '/includes/main_functions.php';

/**
 * dPlocaleReviewMarks(): the entries of a locale file still marked "// MT", which the
 * translation page keeps when it saves them unchanged (modules/system/translate_save.php).
 */
class LocaleReviewMarksTest extends TestCase {
    private function marks($source) {
        $file = tempnam(sys_get_temp_dir(), 'loc');
        file_put_contents($file, $source);
        $marks = dPlocaleReviewMarks($file);
        unlink($file);
        return $marks;
    }

    function testMarkedEntriesOnly() {
        $marks = $this->marks("\"Add note\"=>\"Ajouter une note\", // MT\n"
            . "\"Save\"=>\"Enregistrer\",\n"
            . "\"Added new\"=>\"Nouvel ajout\",   // MT  \n"
            . "\"Bare\",\n");
        $this->assertEquals(array('Add note' => 'Ajouter une note', 'Added new' => 'Nouvel ajout'), $marks);
    }

    function testQuotesAndEscapesAsTheFileMeansThem() {
        $marks = $this->marks("\"Say \\\"hi\\\"\"=>\"Dis \\\"salut\\\"\", // MT\n"
            . "'It''s'=>'x', // MT\n");
        $this->assertEquals('Dis "salut"', $marks['Say "hi"']);
        // What the translation page writes (var_export) reads back the same.
        $line = var_export("Don't \\ stop", true) . '=>' . var_export("N'arrête \\ pas", true) . ', // MT' . "\n";
        $this->assertEquals(array("Don't \\ stop" => "N'arrête \\ pas"), $this->marks($line));
    }

    function testNoCodeRuns() {
        $this->assertEquals(array(), $this->marks("system('id')=>phpinfo(), // MT\n"));
        $this->assertEquals(array(), dPlocaleReviewMarks('/nonexistent/file.inc'));
    }
}
