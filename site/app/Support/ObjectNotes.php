<?php

namespace App\Support;

use Devlib\Components\Kanban;

// Catatan tulisan tangan untuk halaman cerita object (ObjectDoc): arti singkat + contoh tiap properti, "boleh ditimpa?" + kegunaan tiap method.
// Nama butir TIDAK disalin: tes (ObjectDocTest) memastikan kunci di sini persis sama dengan konstruktor / method PHP / method JS di kode,
// jadi properti atau method baru tanpa catatan = tes gagal. Teks layar English (nilai ["boleh ditimpa?", "dipakai untuk"]).
final class ObjectNotes
{
    private const NOTES = [
        Kanban::class => [
            'story' => [
                'What it is' => [
                    'A card board. Columns hold cards; people move a card between columns by dragging it or with the "Move to" menu (the non-drag fallback is required). Double-click opens the card dialog (labels, dates, checklist, members, priority, comments, activity). A bar on top searches cards and filters by priority.',
                    'A screen only declares columns and cards: the object draws the rest.',
                ],
                'What it is used for' => [
                    'Work that moves through ordered stages, one card per work item. Each column can carry a WIP limit, and transitions say which moves are allowed.',
                    'In this catalog it runs a production board: work orders moving Queue, Print, Finishing, QC, Done (the KanbanProduksi example below).',
                ],
                'When to choose it' => [
                    'Each item has a stage and people move it from stage to stage.',
                    'Moves must follow rules (transitions) and the server must have the last word (move-url).',
                ],
                'What it does not do' => [
                    'It reads no table or model: data enters only through props.',
                    'It does not save by itself. Without move-url a move stays in the browser; creating, editing and deleting cards only fire the dl:kanban event, so the application must listen and save. The database stays the source of truth for card positions, and the server must validate again.',
                    'Opening a card is a double-click and that is locked; the read-only mode has no drag, no open and no edit.',
                ],
            ],
            'props' => [
                'columns' => ['Columns, left to right: key, label, tone, optional limit (WIP limit) and done (cards in a done column never show as late).', "[['key' => 'qc', 'label' => 'QC', 'tone' => 'violet', 'limit' => 2]]"],
                'cards' => ['Cards: id, col (column key), title, desc, tags, priority (High, Medium, Low), due (YYYY-MM-DD HH:MM), assignee, checklist, comments; a card may carry url and urlLabel (Open button).', "[['id' => 'wo-31', 'col' => 'queue', 'title' => 'Box 3D', 'priority' => 'High']]"],
                'transitions' => ['Allowed moves, from column => to columns. A column that is not listed moves freely; a column with [] cannot be left.', "['queue' => ['print'], 'done' => []]"],
                'members' => ['Names offered in the Members popover of a card.', "['Budi Santoso', 'Rina Wulandari']"],
                'addColumn' => ['Shows the button that adds a column.', 'false'],
                'addCard' => ['Shows "Add card" at the foot of every column.', 'false'],
                'me' => ['Name of the current user: author of comments, activity entries and new cards. The default is translated.', "'Demo Admin'"],
                'source' => ['Label of where the data comes from, printed in the data-dl tag of the element.', "'production.prd_wo_operation'"],
                'moveUrl' => ['Address that receives every move as POST JSON {card, from, to}. The server decides: a rejected move puts the card back and shows the message.', "'/work-orders/move'"],
                'readonly' => ['Read-only board: no drag, no open, no edit. Cards with url keep their Open button.', 'true'],
            ],
            'php' => [
                'tag' => ['Required', 'Name of the Blade tag. A subclass returns its own tag, for example x-demo.kanban-produksi.'],
                'source' => ['Yes', 'Text that says where the data comes from (shown in data-dl). Kanban returns its source prop; a subclass can fix it.'],
                'render' => ['Rarely', 'Returns the Blade markup of the board shell. The cards are drawn by JavaScript, so change cardHtml() in the JS class instead.'],
                'lineage' => ['Not needed', 'Parent chain, for example KanbanProduksi ← Kanban ← DevlibComponent; written into data-dl.'],
                'withAttributes' => ['Not needed', 'Catches the can="module.action" attribute: without the right, nothing is rendered.'],
                'translateTexts' => ['Not needed', 'Translates the text props through the dictionary.'],
                'dictionaryGroup' => ['Rarely', 'Group of the Translation Dictionary the texts are filed under (Kanban is in grid).'],
                'resolveView' => ['Not needed', 'Resolves the view with translation and the access check.'],
                'jsClass' => ['Yes', 'Name of the JS class that drives the element (data-dl-js). A subclass with its own JS returns that class name.'],
                'lint' => ['Rarely', 'Rule findings for php artisan devlib:lint; nothing is shown on screen. Empty means the configuration follows the rules.'],
                'dl' => ['Not needed', 'Prints data-dl (name, parent chain, source, JS class) on the root element.'],
            ],
            'js' => [
                'init' => ['Rarely', 'Reads the data-* attributes, wires drag, click and key events, then draws the board. Call super.init() first when you override it.'],
                'ini' => ['Yes', 'Initials shown in an avatar.'],
                'due' => ['Rarely', 'Turns the due text of a card into a date (UTC+7).'],
                'dueTxt' => ['Yes', 'Due date text on the card foot.'],
                'allowed' => ['Yes', 'Rule: may a card move from this column to that one? The parent allows the same column, a column without rules, or a target listed in transitions. Add rules with super.allowed(from, to) && ...'],
                '_emit' => ['Not needed', 'Fires the dl:kanban event; detail is {type, card, from, to}.'],
                'colOf' => ['Not needed', 'Finds a column by key.'],
                'visible' => ['Yes', 'Whether a card shows under the current search text and priority filter.'],
                'cardHtml' => ['Yes', 'Markup of one card. Take the parent markup with super.cardHtml(card) and adjust it.'],
                'render' => ['Rarely', 'Draws all columns and cards again.'],
                'move' => ['Rarely', 'Moves a card: checks allowed(), warns over the WIP limit, writes the activity log, fires dl:kanban and calls persist().'],
                'persist' => ['Rarely', 'Posts the move to move-url; a rejected move puts the card back.'],
                'cardById' => ['Not needed', 'Finds a card by id.'],
                'toneOf' => ['Rarely', 'Picks the colour of a label from its text.'],
                'allLabels' => ['Not needed', 'All labels used on the board.'],
                'log' => ['Not needed', 'Adds an entry to the activity of a card.'],
                'openCard' => ['Rarely', 'Opens the card dialog.'],
                '$' => ['Use, do not override', 'First element inside the root that matches a selector.'],
                '$$' => ['Use, do not override', 'All elements inside the root that match a selector.'],
                'emit' => ['Use, do not override', 'Fires an event from the root element.'],
                'on' => ['Use, do not override', 'Delegated event listener on the root element.'],
            ],
        ],
    ];

    public static function for(string $class): array
    {
        return self::NOTES[$class] ?? [];
    }
}
