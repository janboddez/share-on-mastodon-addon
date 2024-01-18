<?php

declare (strict_types=1);
namespace Share_On_Mastodon\League\HTMLToMarkdown\Converter;

use Share_On_Mastodon\League\HTMLToMarkdown\Configuration;
use Share_On_Mastodon\League\HTMLToMarkdown\ConfigurationAwareInterface;
use Share_On_Mastodon\League\HTMLToMarkdown\ElementInterface;
/** @internal */
class LinkConverter implements ConverterInterface, ConfigurationAwareInterface
{
    /** @var Configuration */
    protected $config;
    public function setConfig(Configuration $config) : void
    {
        $this->config = $config;
    }
    public function convert(ElementInterface $element) : string
    {
        $href = $element->getAttribute('href');
        $title = $element->getAttribute('title');
        $text = \trim($element->getValue(), "\t\n\r\x00\v");
        if ($title !== '') {
            $markdown = '[' . $text . '](' . $href . ' "' . $title . '")';
        } elseif ($href === $text && $this->isValidAutolink($href)) {
            $markdown = '<' . $href . '>';
        } elseif ($href === 'mailto:' . $text && $this->isValidEmail($text)) {
            $markdown = '<' . $text . '>';
        } else {
            if (\stristr($href, ' ')) {
                $href = '<' . $href . '>';
            }
            $markdown = '[' . $text . '](' . $href . ')';
        }
        if (!$href) {
            if ($this->shouldStrip()) {
                $markdown = $text;
            } else {
                $markdown = \html_entity_decode($element->getChildrenAsString());
            }
        }
        return $markdown;
    }
    /**
     * @return string[]
     */
    public function getSupportedTags() : array
    {
        return ['a'];
    }
    private function isValidAutolink(string $href) : bool
    {
        $useAutolinks = $this->config->getOption('use_autolinks');
        return $useAutolinks && \preg_match('/^[A-Za-z][A-Za-z0-9.+-]{1,31}:[^<>\\x00-\\x20]*/i', $href) === 1;
    }
    private function isValidEmail(string $email) : bool
    {
        // Email validation is messy business, but this should cover most cases
        return \filter_var($email, \FILTER_VALIDATE_EMAIL) !== \false;
    }
    private function shouldStrip() : bool
    {
        return \boolval($this->config->getOption('strip_placeholder_links') ?? \false);
    }
}
