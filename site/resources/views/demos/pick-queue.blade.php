<div class="dl-demo">
    <x-dl::pick-queue title="Ready to ship" :rows="\App\Support\DemoData::pickQueue()" row-head="Order · customer" qty-head="Goods ready / ordered" due-head="Due"
        button="Create 1 delivery from :n orders" button-one="Create delivery" button-partial="Create delivery (:have of :want :unit)"
        together="same address, can be merged" mismatch="Choose orders with the same customer and address" />
</div>
