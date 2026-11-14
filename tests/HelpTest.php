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
}
