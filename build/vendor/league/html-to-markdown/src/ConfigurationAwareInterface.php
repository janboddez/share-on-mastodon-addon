<?php

declare (strict_types=1);
namespace Share_On_Mastodon\League\HTMLToMarkdown;

/** @internal */
interface ConfigurationAwareInterface
{
    public function setConfig(Configuration $config) : void;
}
