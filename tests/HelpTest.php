<?php
if (!defined('DP_BASE_DIR')) {
    die('DP_BASE_DIR not defined');
}
require_once DP_BASE_DIR . '/classes/help.class.php';

require_once DP_BASE_DIR . '/includes/main_functions.php';

/**
 * classes/help.class.php: the Help module shows website/docs inside dotPulse.
 */
class HelpTest extends TestCase {
    function testPagesFollowTheSiteNavigation() {
        $pages = CHelp::pages();
        $names = array_keys($pages);
        $this->assertEquals('index', $names[0]);
        $this->assert(isset($pages['user-guide/gantt-board']));
        $this->assertEquals('Gantt chart and board', $pages['user-guide/gantt-board']['title']);
        $this->assertEquals('User guide', $pages['user-guide/gantt-board']['section']);
        $this->assertEquals('Getting started', $pages['getting-started/index']['title']);
        $this->assertEquals('', $pages['index']['section']);
        // Every page in the navigation exists and renders.
        foreach ($names as $name) {
            $html = CHelp::render($name);
            $this->assert(is_string($html) && strpos($html, '<h1') !== false, $name);
            $this->assert(strpos($html, 'DPHELP') === false, "placeholder left in $name");
        }
    }

    function testOnlyPagesOfTheNavigationAreServed() {
        $this->assertEquals('index', CHelp::page(''));
        $this->assertEquals('user-guide/index', CHelp::page('user-guide'));
        $this->assertEquals('user-guide/tasks', CHelp::page('user-guide/tasks/'));
        $this->assertEquals('', CHelp::page('../includes/config'));
        $this->assertEquals('', CHelp::page('user-guide/../../README'));
        $this->assertEquals('', CHelp::page('nothing'));
    }

    function testMkDocsMarkup() {
        $html = CHelp::render('getting-started/upgrade');
        // Admonition with its title; heading ids as MkDocs makes them.
        $this->assertRegexp('#<div class="dp-help-note dp-help-tip"><p class="dp-help-note-title">Rehearse on a copy</p>#', $html);
        $this->assertRegexp('#<h2 id="1-back-up">#', $html);
        // A * in code inside bold text.
        $this->assertRegexp('#<strong><code>locales/</code> and <code>modules/\*/locales/</code></strong>#', $html);
        $html = CHelp::render('getting-started/first-steps');
        $this->assertRegexp('#<img loading="lazy" src="\./website/docs/img/gs-[\w-]+\.png"[^>]* class="dp-help-shot">#', $html);
        $this->assert(strpos($html, '{ .shot }') === false);
        $html = CHelp::render('user-guide/search');
        $this->assert(strpos($html, '<kbd>Enter</kbd>') !== false);
    }

    function testLinksStayInHelp() {
        $html = CHelp::render('getting-started/index');
        $this->assert(strpos($html, 'href="?m=help&amp;page=getting-started/upgrade"') !== false);
        $html = CHelp::render('user-guide/interface');
        $this->assert(strpos($html, 'href="?m=help&amp;page=user-guide/dashboard#project-tips"') !== false);
        $this->assert(strpos($html, '.md"') === false || strpos($html, 'github.com') !== false);
        $this->assertEquals('getting-started/install', CHelp::resolve('user-guide', '../getting-started/install'));
        $this->assertEquals('img/a.png', CHelp::resolve('user-guide', '../../../img/a.png'));
    }

    function testHelpLinksOfTheApplication() {
        $GLOBALS['dPconfig'] = array();
        $this->assertEquals('./index.php?m=help&page=user-guide/gantt-board#the-projects-chart', dPhelpUrl('projects.viewgantt'));
        $this->assertEquals('./index.php?m=help&page=user-guide/projects', dPhelpUrl('projects.view'));
        $this->assertEquals('./index.php?m=help&page=user-guide', dPhelpUrl('nosuchmodule.index'));
        $this->assertEquals('https://www.dotpulse.org/user-guide/tasks/', dPhelpOnlineUrl('user-guide/tasks'));
        $this->assertEquals('https://www.dotpulse.org/user-guide/', dPhelpOnlineUrl('user-guide/index'));
        $this->assertEquals('https://www.dotpulse.org/', dPhelpOnlineUrl('index'));
    }

