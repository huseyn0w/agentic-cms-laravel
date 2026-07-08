<?php
app()->setLocale('en');
fwrite(STDOUT, "VALUE  : " . __('cpanel/pages.title') . "\n");
try { fwrite(STDOUT, "A :bind : " . trim(strip_tags(Blade::render('<x-field :label="__(\'cpanel/pages.title\')" />'))) . "\n"); } catch (\Throwable $e){ fwrite(STDOUT, "A ERR: ".$e->getMessage()."\n"); }
try { fwrite(STDOUT, "B {{}}  : " . trim(strip_tags(Blade::render('<x-field label="{{ __(\'cpanel/pages.title\') }}" />'))) . "\n"); } catch (\Throwable $e){ fwrite(STDOUT, "B ERR: ".$e->getMessage()."\n"); }
try { fwrite(STDOUT, "C @lang : " . trim(strip_tags(Blade::render('<x-field label="@lang(\'cpanel/pages.title\')" />'))) . "\n"); } catch (\Throwable $e){ fwrite(STDOUT, "C ERR: ".$e->getMessage()."\n"); }
