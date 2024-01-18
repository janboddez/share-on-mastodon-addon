<?php

declare (strict_types=1);
namespace Share_On_Mastodon\League\HTMLToMarkdown;

/** @internal */
interface PreConverterInterface
{
    public function preConvert(ElementInterface $element) : void;
}
