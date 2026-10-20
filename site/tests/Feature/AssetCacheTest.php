<?php

namespace Tests\Feature;

use Devlib\DevlibServiceProvider;
use MockupParts\MockupPartsServiceProvider;
use Tests\TestCase;

// Aset library yang dilayani PHP: URL berversi (?v= dari asset()) di-cache selamanya, tanpa ?v= tetap no-cache.
class AssetCacheTest extends TestCase
{
    public function test_versioned_assets_are_immutable_and_unversioned_ones_are_revalidated(): void
    {
        foreach (['devlib.css', 'devlib.js'] as $file) {
            $this->assertStringContainsString('immutable', $this->get(DevlibServiceProvider::asset($file))->headers->get('Cache-Control'), $file);
            $this->assertStringContainsString('no-cache', $this->get('/_devlib/'.$file)->headers->get('Cache-Control'), $file.' tanpa ?v=');
        }
        foreach (['mockup-parts.css', 'mockup-parts.js'] as $file) {
            $this->assertStringContainsString('immutable', $this->get(MockupPartsServiceProvider::asset($file))->headers->get('Cache-Control'), $file);
        }
    }

    public function test_bundle_version_includes_the_script_count(): void
    {
        $this->assertMatchesRegularExpression('/\?v=\d+-\d+$/', DevlibServiceProvider::asset('devlib.js'));
    }
}
