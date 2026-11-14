<?php
/**
 * The Help module: the documentation in website/docs (the source of www.dotpulse.org),
 * shown inside dotPulse. It matches the version installed and needs no Internet access.
 *
 * Pages are those listed in website/mkdocs.yml (nav). The Markdown is rendered with
 * Parsedown (vendor/), plus what the site uses from MkDocs Material: admonitions
 * (!!! note "Title"), ++key++, { .shot } after images, headings with ids as MkDocs makes
 * them, and links to other pages turned into Help links.
 */
if (!defined('DP_BASE_DIR')) {
	die('You should not access this file directly.');
}

class CHelp
{
	const DOCS = '/website/docs';

	/**
	 * The pages in the order of the site's navigation:
	 * [page => ['title' => ..., 'section' => ...]], page as "user-guide/tasks" (no .md);
	 * a section's index page ("user-guide/index") has the section's name as title.
	 */
	static function pages()
	{
		static $pages = null;
		if ($pages !== null) {
			return $pages;
		}
		$pages = array();
		$yml = @file_get_contents(DP_BASE_DIR . '/website/mkdocs.yml');
		if ($yml === false || !preg_match('/^nav:\s*\n((?:[ \t]+.*\n?)+)/m', $yml, $nav)) {
			return $pages;
		}
		$section = '';
		foreach (explode("\n", $nav[1]) as $line) {
			if (!preg_match('/^(\s*)- (?:([^:]+?):\s*)?(\S+\.md)?\s*$/', $line, $m)) {
				continue;
			}
			$title = isset($m[2]) ? trim($m[2]) : '';
			$file = isset($m[3]) ? $m[3] : '';
			if ($file === '') { // "- Getting started:" opens a section
				$section = $title;
				continue;
			}
			if (strlen($m[1]) <= 2) { // a top-level page ("- Home: index.md")
				$section = '';
			}
			$page = substr($file, 0, -3);
			$pages[$page] = array('title' => $title !== '' ? $title : $section, 'section' => $section);
		}
		return $pages;
	}

	/** A page name from a request, or '' when it is not one of the pages. */
	static function page($name)
	{
		$name = trim((string)$name, '/');
		if ($name === '') {
			return 'index';
		}
		$pages = self::pages();
		if (isset($pages[$name])) {
			return $name;
		}
		if (isset($pages[$name . '/index'])) {
			return $name . '/index';
		}
		return '';
	}

	/** The Help link of a page: "?m=help&page=user-guide/tasks#anchor". */
	static function url($page, $anchor = '')
	{
		$page = preg_replace('#/index$#', '', $page);
		$url = '?m=help' . ($page === 'index' || $page === '' ? '' : '&page=' . $page);
		return $url . ($anchor !== '' ? '#' . $anchor : '');
	}

