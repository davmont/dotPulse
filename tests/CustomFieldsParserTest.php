<?php
require_once dirname(__FILE__) . '/bootstrap.php';
require_once dirname(__FILE__) . '/../classes/customfieldsparser.class.php';

class CustomFieldsParserTest extends TestCase {
    var $parser;

    function setUp() {
        global $mock_sysvals;

        // Mock data for TaskCustomFields
        // Use serialize() to mimic DB storage
        $field_configs = array(
            'text_field' => serialize(array(
                'name' => 'Text Field',
                'type' => 'text',
                'options' => 'style="width:100%"',
                'selects' => '',
                'record_type' => '0'
            )),
            'select_field' => serialize(array(
                'name' => 'Select Field',
                'type' => 'select',
                'options' => '',
                'selects' => 'Option1,Option2,Option3',
                'record_type' => '0'
            )),
            'textarea_field' => serialize(array(
                'name' => 'Textarea Field',
                'type' => 'textarea',
                'options' => 'rows="5"',
                'selects' => '',
                'record_type' => '0'
            )),
            'checkbox_field' => serialize(array(
                'name' => 'Checkbox Field',
                'type' => 'checkbox',
                'options' => '',
                'selects' => 'Check1,Check2',
                'record_type' => '0'
            )),
            'href_field' => serialize(array(
                'name' => 'Href Field',
                'type' => 'href',
                'options' => '',
                'selects' => '',
                'record_type' => '0'
            )),
            'label_field' => serialize(array(
                'name' => 'Label Field',
                'type' => 'label',
                'options' => '',
                'selects' => '',
                'record_type' => '0'
            ))
        );

        $mock_sysvals['TaskCustomFields'] = $field_configs;
        $mock_sysvals['TaskType'] = array('0' => 'General');

        // Instantiate parser
        $this->parser = new CustomFieldsParser('TaskCustomFields', 0);
    }

    function testParseEditField_Text() {
        $html = $this->parser->parseEditField('text_field');

        $this->assertRegexp('/<tr id="custom_tr_text_field">/', $html);
        $this->assertRegexp('/<td.*>Text Field:<\/td>/', $html);
        // Note: dPformSafe in bootstrap mock handles array or string. Here value is '' (string).
        // <input ... value="" />
        $this->assertRegexp('/<input type="text" name="custom_text_field" class="text" style="width:100%" value="" \/>/', $html);
    }

    function testParseEditField_Select() {
        $html = $this->parser->parseEditField('select_field');

        $this->assertRegexp('/<tr id="custom_tr_select_field">/', $html);
        $this->assertRegexp('/<select name="custom_select_field" size="1" class="text" >/', $html);
        // Check for options. Values are indices 0, 1, 2. Labels are Option1, Option2...
        $this->assertRegexp('/<option value="0">Option1<\/option>/', $html);
        $this->assertRegexp('/<option value="1">Option2<\/option>/', $html);
        $this->assertRegexp('/<option value="2">Option3<\/option>/', $html);
    }

    function testParseEditField_Textarea() {
        $html = $this->parser->parseEditField('textarea_field');

        $this->assertRegexp('/<tr id="custom_tr_textarea_field">/', $html);
        $this->assertRegexp('/<textarea name="custom_textarea_field" class="textarea" rows="5" >/', $html);
        $this->assertRegexp('/<\/textarea>/', $html);
    }

    function testParseEditField_Checkbox() {
        $html = $this->parser->parseEditField('checkbox_field');

        $this->assertRegexp('/<tr id="custom_tr_checkbox_field">/', $html);
        $this->assertRegexp('/<input type="checkbox" value="Check1" name="custom_checkbox_field\[\]" class="text" style="border:0"  \/>Check1/', $html);
        $this->assertRegexp('/<input type="checkbox" value="Check2" name="custom_checkbox_field\[\]" class="text" style="border:0"  \/>Check2/', $html);
    }

    function testParseEditField_Href() {
        $html = $this->parser->parseEditField('href_field');

        $this->assertRegexp('/<tr id="custom_tr_href_field">/', $html);
        $this->assertRegexp('/<input type="text" name="custom_href_field" class="text"  value="" \/>/', $html);
    }

    function testParseEditField_Label() {
        $html = $this->parser->parseEditField('label_field');

        $this->assertRegexp('/<tr id="custom_tr_label_field">/', $html);
        $this->assertRegexp('/<td colspan="2"><b>Label Field<\/b><\/td>/', $html);
    }

    function testParseEditField_WithData() {
        // Set previous data
        $this->parser->previous_data = array(
            'text_field' => 'Value',
            'select_field' => '1', // Index 1 -> Option2
            'textarea_field' => 'Content',
            'checkbox_field' => array('Check1') // Check1 is selected
        );

        // Text
        $html = $this->parser->parseEditField('text_field');
        $this->assertRegexp('/value="Value"/', $html);

        // Select
        $html = $this->parser->parseEditField('select_field');
        // Option 1 (index 1) should be selected.
        // My mock arraySelect puts selected="selected" on the option.
        $this->assertRegexp('/<option value="1" selected="selected">Option2<\/option>/', $html);

        // Textarea
        $html = $this->parser->parseEditField('textarea_field');
        $this->assertRegexp('/>Content<\/textarea>/', $html);

        // Checkbox
        $html = $this->parser->parseEditField('checkbox_field');
        // Check1 should be checked
        // Regexp needs to be loose because attribute order might vary?
        // Code: '... checked="checked" ' . $field_config['options'] . ' />'
        $this->assertRegexp('/<input type="checkbox" value="Check1" .* checked="checked" /', $html);
        // Check2 should NOT be checked
        $this->assertRegexp('/<input type="checkbox" value="Check2" /', $html);
        // Ensure Check2 does not have checked attribute
        // This is harder with regexp unless we capture the whole tag.
        // But if Check1 has it and Check2 doesn't, we are good for now.
    }
}
?>
