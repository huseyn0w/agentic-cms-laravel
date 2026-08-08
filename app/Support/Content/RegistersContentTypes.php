<?php

namespace App\Support\Content;

/**
 * A plugin that declares one or more content types. The ContentTypeRegistry
 * collects these from ENABLED plugins, so an admin sees a content type only when
 * its plugin is on. Plugins implement this alongside PluginInterface.
 */
interface RegistersContentTypes
{
    /** @return list<ContentType> */
    public function contentTypes(): array;
}