	/**
	 * The page as HTML (the trusted Markdown of the repository, rendered in Parsedown's
	 * safe mode all the same), or null when it cannot be read.
	 */
	static function render($page)
	{
		$text = @file_get_contents(DP_BASE_DIR . self::DOCS . '/' . $page . '.md');
		if ($text === false) {
			return null;
		}
		require_once DP_BASE_DIR . '/vendor/autoload.php';
		$parsedown = new Parsedown();
		$parsedown->setSafeMode(true);
		$blocks = array();
		$text = str_replace("\r\n", "\n", $text);

		// <div class="grid cards" markdown> ... </div>: the content, without the box.
		$text = preg_replace('#^<div[^>\n]*\bmarkdown\b[^>\n]*>\s*$|^</div>\s*$#m', '', $text);

		// Parsedown pairs a * in code (**`modules/*/locales/`**) with the bold around it.
		$text = preg_replace_callback('/`[^`\n]+`/', function ($m) {
			return str_replace('*', 'DPHELPSTAR', $m[0]);
		}, $text);

		// Admonitions: rendered on their own, put back after the page is rendered.
		$text = preg_replace_callback(
			'/^!!! *(\w+)(?: +"([^"\n]*)")?\n((?:(?: {4}|\t).*\n|[ \t]*\n)+)/m',
			function ($m) use ($parsedown, &$blocks) {
				$body = preg_replace('/^(?: {4}|\t)/m', '', $m[3]);
				$title = $m[2] !== '' ? $m[2] : ucfirst($m[1]);
				$key = 'DPHELPBLOCK' . count($blocks) . 'X';
				$blocks[$key] = '<div class="dp-help-note dp-help-' . htmlspecialchars(strtolower($m[1])) . '">'
					. '<p class="dp-help-note-title">' . htmlspecialchars($title) . '</p>'
					. self::keys($parsedown->text($body)) . '</div>';
				return "\n" . $key . "\n\n";
			},
			$text
		);

		$html = self::keys($parsedown->text($text));
		foreach ($blocks as $key => $block) {
			$html = str_replace('<p>' . $key . '</p>', $block, $html);
		}
		$html = str_replace('DPHELPSTAR', '*', $html);

		// { .shot } after an image: the screenshot frame.
		$html = preg_replace('#(<img [^>]*?)\s*/?>\s*\{\s*\.shot\s*\}#', '$1 class="dp-help-shot">', $html);
		$html = preg_replace('#\{\s*\.[\w-]+(?:\s+\.[\w-]+)*\s*\}#', '', $html);

		// Heading ids, as the MkDocs table of contents makes them, for links with #anchors.
		$seen = array();
		$html = preg_replace_callback('#<h([1-6])>(.*?)</h\1>#s', function ($m) use (&$seen) {
			$id = self::slug($m[2]);
			if (isset($seen[$id])) {
				$id .= '_' . $seen[$id]++;
			} else {
				$seen[$id] = 1;
			}
			return '<h' . $m[1] . ' id="' . $id . '">' . $m[2] . '</h' . $m[1] . '>';
		}, $html);

		// Links and images relative to the page.
		$dir = dirname($page) === '.' ? '' : dirname($page);
		$html = preg_replace_callback('#<a href="([^"]*)"#', function ($m) use ($dir) {
			$href = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
			if (preg_match('#^[a-z][a-z0-9+.-]*:#i', $href)) {
				return '<a href="' . htmlspecialchars($href) . '" target="_blank" rel="noopener"';
			}
			if ($href !== '' && $href[0] === '#') {
				return $m[0];
			}
			$parts = explode('#', $href, 2);
			$target = self::resolve($dir, preg_replace('/\.md$/', '', $parts[0]));
			return '<a href="' . htmlspecialchars(self::url($target, isset($parts[1]) ? $parts[1] : '')) . '"';
		}, $html);
		$html = preg_replace_callback('#<img src="([^"]*)"#', function ($m) use ($dir) {
			$src = html_entity_decode($m[1], ENT_QUOTES, 'UTF-8');
			if (!preg_match('#^[a-z][a-z0-9+.-]*:#i', $src)) {
				$src = '.' . self::DOCS . '/' . self::resolve($dir, $src);
			}
			return '<img loading="lazy" src="' . htmlspecialchars($src) . '"';
		}, $html);
		return $html;
	}

	/** "a/b" + "../c/d" = "a/c/d" (never above website/docs). */
	static function resolve($dir, $path)
	{
		$out = $dir === '' ? array() : explode('/', $dir);
		foreach (explode('/', $path) as $part) {
			if ($part === '..') {
				array_pop($out);
			} elseif ($part !== '.' && $part !== '') {
				$out[] = $part;
			}
		}
		return implode('/', $out);
	}

	/** The id MkDocs gives a heading (Python-Markdown's toc slugify). */
	static function slug($html)
	{
		$text = html_entity_decode(strip_tags($html), ENT_QUOTES, 'UTF-8');
		if (function_exists('iconv')) {
			$ascii = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
			if ($ascii !== false) {
				$text = $ascii;
			}
		}
		$text = strtolower(trim(preg_replace('/[^\w\s-]/', '', $text)));
		return preg_replace('/[-\s]+/', '-', $text);
	}

	/** ++enter++ and ++ctrl+k++ as keys. */
	static function keys($html)
	{
		return preg_replace_callback('/\+\+([\w-]+(?:\+[\w-]+)*)\+\+/', function ($m) {
			$keys = array();
			foreach (explode('+', $m[1]) as $key) {
				$keys[] = '<kbd>' . htmlspecialchars(ucfirst($key)) . '</kbd>';
			}
			return implode('+', $keys);
		}, $html);
	}
}
