<?php

namespace App\Services;

use Mews\Purifier\Facades\Purifier;

/**
 * Sanitizes rich-text HTML produced by the Tiptap editor.
 *
 * Uses the 'tiptap' HTMLPurifier profile defined in config/purifier.php.
 * Suitable for any nullable or required HTML body/description field.
 */
class HtmlSanitizer
{
    /**
     * Sanitize editor HTML and return clean markup, or null when the
     * submission is effectively empty (blank editor, only whitespace, or
     * markup that carries no visible text after purification).
     *
     * @param  string|null  $html  Raw HTML from the request.
     * @return string|null Sanitized HTML, or null if effectively empty.
     */
    public function sanitize(?string $html): ?string
    {
        if ($html === null || trim($html) === '') {
            return null;
        }

        $clean = Purifier::clean($html, 'tiptap');

        // Force rel="noopener noreferrer nofollow" on every <a target="_blank">
        // regardless of what the client sent — security/SEO must not depend
        // on client-side correctness.
        $clean = $this->enforceBlankTargetRel($clean);

        // Normalize genuinely empty content to null. AutoFormat.RemoveEmpty
        // removes bare empty tags (<p></p>) during purification, so a truly
        // empty submission produces an empty string here. strip_tags() is
        // intentionally NOT used: a paragraph containing only an image has
        // no text but is still meaningful content.
        if (trim($clean) === '') {
            return null;
        }

        return $clean;
    }

    /**
     * Rewrite rel attributes on every <a target="_blank"> in the
     * already-purified HTML.  Because HTMLPurifier has already run, the
     * markup is well-formed and uses consistent double-quote delimiters,
     * making the regex safe and predictable.
     */
    private function enforceBlankTargetRel(string $html): string
    {
        if (! str_contains($html, '_blank')) {
            return $html;
        }

        return (string) preg_replace_callback(
            '/<a\b([^>]*)>/i',
            function (array $matches): string {
                $attrs = $matches[1];

                if (! preg_match('/\btarget=["\']_blank["\']/i', $attrs)) {
                    return $matches[0];
                }

                // Strip any existing rel value before writing the enforced one.
                $attrs = (string) preg_replace('/\s*\brel=["\'][^"\']*["\']/', '', $attrs);
                $attrs = rtrim($attrs).' rel="noopener noreferrer nofollow"';

                return "<a{$attrs}>";
            },
            $html,
        );
    }
}
