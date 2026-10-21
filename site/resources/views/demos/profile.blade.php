{{-- Panel Profile = daftar aksi akun Layer Menu (LayerShell::actions) yang naik dari baris pengguna. Contoh merekam panel penuh bawaan, data fiktif. --}}
@php
    // Bentuk seperti di aplikasi pewaris: header panel = nama + e-mail (tanpa jabatan), footer = nama + baris Host / Client (x-dl::env-badge, diisi lewat binding aplikasi).
    $user = ['name' => 'Budi Santoso', 'email' => 'budi@example.test', 'role' => ''];
    $actions = (new \Devlib\Components\LayerShell(user: $user))->actions();
    $chat = \App\Support\DemoData::chat();
    $chat['me']['name'] = $user['name'];
    $chat['convos'] = array_map(fn ($c) => array_merge($c, ['unread' => 0]), $chat['convos']);
    $tree = [
        ['label' => 'Sales', 'icon' => 'fa-cart-shopping', 'items' => [['key' => 'co', 'label' => 'Customer Order', 'icon' => 'fa-clipboard-list']]],
        ['label' => 'Inventory', 'icon' => 'fa-boxes-stacked', 'items' => [['key' => 'card', 'label' => 'Stock Card', 'icon' => 'fa-rectangle-list']]],
    ];
    $views = [
        ['key' => 'ops', 'label' => 'Operational', 'icon' => 'fa-table-cells-large', 'items' => $tree],
        ['key' => 'mgmt', 'label' => 'Management Reports', 'icon' => 'fa-chart-line', 'items' => [['key' => 'r-sales', 'label' => 'Sales Summary', 'icon' => 'fa-chart-column']]],
    ];
    // Per item: fungsi, nilai without yang menyembunyikannya, dan yang harus disediakan aplikasi (dibaca dari LayerShell dan shell/overlays).
    $info = [
        'notif' => ['Opens the Notifications drawer with a mark-all-as-read link.', 'notif', 'account.notifications = [read => url, items => [...]]; empty = preview items.'],
        'chat' => ['Opens Chat in the panel or the right drawer.', 'chat', 'chat = [me, people, convos, api]; empty api = preview without server.'],
        'ai' => ['Opens the AI Assistant drawer.', 'ai', 'assistant = [api => [ask, feedback, csrf], hello, chips, history]; empty = preview that matches menu names.'],
        'edit' => ['Edits name, email and phone (small modal), or a wide drawer with a full screen.', 'edit', 'account.profile = [url, phone, role] for the modal, or drawers.edit = [label, icon, url].'],
        'help' => ['Opens the Help & support tab, or a wide drawer.', 'help', 'help-url, or drawers.help = [label, icon, url]; empty = "soon" message.'],
        'pass' => ['Opens the Change password modal.', 'cannot be hidden', 'account.password = [url] with account.csrf; empty = preview where Save only closes.'],
        'logout' => ['Asks for confirmation, then posts the hidden logout form.', 'cannot be hidden', 'logout-action = url that accepts POST.'],
    ];
    $names = ['notif' => 'Notifications', 'chat' => 'Chat', 'ai' => 'AI Assistant', 'edit' => 'Edit profile', 'help' => 'Help & support', 'pass' => 'Change password', 'logout' => 'Sign out'];
    $rows = array_map(fn ($a) => ['item' => $names[$a['key']], 'does' => $info[$a['key']][0], 'without' => $info[$a['key']][1], 'app' => $info[$a['key']][2]], $actions);
    array_splice($rows, 5, 0, [['item' => 'Setup (Theme, Language)', 'does' => 'Page inside the panel listing Theme and Language, inserted after Help & support.', 'without' => 'setup (all), theme (Theme only)', 'app' => 'Nothing: built in.']]);
    $snippet = <<<'BLADE'
<x-dl::layer-shell
    :modules="$modules"
    :user="['name' => 'Budi Santoso', 'email' => 'budi@example.test', 'role' => 'Operator']"
    :account="$account"
    :chat="$chat"
    :assistant="$assistant"
    :drawers="$drawers"
    help-url="/help"
    logout-action="{{ route('logout') }}"
/>
BLADE;
@endphp
@php
    app()->bind('devlib.env_where', fn () => 'Host: HOST-01 / 10.0.0.10 · Client: 127.0.0.1');
@endphp
<div class="dl-lm-demo">
    <x-dl::layer-menu id="demo-profile" search :views="$views" :user="$user['name']" :login="$user['email']" :actions="$actions">
        <x-slot:chat><x-dl::chat compact :me="$chat['me']" :people="$chat['people']" :convos="$chat['convos']" /></x-slot:chat>
        <x-slot:profile>
            <section data-lm-page="setup" data-label="Setup" data-icon="fa-sliders" data-lm-children="theme,lang"></section>
            <section data-lm-page="theme" data-label="Theme" data-icon="fa-palette"><x-dl::theme-picker /></section>
            <section data-lm-page="lang" data-label="Language" data-icon="fa-globe"><x-dl::lang-switch /></section>
        </x-slot:profile>
    </x-dl::layer-menu>
    <div class="dl-sm-demo__page"><div><b>Profile panel</b><br>Click the avatar row at the bottom of the left menu: the panel slides up over the menu at the same width. Close it with the X, the avatar row again, or Back inside a page (Setup).<br>Header = name and email; below it the account actions of the Layer Shell with their default items (nothing hidden); the footer line (Host / Client) comes from <code>x-dl::env-badge</code>. Fictitious data.</div></div>
</div>
@php
    unset(app()['devlib.env_where']);
@endphp
<x-dl::card title="Items" description="Rows are read from LayerShell::actions() when this page opens; the Setup row is the extra page added by the shell">
    <x-dl::table :columns="['item' => 'Item', 'does' => 'What it does', 'without' => 'Hidden by without', 'app' => 'The application provides']" :rows="$rows" :striped="false" source="LayerShell::actions()" />
    <p class="dl-note">An application without these features hides them with <code>:without="['notif', 'chat', 'ai', 'edit', 'help', 'setup']"</code>; Change password and Sign out always stay. The separator line follows the first visible item of the lower group.</p>
</x-dl::card>
<x-dl::card title="Usage" description="The profile panel needs no markup of its own: x-dl::layer-shell builds it from the user and these props">
    <div class="dl-code__file">resources/views/home.blade.php</div>
    <pre class="dl-code__pre"><code>{{ $snippet }}</code></pre>
</x-dl::card>
