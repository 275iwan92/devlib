<x-dl::card title="New request" icon="fa-calendar-plus">
    <x-dl::field.select name="type" label="Type" :options="['annual' => 'Annual leave', 'sick' => 'Sick leave', 'permit' => 'Permit']" value="annual" />
    <x-dl::field.date name="from" label="From" />
    <x-dl::field.date name="to" label="To" />
    <x-dl::field.text name="reason" label="Reason" multiline />
</x-dl::card>
<x-dl::side-screen.foot hint="Your supervisor is notified">
    <x-dl::button icon="fa-paper-plane" label="Submit request" />
</x-dl::side-screen.foot>