    function testSpanishUsersGetTheSpanishPages() {
        global $AppUI;
        $saved = $AppUI;
        $AppUI = new stdClass();
        $AppUI->user_locale = 'es';
        $AppUI->user_id = 1;
        $GLOBALS['dPconfig'] = array();
        $this->assertEquals('es', CHelp::lang());
        $pages = CHelp::pages();
        $this->assertEquals('Tareas', $pages['user-guide/tasks']['title']);
        $this->assertEquals('Guía de uso', $pages['user-guide/tasks']['section']);
        // Every page of the Spanish navigation renders.
        foreach (array_keys($pages) as $name) {
            $html = CHelp::render($name);
            $this->assert(is_string($html) && strpos($html, '<h1') !== false, $name);
            // (A page may show the syntax itself in code: environment.md does.)
            $this->assert(!preg_match('/\{\s*#/', preg_replace('#<code>.*?</code>#s', '', $html)), "heading id left as text in $name");
        }
        // The Spanish pages keep the English anchors, and their own screenshots.
        $html = CHelp::render('administration/settings');
        $this->assert(strpos($html, 'id="notifications-and-email"') !== false);
        $html = CHelp::render('getting-started/first-steps');
        $this->assertRegexp('#src="\./website/docs-es/img/gs-[\w-]+\.png"#', $html);
        $this->assertEquals('https://www.dotpulse.org/es/user-guide/tasks/', dPhelpOnlineUrl('user-guide/tasks'));
        $AppUI->user_locale = 'en';
        $this->assertEquals('en', CHelp::lang());
        $this->assertEquals('https://www.dotpulse.org/user-guide/tasks/', dPhelpOnlineUrl('user-guide/tasks'));
        $AppUI = $saved;
    }

    function testAdministrationPagesHaveTheirOwnHelp() {
        global $m, $a, $u, $AppUI;
        $saved = array($m, $a, $u, $AppUI, $_GET);
        $AppUI = new stdClass();
        $AppUI->user_id = 1;
        $cases = array(
            // m, a, u, $_GET => Help link
            array('system', 'index', 'roles', array(), 'administration/roles-permissions'),
            array('system', 'index', 'syskeys', array(), 'administration/lists-and-fields#lookup-values'),
            array('system', 'systemconfig', '', array('section' => 'mail'), 'administration/settings#notifications-and-email'),
            array('system', 'systemconfig', '', array('section' => 'login'), 'administration/settings#login-ldap'),
            array('system', 'systemconfig', '', array('section' => 'x<y'), 'administration/settings'),
            array('system', 'addeditpref', '', array(), 'administration/lists-and-fields#default-user-preferences'),
            array('system', 'addeditpref', '', array('user_id' => '2'), 'user-guide/preferences#edit-your-preferences'),
            array('admin', 'viewuser', '', array('user_id' => '1'), 'user-guide/preferences'),
            array('admin', 'viewuser', '', array('user_id' => '2'), 'administration/users'),
            array('admin', 'index', '', array(), 'administration/users'),
            array('holiday', 'index', '', array(), 'administration/holidays'),
            array('system', 'index', '', array(), 'administration'),
        );
        foreach ($cases as $c) {
            list($m, $a, $u, $_GET) = $c;
            $this->assertEquals('./index.php?m=help&page=' . $c[4], dPhelpUrl("$m.$a"), "$m.$a $u");
            $this->assertEquals('./index.php?m=help&page=' . $c[4], dPhelpUrl(''), "header link on $m.$a $u");
            list($page) = explode('#', $c[4]);
            $this->assert(CHelp::page($page) !== '', "page $page");
        }
        // A link to another page keeps its own reference.
        $this->assertEquals('./index.php?m=help&page=user-guide/projects', dPhelpUrl('projects.view'));
        list($m, $a, $u, $AppUI, $_GET) = $saved;
    }
}
