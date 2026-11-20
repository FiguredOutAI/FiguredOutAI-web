<?php
/**
 * Small, safe Markdown renderer. All raw HTML is escaped.
 * Supports: ## headings, paragraphs, **bold**, *italic*, `code`, ``` fenced code ```,
 * [links](url), ![images](url), - lists, 1. lists, > quotes, --- rules, | tables |.
 */
function md_safe_url(string $u): string {
    $u = trim(html_entity_decode($u, ENT_QUOTES, 'UTF-8'));
    if (preg_match('~^(https?://|/|#|mailto:)~i', $u)) return htmlspecialchars($u, ENT_QUOTES, 'UTF-8');
    return '#';
}
function md_inline(string $t): string {
    $codes = [];
    $t = preg_replace_callback('~`([^`]+)`~', function ($m) use (&$codes) { $codes[] = '<code>' . $m[1] . '</code>'; return "\x01" . (count($codes) - 1) . "\x01"; }, $t);
    $t = preg_replace_callback('~!\[([^\]]*)\]\(([^)\s]+)\)~', fn($m) => '<img src="' . md_safe_url($m[2]) . '" alt="' . $m[1] . '" loading="lazy" decoding="async">', $t);
    $t = preg_replace_callback('~\[([^\]]+)\]\(([^)\s]+)\)~', function ($m) {
        $href = md_safe_url($m[2]);
        $ext = preg_match('~^https?://~i', $href) && !str_contains($href, parse_url(cfg('base_url'), PHP_URL_HOST) ?? '###');
        return '<a href="' . $href . '"' . ($ext ? ' target="_blank" rel="noopener"' : '') . '>' . $m[1] . '</a>';
    }, $t);
    $t = preg_replace('~\*\*(.+?)\*\*~', '<strong>$1</strong>', $t);
    $t = preg_replace('~(?<![\w*])\*(?!\s)(.+?)(?<!\s)\*(?![\w*])~', '<em>$1</em>', $t);
    $t = preg_replace('~(?<!\w)_(?!\s)(.+?)(?<!\s)_(?!\w)~', '<em>$1</em>', $t);
    return preg_replace_callback("~\x01(\d+)\x01~", fn($m) => $codes[(int)$m[1]], $t);
}
function md_to_html(string $md, ?array &$toc = null): string {
    $toc = [];
    $ids = [];
    $lines = preg_split("~\r\n|\n|\r~", htmlspecialchars($md, ENT_QUOTES, 'UTF-8'));
    $out = []; $para = []; $list = null; $quote = [];
    $flushP = function () use (&$para, &$out) { if ($para) { $out[] = '<p>' . md_inline(implode(' ', $para)) . '</p>'; $para = []; } };
    $flushL = function () use (&$list, &$out) { if ($list) { $out[] = "<{$list['t']}>" . implode('', array_map(fn($i) => '<li>' . md_inline($i) . '</li>', $list['items'])) . "</{$list['t']}>"; $list = null; } };
    $flushQ = function () use (&$quote, &$out) { if ($quote) { $out[] = '<blockquote><p>' . md_inline(implode(' ', $quote)) . '</p></blockquote>'; $quote = []; } };
    $flushAll = function () use ($flushP, $flushL, $flushQ) { $flushP(); $flushL(); $flushQ(); };
    $n = count($lines);
    for ($i = 0; $i < $n; $i++) {
        $l = $lines[$i];
        if (preg_match('~^```\s*([\w+-]*)~', $l, $m)) {
            $flushAll(); $code = [];
            for ($i++; $i < $n && !preg_match('~^```~', $lines[$i]); $i++) $code[] = $lines[$i];
            $out[] = '<pre><code' . ($m[1] ? ' class="lang-' . $m[1] . '"' : '') . '>' . implode("\n", $code) . '</code></pre>';
            continue;
        }
        if (trim($l) === '') { $flushAll(); continue; }
        if (preg_match('~^(#{1,4})\s+(.+?)\s*#*$~', $l, $m)) {
            $flushAll();
            $lvl = max(2, strlen($m[1]));
            $text = md_inline($m[2]);
            $id = slugify(strip_tags($text)); $base = $id; $k = 2;
            while (isset($ids[$id])) $id = $base . '-' . $k++;
            $ids[$id] = 1;
            if ($lvl <= 3) $toc[] = ['level' => $lvl, 'id' => $id, 'text' => strip_tags($text)];
            $out[] = "<h$lvl id=\"$id\">$text</h$lvl>";
            continue;
        }
        if (preg_match('~^(\*\s*\*\s*\*|-\s*-\s*-)[\s*-]*$~', $l)) { $flushAll(); $out[] = '<hr>'; continue; }
        if (preg_match('~^&gt;\s?(.*)$~', $l, $m)) { $flushP(); $flushL(); $quote[] = $m[1]; continue; }
        if (preg_match('~^\s*[-*+]\s+(.+)$~', $l, $m)) { $flushP(); $flushQ(); if (!$list || $list['t'] !== 'ul') { $flushL(); $list = ['t' => 'ul', 'items' => []]; } $list['items'][] = $m[1]; continue; }
        if (preg_match('~^\s*\d+[.)]\s+(.+)$~', $l, $m)) { $flushP(); $flushQ(); if (!$list || $list['t'] !== 'ol') { $flushL(); $list = ['t' => 'ol', 'items' => []]; } $list['items'][] = $m[1]; continue; }
        if (str_starts_with(trim($l), '|') && isset($lines[$i + 1]) && preg_match('~^\s*\|?[\s:|-]+\|[\s:|-]*$~', $lines[$i + 1])) {
            $flushAll();
            $cells = fn($r) => array_map('trim', explode('|', trim(trim($r), '|')));
            $h = '<div class="table-wrap"><table><thead><tr>' . implode('', array_map(fn($c) => '<th>' . md_inline($c) . '</th>', $cells($l))) . '</tr></thead><tbody>';
            for ($i += 2; $i < $n && str_starts_with(trim($lines[$i]), '|'); $i++) $h .= '<tr>' . implode('', array_map(fn($c) => '<td>' . md_inline($c) . '</td>', $cells($lines[$i]))) . '</tr>';
            $i--;
            $out[] = $h . '</tbody></table></div>';
            continue;
        }
        if ($list && preg_match('~^\s{2,}(.+)$~', $l, $m)) { $list['items'][count($list['items']) - 1] .= ' ' . $m[1]; continue; }
        $flushL(); $flushQ();
        $para[] = trim($l);
    }
    $flushAll();
    return implode("\n", $out);
}
