<div class="dl-demo">
    <x-dl::floor-plan label="Floor 2 plan (uploaded image)" image="{{ asset('media/floor-plan-sample.svg') }}" aspect="16:9" :legend="true" :readonly="false"
        :pins="[
            ['id' => 'v1', 'x' => 15, 'y' => 20, 'tone' => 'success', 'label' => 'Unit 11 · Lobby', 'number' => '11', 'href' => '?pin=v1'],
            ['id' => 'v2', 'x' => 62, 'y' => 24, 'tone' => 'danger', 'label' => 'Unit 12 · Open office', 'number' => '12', 'href' => '?pin=v2'],
            ['id' => 'v3', 'x' => 80, 'y' => 74, 'tone' => 'warn', 'label' => 'Unit 13 · Server room', 'number' => '13', 'href' => '?pin=v3'],
        ]"
        :selected="request()->string('pin')->toString() ?: null">
        <x-slot:detail>
            <p class="dl-note">Pins here are links (<code>href</code>): the screen renders the detail on the server. The plan image is any uploaded file; pins stay on top of it.</p>
        </x-slot:detail>
    </x-dl::floor-plan>
</div>
