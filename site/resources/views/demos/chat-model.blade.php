{{-- Model Chat (Devlib\Chat): ChatService + MemoryChat (data fiktif) menghasilkan data untuk x-dl::chat. --}}
@php
    $chat = \App\Support\ModelDemo::chat();
@endphp
<x-dl::chat :me="$chat['me']" :people="$chat['people']" :convos="$chat['convos']" height="520px" source="Devlib\Chat\ChatService::boot()" />
<p class="dl-note">The data above is produced by <code>ChatService::boot()</code> over in-memory adapters (<code>MemoryChat</code>, five fictitious people). An application binds <code>Directory</code>, <code>ChatStore</code>, <code>Presence</code> and <code>Uploads</code> to its own storage and adds thin routes that call <code>poll</code>, <code>messages</code>, <code>upload</code> and <code>send</code>. Rules: <code>skills/devlib/chat.md</code>.</p>
