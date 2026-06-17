<?php

namespace Tests\Unit;

use App\Services\HtmlSanitizer;
use Tests\TestCase;

class HtmlSanitizerTest extends TestCase
{
    private HtmlSanitizer $sanitizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->sanitizer = $this->app->make(HtmlSanitizer::class);
    }

    // ── null / empty inputs ───────────────────────────────────────────────────

    public function test_null_input_returns_null(): void
    {
        $this->assertNull($this->sanitizer->sanitize(null));
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull($this->sanitizer->sanitize(''));
    }

    public function test_whitespace_only_returns_null(): void
    {
        $this->assertNull($this->sanitizer->sanitize('   '));
    }

    public function test_empty_paragraph_returns_null(): void
    {
        $this->assertNull($this->sanitizer->sanitize('<p></p>'));
    }

    public function test_paragraph_with_only_br_returns_null(): void
    {
        // <br> is not in the allowlist, so it is stripped, leaving <p></p>
        // which is then removed by AutoFormat.RemoveEmpty.
        $this->assertNull($this->sanitizer->sanitize('<p><br></p>'));
    }

    public function test_multiple_empty_paragraphs_return_null(): void
    {
        $this->assertNull($this->sanitizer->sanitize('<p></p><p></p>'));
    }

    // ── allowlisted elements preserved ───────────────────────────────────────

    public function test_paragraph_with_content_is_preserved(): void
    {
        $result = $this->sanitizer->sanitize('<p>Hello world</p>');
        $this->assertSame('<p>Hello world</p>', $result);
    }

    public function test_arabic_text_in_paragraph_is_preserved(): void
    {
        $result = $this->sanitizer->sanitize('<p>محتوى عربي</p>');
        $this->assertStringContainsString('محتوى عربي', $result);
    }

    public function test_heading_levels_h2_h3_h4_are_preserved(): void
    {
        $input = '<h2>Title</h2><h3>Subtitle</h3><h4>Section</h4>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('<h2>', $result);
        $this->assertStringContainsString('<h3>', $result);
        $this->assertStringContainsString('<h4>', $result);
    }

    public function test_inline_formatting_preserved(): void
    {
        $input = '<p><strong>bold</strong> <em>italic</em> <u>underline</u> <s>strike</s></p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('<strong>bold</strong>', $result);
        $this->assertStringContainsString('<em>italic</em>', $result);
        $this->assertStringContainsString('<u>underline</u>', $result);
        $this->assertStringContainsString('<s>strike</s>', $result);
    }

    public function test_lists_are_preserved(): void
    {
        $input = '<ul><li>Item 1</li><li>Item 2</li></ul><ol><li>One</li></ol>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('<ul>', $result);
        $this->assertStringContainsString('<ol>', $result);
        $this->assertStringContainsString('<li>Item 1</li>', $result);
    }

    public function test_blockquote_pre_code_hr_are_preserved(): void
    {
        $input = '<blockquote><p>quote</p></blockquote><pre><code>code</code></pre><hr>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('<blockquote>', $result);
        $this->assertStringContainsString('<pre>', $result);
        $this->assertStringContainsString('<code>', $result);
        $this->assertStringContainsString('<hr', $result);
    }

    public function test_link_attributes_are_preserved(): void
    {
        $input = '<p><a href="https://example.com" rel="noopener noreferrer">link</a></p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('href="https://example.com"', $result);
        $this->assertStringContainsString('<a ', $result);
    }

    public function test_image_attributes_are_preserved(): void
    {
        $input = '<p><img src="https://example.com/img.jpg" alt="photo" width="100" height="80"></p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('src="https://example.com/img.jpg"', $result);
        $this->assertStringContainsString('alt="photo"', $result);
    }

    public function test_text_align_style_is_preserved(): void
    {
        $input = '<p style="text-align: center;">centered</p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('text-align', $result);
        $this->assertStringContainsString('centered', $result);
    }

    // ── disallowed elements stripped ─────────────────────────────────────────

    public function test_script_tag_is_stripped(): void
    {
        $input = '<p>safe</p><script>alert("xss")</script>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringNotContainsString('alert', $result);
    }

    public function test_h1_is_stripped(): void
    {
        // h1 is outside the toolbar allowlist
        $input = '<h1>Should be stripped</h1><p>kept</p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('<h1>', $result);
        $this->assertStringContainsString('kept', $result);
    }

    public function test_div_and_span_are_stripped(): void
    {
        $input = '<div><span style="color:red">text</span></div>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('<div>', $result);
        $this->assertStringNotContainsString('<span>', $result);
        $this->assertStringContainsString('text', $result);
    }

    public function test_event_handler_attribute_is_stripped(): void
    {
        $input = '<p onclick="alert(1)">text</p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('onclick', $result);
    }

    public function test_disallowed_css_property_is_stripped(): void
    {
        // only text-align is allowed; color should be removed
        $input = '<p style="color: red; text-align: left;">text</p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('color', $result);
        $this->assertStringContainsString('text-align', $result);
    }

    public function test_javascript_href_is_stripped(): void
    {
        $input = '<a href="javascript:alert(1)">click</a>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('javascript:', $result);
    }

    // ── rel enforcement on target="_blank" ───────────────────────────────────

    public function test_blank_target_link_gets_enforced_rel(): void
    {
        $input = '<p><a href="https://example.com" target="_blank">link</a></p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $result);
    }

    public function test_blank_target_link_overwrites_existing_rel(): void
    {
        $input = '<p><a href="https://example.com" target="_blank" rel="nofollow">link</a></p>';
        $result = $this->sanitizer->sanitize($input);
        // Must contain the full forced value, not just the original nofollow.
        $this->assertStringContainsString('rel="noopener noreferrer nofollow"', $result);
        // Must not contain any other rel value.
        $this->assertEquals(1, substr_count((string) $result, 'rel='));
    }

    public function test_non_blank_target_link_is_not_modified(): void
    {
        $input = '<p><a href="https://example.com">link</a></p>';
        $result = $this->sanitizer->sanitize($input);
        $this->assertStringNotContainsString('rel=', $result);
    }
}
